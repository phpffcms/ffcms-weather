<?php

/** @var \Apps\Model\Admin\User\FormWeatherDelete $model */
/** @var $this object */
/** @var string $tplDir */
use Ffcms\Core\Helper\Date;
use Ffcms\Core\Helper\HTML\Form;
use Ffcms\Core\Helper\HTML\Table;
use Ffcms\Core\Helper\Url;

$this->title = __('Delete weather points');

$this->breadcrumbs = [
    Url::to('main/index') => __('Main'),
    Url::to('application/index') => __('Applications'),
    Url::to('weather/index') => __('Weather list'),
    __('Delete points')
];
?>

<?= $this->render('weather/_tabs', null, $tplDir) ?>

<h1><?= __('Delete weather points') ?></h1>
<hr />
<p><?= __('Are you sure to delete this points?') ?></p>
<?php
$items = [];
foreach ($model->points as $point) {
    /** @var \Apps\ActiveRecord\Weather $point */
    $items[] = [
        ['text' => $point->id],
        ['text' => $point->getLocaled('name')],
        ['text' => $point->latin_name],
        ['text' => $point->country],
        ['text' => Date::convertToDatetime($point->created_at, Date::FORMAT_TO_HOUR)]
    ];
}
?>

<?= Table::display([
    'table' => ['class' => 'table table-bordered'],
    'thead' => [
        'titles' => [
            ['text' => '#'],
            ['text' => __('Name')],
            ['text' => __('International name')],
            ['text' => __('Country')],
            ['text' => __('Created date')]
        ]
    ],
    'tbody' => [
        'items' => $items
    ]
]) ?>


<?php
$form = new Form($model, ['class' => 'form-horizontal', 'method' => 'post', 'action' => '']);
echo $form->start();
echo $form->submitButton(__('Delete'), ['class' => 'btn btn-danger']) . "&nbsp;";
echo Url::link(['weather/index'], __('Cancel'), ['class' => 'btn btn-default']);
echo $form->finish();
?>