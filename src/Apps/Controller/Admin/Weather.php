<?php

namespace Apps\Controller\Admin;

use Apps\ActiveRecord\App as AppRecord;
use Apps\ActiveRecord\Weather as WeatherRecord;
use Apps\Model\Admin\Weather\FormWeatherDelete;
use Apps\Model\Admin\Weather\FormWeatherSettings;
use Apps\Model\Admin\Weather\FormWeatherUpdate;
use Extend\Core\Arch\AdminController;
use Ffcms\Core\App;
use Ffcms\Core\Exception\NotFoundException;
use Ffcms\Core\Helper\Date;
use Ffcms\Core\Helper\FileSystem\File;
use Ffcms\Core\Helper\HTML\SimplePagination;
use Ffcms\Core\Helper\Serialize;
use Ffcms\Core\Helper\Type\Arr;
use Ffcms\Core\Helper\Type\Obj;
use Ffcms\Core\Helper\Type\Str;
use Ffcms\Core\Managers\MigrationsManager;

class Weather extends AdminController
{
    const VERSION = '1.0.0';
    const ITEM_PER_PAGE = 10;
    const CRON_DELAY = 1800; // 30 min delay for each point updates

    private $appRoot;
    private $tplDir;
    // http://api.openweathermap.org/data/2.5/forecast?q=moscow,ru&units=metric&appid={key}

    /**
     * Initialize application: set app route path, tpl path, append language translations
     */
    public function before()
    {
        parent::before();
        // define application root diskpath and tpl native directory
        $this->appRoot = realpath(__DIR__ . '/../../../');
        $this->tplDir = realpath($this->appRoot . '/Apps/View/Admin/default/');
        // load internalization package for current lang
        $langFile = $this->appRoot . '/I18n/Admin/' . App::$Request->getLanguage() . '/Weather.php';
        if (App::$Request->getLanguage() !== 'en' && File::exist($langFile)) {
            App::$Translate->append($langFile);
        }
    }

    /**
     * Show weather city points
     * @return string
     * @throws \Ffcms\Core\Exception\SyntaxException
     * @throws \Ffcms\Core\Exception\NativeException
     */
    public function actionIndex()
    {
        $query = new WeatherRecord();
        // set current page and offset
        $page = (int)$this->request->query->get('page', 0);
        $offset = $page * self::ITEM_PER_PAGE;

        // build pagination
        $pagination = new SimplePagination([
            'url' => ['weather/index'],
            'page' => $page,
            'step' => self::ITEM_PER_PAGE,
            'total' => $query->count()
        ]);

        // get fetch result array
        $records = $query->orderBy('id', 'desc')->skip($offset)->take(self::ITEM_PER_PAGE)->get();

        // render view output
        return $this->view->render('weather/index', [
            'records' => $records,
            'tplDir' => $this->tplDir,
            'pagination' => $pagination
        ], $this->tplDir);
    }

    /**
     * Create or update weather points action
     * @param int|null $id
     * @return string
     * @throws \Ffcms\Core\Exception\SyntaxException
     * @throws \Ffcms\Core\Exception\NativeException
     */
    public function actionUpdate($id = null)
    {
        // find weather active record object & init model
        $weathers = WeatherRecord::findOrNew($id);
        $configs = $this->getConfigs();
        $model = new FormWeatherUpdate($weathers, $configs['key']);

        // check if form submited
        if ($model->send() && $model->validate()) {
            $model->make();
            App::$Session->getFlashBag()->add('success', __('Weather point is successful updated'));
            $this->response->redirect('weather/index');
        }

        // render output view
        return $this->view->render('weather/update', [
            'tplDir' => $this->tplDir,
            'model' => $model
        ], $this->tplDir);
    }

    /**
     * Delete user row from database
     * @param int $id
     * @return string
     * @throws \Ffcms\Core\Exception\SyntaxException
     * @throws \Ffcms\Core\Exception\NativeException
     * @throws NotFoundException
     */
    public function actionDelete($id = null)
    {
        // check if id is passed or get data from GET as array ids
        if ($id === 0 || (int)$id < 1) {
            $ids = $this->request->query->get('selected');
            if (Obj::isArray($ids) && Arr::onlyNumericValues($ids)) {
                $id = $ids;
            } else {
                throw new NotFoundException('Bad conditions');
            }
        } else {
            $id = [$id];
        }

        // initialize delete model
        $model = new FormWeatherDelete($id);

        // check if users is found
        if ($model->points === null) {
            throw new NotFoundException(__('Weather points are not found'));
        }

        // check if delete is submited
        if ($model->send() && $model->validate()) {
            $model->delete();
            App::$Session->getFlashBag()->add('success', __('All selected points are successful removed'));
            $this->response->redirect('weather/index');
        }

        // set view response
        return $this->view->render('weather/delete', [
            'model' => $model,
            'tplDir' => $this->tplDir
        ], $this->tplDir);
    }

