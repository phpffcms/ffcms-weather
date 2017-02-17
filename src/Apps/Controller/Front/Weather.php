<?php

namespace Apps\Controller\Front;

use Apps\ActiveRecord\Weather as WeatherRecord;
use Extend\Core\Arch\FrontAppController;
use Ffcms\Core\App;
use Ffcms\Core\Helper\FileSystem\File;

class Weather extends FrontAppController
{
    public $appRoot;
    public $tplDir;
    public $viewUri;

    /**
     * Set default vars and files
     */
    public function before()
    {
        parent::before();
        // define application root diskpath and tpl native directory
        $this->appRoot = realpath(__DIR__ . '/../../../');
        $this->tplDir = realpath($this->appRoot . '/Apps/View/Front/default/');
        $this->viewUri = App::$Alias->currentViewUrl;
        if ($this->appRoot !== root) {
            $this->viewUri = '/vendor/phpffcms/ffcms-weather/src/Apps/View/Front/default';
        }
        // load internalization package for current lang
        $langFile = $this->appRoot . '/I18n/Admin/' . App::$Request->getLanguage() . '/Weather.php';
        if (App::$Request->getLanguage() !== 'en' && File::exist($langFile)) {
            App::$Translate->append($langFile);
        }
    }

    /**
     * Show weather short info list
     * @return string
     * @throws \Ffcms\Core\Exception\SyntaxException
     * @throws \Ffcms\Core\Exception\NativeException
     */
    public function actionIndex()
    {
        // select all rows from db to show. DO NOT USE COLUMN 'data', cuz it binary with size > 15kB for each one
        $records = WeatherRecord::whereNotNull('today')->get(['id', 'name', 'latin_name', 'short', 'today', 'updated_at']);
        $configs = $this->getConfigs();

        // render output view
        return $this->view->render('weather/index', [
            'records' => $records,
            'configs' => $configs,
            'viewUri' => $this->viewUri
        ], $this->tplDir);
    }

    public function actionShow($id)
    {

        return $this->view->render('weather/show', [

        ], $this->tplDir);
    }

}