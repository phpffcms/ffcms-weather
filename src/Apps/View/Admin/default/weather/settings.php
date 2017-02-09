<?php

use Ffcms\Core\Helper\HTML\Form;
use Ffcms\Core\Helper\Url;

/** @var $model \Apps\Model\Admin\Weather\FormWeatherSettings */
/** @var string $tplDir */

$this->title = __('Settings');

$this->breadcrumbs = [
    Url::to('main/index') => __('Main'),
    Url::to('application/index') => __('Applications'),
    Url::to('weather/index') => __('Weather list'),
    __('Settings')
];

?>

<?= $this->render('weather/_tabs', [], $tplDir) ?>
<h1><?= $this->title ?></h1>
<hr />

<?php $form = new Form($model, ['class' => 'form-horizontal', 'method' => 'post', 'action' => '']) ?>

<?= $form->start() ?>
<?= $form->field('key', 'text', ['class' => 'form-control'], __('Set your api key from openweathermap.org service. Example: e6s0164zx51e4c50fab325c8ac32eq41')) ?>
<?= $form->field('showMap', 'checkbox', null, __('Show weather data on map?')) ?>
<?= $form->field('mapCenter', 'text', ['class' => 'form-control'], __('Set map center point as lat;lon. Example: 45.21;34.30')) ?>

<div class="col-md-offset-3 col-md-9"><?= $form->submitButton(__('Save'), ['class' => 'btn btn-primary']) ?></div>

<?= $form->finish() ?>