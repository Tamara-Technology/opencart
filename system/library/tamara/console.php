#!/usr/bin/env php
<?php

define('DIR_TAMARA_LIBRARY', dirname(__FILE__). DIRECTORY_SEPARATOR);

// Configuration
$configFile = DIR_TAMARA_LIBRARY  .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR. 'config.php';
if (is_file($configFile)) {
    require_once($configFile);
}

// Startup
require_once(DIR_SYSTEM. 'startup.php');

//Load needed components
require_once(DIR_TAMARA_LIBRARY. 'framework.php');

use TMS\Symfony\Component\Console\Application;

$app = new Application('Tamara Console', 'v1.0.0');
$app->add(new TamaraScanOrder($registry, $log));
$app->run();