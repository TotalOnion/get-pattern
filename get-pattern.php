<?php
/**
 * Plugin Name: Get Pattern
 * Description: Child Block Creator
 * Author: Total Onion
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GET_PATTERN_PLUGIN_DIR', plugin_dir_path(__FILE__));

$patternPath = GET_PATTERN_PLUGIN_DIR . 'inc/';

$directory = new RecursiveDirectoryIterator($patternPath, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($directory);

foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        require_once $file->getRealPath();
    }
}

if (class_exists('\GetPattern\AjaxHandler')) {
    \GetPattern\AjaxHandler::init();
}
