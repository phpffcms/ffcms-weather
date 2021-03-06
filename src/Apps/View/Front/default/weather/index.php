<?php

use Ffcms\Core\Helper\Date;
use Ffcms\Core\Helper\Type\Str;
use Ffcms\Core\Helper\Url;

/** @var \Apps\ActiveRecord\Weather[] $records */
/** @var \Ffcms\Core\Arch\View $this */
/** @var array $configs */
/** @var string $viewUri */

App::$Alias->setCustomLibrary('css', $viewUri . '/assets/css/owfont-regular.min.css');
App::$Alias->setCustomLibrary('css', $viewUri . '/assets/css/weather.css');

$this->title = __('Weather forecasts');
list($mapLat, $mapLon) = explode(';', $configs['mapCenter']);

?>
<h1><?= __('Weather') ?></h1>
<hr />
<?php if ($records->count() < 1): ?>
    <p class="alert alert-warning"><?= __('No weather data found') ?></p>
    <?php return; ?>
<?php endif; ?>
<?php if ((bool)$configs['showMap'] && !Str::likeEmpty($configs['mapKey'])): ?>
    <link rel="stylesheet" href="<?= \App::$Alias->scriptUrl ?>/vendor/bower/leaflet/dist/leaflet.css" />
    <script src="<?= \App::$Alias->scriptUrl ?>/vendor/bower/leaflet/dist/leaflet.js"></script>
    <div class="row" style="padding-bottom: 15px;">
        <div class="col-md-12">
            <div id="weather-map" style="height: 400px;"></div>
        </div>
    </div>
    <script>
        var wmap = L.map('weather-map').setView([<?= (float)$mapLat . ', ' . (float)$mapLon ?>], 8);
        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=<?= $configs['mapKey'] ?>', {
            maxZoom: 18,
            attribution: '',
            id: 'mapbox.streets'
        }).addTo(wmap);
    </script>
<?php endif; ?>

<div class="row">
<?php foreach ($records as $record): ?>
    <?php $info = json_decode($record->today); ?>
    <?php $day = (date('H') > 7 && date('H') < 19); ?>
    <?php
    $weatherClass = 'weather-clear';
    $weatherId = (int)$info->weather[0]->id;
    if ($weatherId >= 200 && $weatherId <= 250) {
        $weatherClass = 'weather-lighting';
    }
    if ($weatherId >= 500 && $weatherId <= 550) {
        $weatherClass = 'weather-rain';
    }
    if ($weatherId >= 600 && $weatherId <= 650) {
        $weatherClass = 'weather-snow';
    }
    if ($weatherId >= 801 && $weatherId <= 804) {
        $weatherClass = 'weather-clouds';
    }
    ?>
    <div class="col-md-4" id="weather-<?= $record->id ?>">
        <div class="panel <?= $weatherClass ?>">
            <div class="panel-heading">
                <span class="h3"><?= $record->getLocaled('name') ?></span><br />
                <span><?= $record->latin_name ?></span><br />
                <small><?= Date::convertToDatetime(time(), 'd.m.Y H:00') ?></small>
                <div class="weather-main">
                    <div class="row">
                        <div class="col-md-4 nopadding">
                            <i class="owf owf-<?= $weatherId ?>-<?= $day ? 'd' : 'n' ?> owf-4x"></i>
                        </div>
                        <div class="col-md-8 nopadding">
                            <?php
                                $tempNow = round($info->main->temp, 1);
                                $tempNow = ($tempNow > 0) ? '+' . $tempNow : $tempNow;
                            ?>
                            <span class="owf-3x"><?= $tempNow ?>&deg;C
                        </div>
                    </div>
                    <div class="row">
                        <i class="owf owf-956 owf-2x"></i> <span class="owf-2x"><?= round($info->wind->speed, 1) ?><?= __('m/s') ?></span>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <?php $short = json_decode($record->short) ?>
                <?php foreach ($short as $forecast): ?>
                    <div class="row weather-forecast">
                        <div class="col-xs-3 small-padding">
                            <?= Date::convertToDatetime($forecast->dt, 'd.m') ?>
                        </div>
                        <div class="col-xs-4 small-padding">
                            <?php
                            // get weather object
                            $weather = $forecast->weather[0];
                            // round to upper temperature value
                            $temp = (int)ceil($forecast->main->temp);
                            // set "+" if temperature is positive
                            if ($temp > 0) {
                                $temp = '+' . $temp;
                            }
                            ?>
                            <i class="owf owf-<?= $weather->id ?>-d"></i>
                            <?= $temp ?> &deg;C
                        </div>
                        <div class="col-xs-5 small-padding">
                            <i class="owf owf-956"></i>
                            <?= round($forecast->wind->speed,1) ?> <?= __('m/s') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?= Url::link(['weather/show', $record->id], __('More'), ['class' => 'btn btn-success btn-sm btn-block']) ?>
            </div>
        </div>
    </div>
    <script>
        L.marker([<?= $info->coord->lat . ', ' . $info->coord->lon ?>], {opacity: '0.5'}).addTo(wmap)
            .bindTooltip('<i class="owf owf-<?= $weatherId ?>-d"></i> <?= $tempNow ?>', {permanent: true})
            .on('click', function(e){
                $(document).ready(function(){
                    $('#weather-<?= $record->id ?>').addClass('weather-hover');
                    $('html, body').animate({
                        scrollTop: $("#weather-<?= $record->id ?>").offset().top
                    }, 1000);
                });
            });
    </script>
<?php endforeach; ?>
</div>