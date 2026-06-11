<?php

namespace justinholtweb\updatepermissionsreminder\web\assets\reminder;

use craft\web\AssetBundle;
use craft\web\View;

/**
 * CP asset bundle for the reminder sticky bar and modal.
 */
class ReminderAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/dist';

    public $depends = [];

    public $css = ['reminder.css'];

    public $js = ['reminder.js'];

    public $jsOptions = ['position' => View::POS_END];
}
