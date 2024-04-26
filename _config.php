<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Admin\LeftAndMain;
use SheaDawson\Blocks\Controllers\BlockAdmin;

//define global path to Components' root folder
if (!defined('BLOCKS_DIR')) {
	define('BLOCKS_DIR', 'vendor/sheadawson/' . rtrim(basename(dirname(__FILE__))));
}

Config::modify()->set(LeftAndMain::class, 'extra_requirements_javascript', [BLOCKS_DIR. '/javascript/blocks-cms.js']);
Config::modify()->set(BlockAdmin::class, 'menu_icon', BLOCKS_DIR. '/images/blocks.png');
