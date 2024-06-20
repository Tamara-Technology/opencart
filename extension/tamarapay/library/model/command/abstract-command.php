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
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' OR store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY store_id ASC");

        foreach ($query->rows as $result) {
            if (!$result['serialized']) {
                $this->config->set($result['key'], $result['value']);
            } else {
                $this->config->set($result['key'], json_decode($result['value'], true));
            }
        }
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
            if (!empty($backtrace[$class_step]['class']) && !empty($backtrace[$function_step]['function'])) {
                $this->log->write('(' . $backtrace[$class_step]['class'] . '::' . $backtrace[$function_step]['function'] . ') - ' . print_r($data,
                        true));
            }
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