<?php

namespace Apps\ActiveRecord;

use Ffcms\Core\Arch\ActiveModel;

/**
 * Class Weather. Active record model for weathers table
 * @package Apps\ActiveRecord
 * @property int $id
 * @property array $name
 * @property string $latin_name
 * @property string $country
 * @property string $short
 * @property string $today
 * @property string $data
 * @property string $created_at
 * @property string $updated_at
 */
class Weather extends ActiveModel
{
    protected $casts = [
        'id' => 'integer',
        'name' => 'serialize',
        'latin_name' => 'string',
        'country' => 'string'
    ];
}