#!/usr/bin/env php
<?php

// Configuration
if (is_file('../config.php')) {
    require_once('../config.php');
}

// Startup
require_once('startup.php');

//Load needed components
require_once('framework.php');

use TMS\Symfony\Component\Console\Application;

$app = new Application('Tamara Console', 'v1.0.0');
$app->add(new TamaraScanOrder($registry, $log));
$app->run();