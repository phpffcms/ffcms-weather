<?php

use Ffcms\Core\Exception\ForbiddenException;
use Ffcms\Core\Helper\Date;
use Ffcms\Core\Helper\Type\Str;
use Ffcms\Core\Helper\Url;

/** @var \Ffcms\Core\Arch\View $this */
/** @var \Apps\ActiveRecord\Weather $record */
/** @var string $viewUri */

App::$Alias->setCustomLibrary('css', $viewUri . '/assets/css/owfont-regular.min.css');
App::$Alias->setCustomLibrary('css', $viewUri . '/assets/css/weather.css');

$this->title = __('Weather: %city%', ['city' => $record->getLocaled('name')]);

$this->breadcrumbs = [
    Url::to('/') => __('Home'),
    Url::to('weather/index') => __('Weather list'),
    $record->getLocaled('name')
];

$weather = json_decode($record->today);
$forecast = json_decode($record->data);

if ((int)$weather->cod !== 200 || (int)$forecast->cod !== 200) {
    throw new ForbiddenException(__('Weather API data response is wrong'));
}

$mapLat = $weather->coord->lat;
$mapLon = $weather->coord->lon;

$tempNow = round($weather->main->temp, 1);
$tempNow = ($tempNow > 0) ? '+' . $tempNow : $tempNow;

$forecastArray = [];
// list 4 day's - prepare forecast array to display separated by day
for ($i=1;$i<=4;$i++) {
    // get date to compare with json data
    $dmyGuess = date('Y-m-d', strtotime('+' . $i . ' day'));
    foreach ($forecast->list as $item) {
        if (Str::startsWith($dmyGuess, $item->dt_txt)) {
            $forecastArray[$dmyGuess][] = $item;
        }
    }
}

?>
<h1><?= $this->title ?></h1>
<hr />
<div class="row">
    <div class="col-md-6">
        <h2 style="margin-top: 5px;"><?= __('Weather now') ?></h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <tbody>
                <tr>
                    <td><?= __('Temperature') ?></td>
                    <td><?= $tempNow ?>&deg;C <i class="owf owf-<?= $weather->weather[0]->id ?>-d"></i></td>
                </tr>
                <tr>
                    <td><?= __('Wind') ?></td>
                    <td><?= round($weather->wind->speed, 1) ?> <?= __('m/s') ?></td>
                </tr>
                <tr>
                    <td><?= __('Humidity') ?></td>
                    <td><?= $weather->main->humidity ?>%</td>
                </tr>
                <tr>
                    <td><?= __('Pressure') ?></td>
                    <td><?= round($weather->main->pressure * 0.75, 1) ?> <?= __('mmHg') ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <link rel="stylesheet" href="<?= \App::$Alias->scriptUrl ?>/vendor/bower/leaflet/dist/leaflet.css" />
        <script src="<?= \App::$Alias->scriptUrl ?>/vendor/bower/leaflet/dist/leaflet.js"></script>
        <div class="row" style="padding-bottom: 15px;">
            <div class="col-md-12">
                <div id="weather-map" style="height: 210px;"></div>
            </div>
        </div>
        <script>
            var wmap = L.map('weather-map').setView([<?= (float)$mapLat . ', ' . (float)$mapLon ?>], 11);
            L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=<?= $configs['mapKey'] ?>', {
                maxZoom: 18,
                attribution: '',
                id: 'mapbox.streets'
            }).addTo(wmap);

            L.marker([<?= $weather->coord->lat . ', ' . $weather->coord->lon ?>], {opacity: '1'}).addTo(wmap)
                .bindTooltip('<i class="owf owf-<?= $weather->weather[0]->id ?>-d"></i> <?= $tempNow ?>', {permanent: true});
        </script>
    </div>
</div>
<h2><?= __('Weather forecast: %city%', ['city' => $record->getLocaled('name')]) ?></h2>

<?php foreach ($forecastArray as $date => $day): ?>
<div class="row">
    <div class="col-md-2" style="font-size: 24px;padding-top: 26px;">
        <p class="text-center"><?= Date::convertToDatetime($date, 'd.m') ?></p>
    </div>
    <div class="col-md-10">
        <div class="row">
            <div class="col-sm-2 col-xs-4 text-center"><?= __('Temperature') ?></div>
            <div class="col-sm-4 col-xs-4 text-center"><?= __('Weather') ?></div>
            <div class="col-sm-2 col-xs-4 text-center"><?= __('Wind') ?></div>
            <div class="col-sm-2 hidden-xs text-center"><?= __('Pressure') ?></div>
            <div class="col-sm-2 hidden-xs text-center"><?= __('Humidity') ?></div>
        </div>
        <?php foreach ($day as $hourly): ?>
            <?php
            $isDay = ((int)Date::convertToDatetime($hourly->dt, 'H') > 6 && (int)Date::convertToDatetime($hourly->dt, 'H') < 21);
            $weatherClass = 'wclear';
            $weatherId = (int)$hourly->weather[0]->id;
            if ($weatherId === 800 && !$isDay) {
                $weatherClass = 'wnclear';
            }
            if ($weatherId >= 200 && $weatherId <= 250) {
                $weatherClass = 'wlighting';
            }
            if ($weatherId >= 500 && $weatherId <= 550) {
                $weatherClass = 'wrain';
            }
            if ($weatherId >= 600 && $weatherId <= 650) {
                $weatherClass = 'wsnow';
            }
            if ($weatherId >= 801 && $weatherId <= 804) {
                $weatherClass = 'wclouds';
            }
            ?>
        <div class="row weather-details <?= $weatherClass ?>">
            <div class="col-sm-2 col-xs-3">
                <small><?= Date::convertToDatetime($hourly->dt, 'H:i') ?></small> <br />
                <p class="text-center" style="font-size: 16px;font-weight: bold;">
                    <?php
                    $hourlyTemp = round($hourly->main->temp, 1);
                    if ($hourlyTemp > 0) {
                        $hourlyTemp = '+' . $hourlyTemp;
                    }
                    echo $hourlyTemp
                    ?>&deg;C
                </p>
            </div>
            <div class="col-sm-4 col-xs-5">
                <i class="owf owf-<?= $hourly->weather[0]->id ?>-<?= ($isDay) ? 'd' : 'n' ?> owf-2x"></i>
                <?= __($hourly->weather[0]->description) ?>
            </div>
            <div class="col-sm-2 col-xs-3">
                <?= round($hourly->wind->speed, 1) ?><?= __('m/s') ?>
            </div>
            <div class="col-md-2 hidden-xs">
                <?= round(0.75 * $hourly->main->pressure, 0) ?><?= __('mmHg') ?>
            </div>
            <div class="col-md-1 hidden-xs">
                <?= $hourly->main->humidity ?>%
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>