    /**
     * Show app settings form
     * @return string
     * @throws \Ffcms\Core\Exception\SyntaxException
     * @throws \Ffcms\Core\Exception\NativeException
     */
    public function actionSettings()
    {
        // load model and pass property's as argument
        $model = new FormWeatherSettings($this->getConfigs());

        if ($model->send()) {
            if ($model->validate()) {
                $this->setConfigs($model->getAllProperties());
                App::$Session->getFlashBag()->add('success', __('Settings is successful updated'));
                $this->response->redirect('weather/index');
            } else {
                App::$Session->getFlashBag()->add('error', __('Form validation is failed'));
            }
        }

        // render view
        return $this->view->render('weather/settings', [
            'model' => $model,
            'tplDir' => $this->tplDir
        ], $this->tplDir);
    }

    /**
     * Download api data from openweathermap.org to database prefix_weathers.data (binary) column
     * @return bool
     */
    public static function scheduleDownloadTask()
    {
        $time = Date::convertToDatetime(strtotime('-60 min'), Date::FORMAT_SQL_TIMESTAMP);
        $records = WeatherRecord::where('updated_at', '<', $time);
        if ($records->count() < 1) {
            return false;
        }

        $apiKey = \Apps\ActiveRecord\App::getConfig('app', 'Weather', 'key');
        if ($apiKey === null || Str::likeEmpty($apiKey)) {
            return false;
        }

        foreach ($records->get() as $record) {
            $cityCounty = $record->latin_name . ',' . $record->country;
            // get forecast for 5 days
            $data = File::getFromUrl('http://api.openweathermap.org/data/2.5/forecast?q=' . $cityCounty . '&appid=' . $apiKey . '&units=metric');
            // check response data
            $resFull = json_decode($data);
            if ((int)$resFull->cod !== 200 || !isset($resFull->list)) {
                continue;
            }
            // get weather actual data
            $today = File::getFromUrl('http://api.openweathermap.org/data/2.5/weather?q=' . $cityCounty . '&appid=' . $apiKey . '&units=metric');
            $resToday = json_decode($today);
            if ((int)$resToday->cod !== 200) {
                continue;
            }

            // todo: short data from data
            $short = [];
            // list 4 day's
            for ($i=1;$i<=4;$i++) {
                // get date to compare with json data
                $formatedDate = date('Y-m-d 12:00:00', strtotime('+' . $i . ' day'));
                foreach ($resFull->list as $item) {
                    if ($item->dt_txt === $formatedDate) {
                        $short[] = $item;
                    }
                }
            }

            // save data to db
            $record->data = $data;
            $record->short = json_encode($short);
            $record->today = $today;
            $record->save();
        }
        return true;
    }

    /**
     * Install function callback
     */
    public static function install()
    {
        // prepare application information to extend inserted before row to table apps
        $appData = new \stdClass();
        $appData->configs = [
            'key' => '',
            'showMap' => true,
            'mapCenter' => '45.16;34.42'
        ];
        $appData->name = [
            'ru' => 'Погода',
            'en' => 'Weather'
        ];
        // get current app row from db (like SELECT ... WHERE type='app' and sys_name='Demoapp')
        $query = AppRecord::where('type', '=', 'app')->where('sys_name', '=', 'Weather');
        if ($query->count() !== 1) {
            return;
        }
        $query->update([
            'name' => Serialize::encode($appData->name),
            'configs' => Serialize::encode($appData->configs),
            'disabled' => 0
        ]);
        $root = realpath(__DIR__ . '/../../../');
        // implement migrations
        $manager = new MigrationsManager($root . '/Private/Migrations/');
        $manager->makeUp([
            'install_weather_table-2017-02-09-11-10-30.php'
        ]);
        App::$Cron->register('Apps\Controller\Admin\Weather', 'scheduleDownloadTask', static::CRON_DELAY);
    }
}