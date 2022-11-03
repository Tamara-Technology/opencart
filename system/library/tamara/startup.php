<?php
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Modification Override
function modification($filename) {
    if (!defined('DIR_CATALOG')) {
        $file = DIR_MODIFICATION . 'catalog/' . substr($filename, strlen(DIR_APPLICATION));
    } else {
        $file = DIR_MODIFICATION . 'admin/' .  substr($filename, strlen(DIR_APPLICATION));
    }

    if (substr($filename, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
        $file = DIR_MODIFICATION . 'system/' . substr($filename, strlen(DIR_SYSTEM));
    }

    if (file_exists($file)) {
        return $file;
    } else {
        return $filename;
    }
}

// Autoloader
function autoload($class) {
    $file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';

    if (file_exists($file)) {
        include(modification($file));

        return true;
    } else {
        return false;
    }
}

spl_autoload_register('autoload');
spl_autoload_extensions('.php');

if (is_file(DIR_SYSTEM . 'library/tamara/vendor/autoload.php')) {
    require_once(DIR_SYSTEM . 'library/tamara/vendor/autoload.php');
}

// Engine
require_once(modification(DIR_SYSTEM . 'engine/action.php'));
require_once(modification(DIR_SYSTEM . 'engine/controller.php'));
require_once(modification(DIR_SYSTEM . 'engine/event.php'));
require_once(modification(DIR_SYSTEM . 'engine/front.php'));
require_once(modification(DIR_SYSTEM . 'engine/loader.php'));
require_once(modification(DIR_SYSTEM . 'engine/model.php'));
require_once(modification(DIR_SYSTEM . 'engine/registry.php'));

// Helper
require_once(DIR_SYSTEM . 'helper/json.php');
require_once(DIR_SYSTEM . 'helper/utf8.php');