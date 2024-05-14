<?php

declare (strict_types=1);
namespace TMS\Tamara\Request;

use TMS\Tamara\Exception\RequestDispatcherException;
use TMS\Tamara\HttpClient\HttpClient;
use TMS\Tamara\Response\ClientResponse;
class RequestDispatcher
{
    /**
     * @var HttpClient
     */
    private $httpClient;
    public function __construct(\TMS\Tamara\HttpClient\HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    /**
     * @param object $request
     *
     * @return mixed
     *
     * @throws RequestDispatcherException
     */
    public function dispatch($request)
    {
        $requestClass = \get_class($request);
        $handlerClass = $requestClass . 'Handler';
        if (!\class_exists($handlerClass)) {
            throw new \TMS\Tamara\Exception\RequestDispatcherException(\sprintf('Missing handler for this request, please add %s', $handlerClass));
        }
        $handler = new $handlerClass($this->httpClient);
        try {
            $response = $handler($request);
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        }
        if (!$response instanceof \TMS\Tamara\Response\ClientResponse) {
            throw new \TMS\Tamara\Exception\RequestDispatcherException(\sprintf('The response of the %s::__invoke must be type of %s', $handlerClass, \TMS\Tamara\Response\ClientResponse::class));
        }
        if ($response->getStatusCode() == 401) {
            //disable Tamara payment
            $this->getOcDbInstance()->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '0' WHERE `code` = 'tamarapay' AND `key` = 'tamarapay_status'");
        }
        return $response;
    }

    public function getOcConfig() {
        global $ocConfig;
        if ($ocConfig instanceof \Config) {
            return $ocConfig;
        }
        $ocConfig = new \Config();
        $ocConfig->set('config_store_id', 0);
        return $ocConfig;
    }

    public function getOcDbInstance() {
        global $ocDb;
        if ($ocDb instanceof \DB) {
            return $ocDb;
        }
        $ocDb = new \DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        return $ocDb;
    }

    /**
     * @param bool $val
     * @param int $lifeTime
     */
    public function setDisableTamara($val, $lifeTime = 900) {
        $data = ['cached_time' => time(), 'life_time' => $lifeTime, 'value' => $val];
        $this->saveTamaraConfig('disable_tamara', json_encode($data));
    }

    public function saveTamaraConfig($key, $value) {
        $query = "SELECT * FROM `" . DB_PREFIX . "tamara_config` WHERE `key`='{$key}' LIMIT 1";
        $result = $this->getOcDbInstance()->query($query);
        if ($result->num_rows > 0) {
            $this->getOcDbInstance()->query("UPDATE `" . DB_PREFIX . "tamara_config` SET `value` = '{$value}' WHERE `key` = '{$key}'");
        } else {
            $this->getOcDbInstance()->query("INSERT INTO `" . DB_PREFIX . "tamara_config`(id, `key`, value, created_at, updated_at) VALUES(NULL, '{$key}', '{$value}', NOW(), NOW())");
        }
    }
}
