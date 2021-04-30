<?php

use Symfony\Component\Console\Input\InputOption;

class TamaraScanOrder extends TamaraAbstractCommand
{
    const START_TIME = 'start-time';
    const END_TIME = 'end-time';

    protected function configure()
    {
        $this->setName("tamara:orders-scan");
        $this->setDescription("Update status of orders that pay with Tamara");
        $this->addOption(
            self::START_TIME,
            null,
            InputOption::VALUE_REQUIRED,
            'Start time to scan'
        );
        $this->addOption(
            self::END_TIME,
            null,
            InputOption::VALUE_OPTIONAL,
            'End time to scan'
        );
        parent::configure();
    }

    protected function process()
    {
        $this->log(["Run scan orders from console"]);
        $this->load->model('extension/payment/tamarapay');
        try {
            $this->load->model('extension/payment/tamarapay_console_scan');

            if ($this->console->getInput()->getOption(self::END_TIME)) {
                $this->model_extension_payment_tamarapay_console_scan->scan($this->console->getInput()->getOption(self::START_TIME),
                    $this->console->getInput()->getOption(self::END_TIME));
            } else {
                $this->model_extension_payment_tamarapay_console_scan->scan($this->console->getInput()->getOption(self::START_TIME));
            }
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }
    }
}