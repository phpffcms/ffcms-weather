<?php

namespace Apps\Model\Admin\Weather;

use Apps\ActiveRecord\Weather as WeatherRecord;
use Ffcms\Core\Arch\Model;

/**
 * Class FormWeatherDelete. Delete passed weather points by $ids
 * @package Apps\Model\Admin\User
 */
class FormWeatherDelete extends Model
{
    public $points;

    private $_ids;

    /**
     * FormUserDelete constructor. Pass user ids inside
     * @param array $ids
     */
    public function __construct(array $ids)
    {
        $this->_ids = $ids;
        parent::__construct(true);
    }

    /**
     * Set weather points objects to model property
     */
    public function before()
    {
        // try to find each point
        foreach ($this->_ids as $id) {
            $point = WeatherRecord::find($id);
            if ($point !== null) {
                $this->points[] = $point;
            }
        }
    }

    /**
     * Delete user from database
     * @throws \Exception
     */
    public function delete()
    {
        foreach ($this->points as $point) {
            $point->delete();
        }
    }


}