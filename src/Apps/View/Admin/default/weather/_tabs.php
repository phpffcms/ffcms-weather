<?php
use Ffcms\Core\Helper\HTML\Bootstrap\Nav;

?>

<?= Nav::display([
    'property' => ['class' => 'nav-tabs nav-justified'],
    'items' => [
        ['type' => 'link', 'text' => __('Weather list'), 'link' => ['weather/index']],
        ['type' => 'link', 'text' => __('Settings'), 'link' => ['weather/settings']]
    ]
]);?>