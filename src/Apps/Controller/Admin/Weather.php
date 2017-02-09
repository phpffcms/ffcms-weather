<?php

namespace Apps\Controller\Admin;

use Apps\ActiveRecord\App as AppRecord;
use Apps\ActiveRecord\Weather as WeatherRecord;
use Apps\Model\Admin\Weather\FormWeatherSettings;
use Extend\Core\Arch\AdminController;
use Ffcms\Core\App;
use Ffcms\Core\Helper\FileSystem\File;
use Ffcms\Core\Helper\HTML\SimplePagination;
use Ffcms\Core\Helper\Serialize;
use Ffcms\Core\Managers\MigrationsManager;

class Weather extends AdminController
{
    const VERSION = '1.0.0';
    const ITEM_PER_PAGE = 10;

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
    }
}