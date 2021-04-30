<?php
// Registry
$registry = new Registry();

// Config
$application_config = "catalog";
$config = new Config();
$config->load('default');
$config->load($application_config);
$registry->set('config', $config);

// Log
$log = new Log("tamarapay.log");
$registry->set('log', $log);

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Event Register
if ($config->has('action_event')) {
    foreach ($config->get('action_event') as $key => $value) {
        $event->register($key, new Action($value));
    }
}

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Request
$registry->set('request', new Request());

// Database
if ($config->get('db_autostart')) {
    $registry->set('db', new DB($config->get('db_type'), $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port')));
}

// Session
$session = new Session();


if ($config->get('session_autostart')) {
    $session->start();
}

$registry->set('session', $session);

// Cache
$registry->set('cache', new Cache($config->get('cache_type'), $config->get('cache_expire')));

// Url
if ($config->get('url_autostart')) {
    $registry->set('url', new Url($config->get('site_base'), $config->get('site_ssl')));
}

// Language
$language = new Language($config->get('language_default'));
$language->load($config->get('language_default'));
$registry->set('language', $language);

// Document
$registry->set('document', new Document());

// Config Autoload
if ($config->has('config_autoload')) {
    foreach ($config->get('config_autoload') as $value) {
        $loader->config($value);
    }
}

// Language Autoload
if ($config->has('language_autoload')) {
    foreach ($config->get('language_autoload') as $value) {
        $loader->language($value);
    }
}

// Library Autoload
if ($config->has('library_autoload')) {
    foreach ($config->get('library_autoload') as $value) {
        $loader->library($value);
    }
}

// Model Autoload
if ($config->has('model_autoload')) {
    foreach ($config->get('model_autoload') as $value) {
        $loader->model($value);
    }
}

require_once 'load-class.php';
