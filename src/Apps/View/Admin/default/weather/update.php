<?php

/** @var \Apps\Model\Admin\Weather\FormUpdateWeathers $model */
/** @var \Ffcms\Core\Arch\View $this */
/** @var string $tplDir */
use Ffcms\Core\Helper\Date;
use Ffcms\Core\Helper\HTML\Bootstrap\Nav;
use Ffcms\Core\Helper\HTML\Table;
use Ffcms\Core\Helper\Type\Str;
use Ffcms\Core\Helper\Url;

$this->title = __('Weather point update');

$this->breadcrumbs = [
    Url::to('main/index') => __('Main'),
    Url::to('application/index') => __('Applications'),
    Url::to('weather/index') => __('Weather list'),
    __('Update')
];

?>

<?= $this->render('weather/_tabs', [], $tplDir) ?>

<h1><?= __('Weather item add or update') ?></h1>
<hr />

<p><?= __('You can check your city name and country code in ISO 3166 standard using official weather provider website. Try to find weather data in this form: %link%',
        ['link' => '<a href="https://openweathermap.org/city" target="_blank">Openweathermap.org</a>']); ?></p>

<?php $form = new \Ffcms\Core\Helper\HTML\Form($model, ['class' => 'form-horizontal', 'method' => 'post']); ?>

<?= $form->start() ?>

<?php
$items = [];
foreach (\App::$Properties->get('languages') as $lang) {
    $items[] = [
        'type' => 'tab',
        'text' => __('Lang') . ': ' . Str::upperCase($lang),
        'content' => $form->field('name.' . $lang, 'text', ['class' => 'form-control'], __('Set weather point name. Example: Moscow')),
        'html' => true,
        'active' => $lang === \App::$Request->getLanguage(),
        '!secure' => true
    ];
}
?>

<?= Nav::display([
    'property' => ['class' => 'nav-pills'],
    'blockProperty' => ['class' => 'nav-locale-block nav-border'],
    'tabAnchor' => 'category-update-locale',
    'items' => $items
]) ?>


<?= $form->field('latin_name', 'text', ['class' => 'form-control'], __('Set city name in ISO 3166 standard. Example: Moscow, London, Washington, Kiev')) ?>
<?= $form->field('country', 'text', ['class' => 'form-control'], __('Set country name in ISO 3166 standard. Example: ru, uk, us, ua')) ?>

<div class="col-md-9 col-md-offset-3">
    <?= $form->submitButton(__('Save'), ['class' => 'btn btn-primary']) ?>
</div>

<?= $form->finish() ?>
