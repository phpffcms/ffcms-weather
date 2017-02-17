<?php

namespace Apps\Model\Admin\Weather;


use Apps\ActiveRecord\Weather as WeatherRecord;
use Ffcms\Core\App;
use Ffcms\Core\Arch\Model;
use Ffcms\Core\Exception\ForbiddenException;
use Ffcms\Core\Helper\FileSystem\File;
use Ffcms\Core\Helper\Type\Str;
use Ffcms\Core\Helper\Url;

/**
 * Class FormUpdateWeathers. Business logic for updating weather form
 * @package Apps\Model\Admin\Weather
 */
class FormWeatherUpdate extends Model
{
    public $name;
    public $latin_name;
    public $country;
    public $data;


    /** @var WeatherRecord */
    private $_record;
    private $apiKey;

    /**
     * FormUpdateWeathers constructor.
     * @param WeatherRecord $record
     * @param string|null $apiKey
     */
    public function __construct(WeatherRecord $record, $apiKey = null)
    {
        $this->_record = $record;
        $this->apiKey = $apiKey;
        parent::__construct(true);
    }

    /**
     * Load user data on before method
     * @throws \Ffcms\Core\Exception\ForbiddenException
     */
    public function before()
    {
        // check api key
        if ($this->apiKey === null || Str::likeEmpty($this->apiKey)) {
            throw new ForbiddenException(App::$Translate->get('Weather', 'Api key is empty! Please follow to settings and set openweather api key'));
        }

        foreach ($this->getAllProperties() as $property => $old_data) {
            if ($this->_record->{$property} !== null) {
                $this->{$property} = $this->_record->{$property};
            }
        }
    }

    /**
     * Validation rules
     * @return array
     */
    public function rules()
    {
        return [
            [['name', 'latin_name', 'country'], 'required'],
            ['latin_name', 'Apps\Model\Admin\Weather\FormWeatherUpdate::checkApi', $this->getRequest('country', 'post')]
        ];
    }

    /**
     * Display labels
     * @return array
     */
    public function labels()
    {
        return [
            'name' => __('Name'),
            'latin_name' => __('International name'),
            'country' => __('International country'),
        ];
    }

    /**
     * Save changes in database
     */
    public function make()
    {
        $this->_record->name = $this->name;
        $this->_record->latin_name = $this->latin_name;
        $this->_record->country = $this->country;
        $this->_record->save();
    }

    /**
     * Filter to check validity of new city,country data
     * @param $city
     * @param $country
     * @return bool
     */
    public function checkApi($city, $country)
    {
        $request = File::getFromUrl('http://api.openweathermap.org/data/2.5/weather?q=' . $city . ',' . $country . '&appid=' . $this->apiKey);
        $response = json_decode($request);

        return (int)$response->cod === 200;
    }

}