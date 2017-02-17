<?php

/** @var \Apps\ActiveRecord\Weather[] $records */
/** @var \Ffcms\Core\Helper\HTML\SimplePagination $pagination */
/** @var \Ffcms\Core\Arch\View $this */
/** @var string $tplDir */
use Ffcms\Core\Helper\Date;
use Ffcms\Core\Helper\HTML\Table;
use Ffcms\Core\Helper\Url;

$this->title = __('Weather list');

$this->breadcrumbs = [
    Url::to('main/index') => __('Main'),
    Url::to('application/index') => __('Applications'),
    __('Weather list')
];

?>

<?= $this->render('weather/_tabs', [], $tplDir) ?>

<h1><?= __('Weather points list') ?></h1>
<hr />
<?php
if ($records->count() < 1) {
    echo '<p class="alert alert-warning">' . __('No weather points found. Please add new') . '</p>';
    return;
}

$items = [];
foreach ($records as $point) {
    /** @var \Apps\ActiveRecord\Weather $point */
    $items[] = [
        1 => ['text' => $point->id],
        2 => ['text' => $point->getLocaled('name') . ' (' . $point->latin_name . ')'],
        3 => ['text' => $point->country],
        5 => ['text' => Date::convertToDatetime($point->updated_at, Date::FORMAT_TO_HOUR)],
        6 => ['text' => Url::link(['weather/update', $point->id], '<i class="glyphicon glyphicon-pencil"></i>') . "&nbsp;" .
            Url::link(['weather/delete', $point->id], '<i class="glyphicon glyphicon-trash"></i>'),
            'html' => true, 'property' => ['class' => 'text-center']],
        'property' => [
            'class' => 'checkbox-row'
        ]
    ];
}
?>

<div class="pull-right">
    <?= Url::link(['weather/update', '0'], __('Add point'), ['class' => 'btn btn-primary']) ?>
</div>

<?= Table::display([
    'table' => ['class' => 'table table-bordered'],
    'thead' => [
        'titles' => [
            ['text' => 'id'],
            ['text' => __('Name')],
            ['text' => __('Country')],
            ['text' => __('Updated')],
            ['text' => __('Actions')]
        ]
    ],
    'tbody' => [
        'items' => $items
    ],
    'selectableBox' => [
        'attachOrder' => 1,
        'form' => ['method' => 'GET', 'class' => 'form-horizontal', 'action' => Url::to('weather/delete')],
        'selector' => ['type' => 'checkbox', 'name' => 'selected[]', 'class' => 'massSelectId'],
        'buttons' => [
            ['type' => 'submit', 'class' => 'btn btn-danger', 'value' => __('Delete selected')]
        ]
    ]
]) ?>

<div class="text-center">
    <?= $pagination->display(['class' => 'pagination pagination-centered']) ?>
</div>