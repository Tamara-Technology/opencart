#!/usr/bin/env php
<?php

use TMS\Symfony\Component\Console\Command\Command as SymfonyCommand;
use TMS\Symfony\Component\Console\Input\InputInterface;
use TMS\Symfony\Component\Console\Output\OutputInterface;

abstract class TamaraAbstractCommand extends SymfonyCommand
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Log
     */
    protected $log;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct($registry, $log, string $name = null)
    {
        $this->registry = $registry;
        $this->log = $log;
        $this->loadSettingFromDb();
        parent::__construct($name);
    }

    public function loadSettingFromDb()
    {
        // Settings
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");

        foreach ($query->rows as $setting) {
            if (!$setting['serialized']) {
                $this->config->set($setting['key'], $setting['value']);
            } else {
                $this->config->set($setting['key'], json_decode($setting['value'], true));
            }
        }

        // Language
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($this->config->get('config_admin_language')) . "'");

        if ($query->num_rows) {
            $this->config->set('config_language_id', $query->row['language_id']);
        }

        // Language
        $language = new Language($this->config->get('config_admin_language'));
        $language->load($this->config->get('config_admin_language'));
        $this->registry->set('language', $language);
    }

    public function log($data, $class_step = 6, $function_step = 6)
    {
        if ($this->config->get('payment_tamarapay_debug')) {
            if ($this->output) {
                $consoleMessage = "";
                if (is_string($data)) {
                    $consoleMessage = $data;
                } else {
                    if (is_array($data) && is_string($data[0])) {
                        $consoleMessage = $data[0];
                    }
                }
                if (!empty($consoleMessage)) {
                    $this->output->writeln($consoleMessage);
                }
            }

            $backtrace = debug_backtrace();
            $this->log->write('(' . $backtrace[$class_step]['class'] . '::' . $backtrace[$function_step]['function'] . ') - ' . print_r($data,
                    true));
        }
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepare($input, $output);
        $this->process();
        return 1;
    }

    protected function prepare(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->registry->set('console', new TamaraConsoleInputOutput($input, $output));
    }

    protected function process()
    {
    }
}