<?php

namespace Apps\Model\Admin\Weather;

use Ffcms\Core\Arch\Model;

/**
 * Class FormWeatherSettings. Business logic of app settings
 * @package Apps\Model\Admin\Weather
 */
class FormWeatherSettings extends Model
{
    public $key;
    public $showMap;
    public $mapCenter;
    public $mapKey;

    private $_config;

    /**
     * FormWeatherSettings constructor. Pass configs inside the model
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        $this->_config = $config;
        parent::__construct();
    }

    /**
     * Load configs from app data
     */
    public function before()
    {
        if ($this->_config === null) {
            return;
        }
        foreach ($this->_config as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * Form display labels
     * @return array
     */
    public function labels()
    {
        return [
            'key' => __('Api key'),
            'showMap' => __('Show map'),
            'mapKey' => __('Map key'),
            'mapCenter' => __('Map center')
        ];
    }

    /**
     * Validation rules
     * @return array
     */
    public function rules()
    {
        return [
            [['key', 'showMap'], 'required'],
            [['mapCenter', 'mapKey'], 'used']
        ];
    }
}