<?php

use TMS\Tamara\Request\Webhook\RegisterWebhookRequest;
use TMS\Tamara\Client;
use TMS\Tamara\Configuration;

class ModelExtensionPaymentTamarapay extends Model
{
    /**
     * Define version of extension
     */
    public const VERSION = '1.9.1';

    /**
     * Define schema version
     */
    public const SCHEMA_VERSION = '1.8.0';

    private const TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE = 'tamara_order_status_change';
    private const TAMARA_EVENT_ADD_PROMO_WIDGET_CODE = 'tamara_promo_wg';

    const WEBHOOK_URL = 'index.php?route=extension/payment/tamarapay/webhook', ALLOWED_WEBHOOKS = ['order_expired', 'order_declined'];

    const SANDBOX_API_URL = "https://api-sandbox.tamara.co";
    const SANDBOX_API_ENVIRONMENT = "1";
    const PRODUCTION_API_URL = "https://api.tamara.co";
    const PRODUCTION_API_ENVIRONMENT = "2";
    const API_REQUEST_TIMEOUT = 30; //in seconds

    const IS_SINGLE_CHECKOUT_VERSION = false;

    /**
     * Get extension version
     */
    public function getExtensionVersion() {
        return self::VERSION;
    }

    public function getSchemaVersion() {
        return self::SCHEMA_VERSION;
    }

    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    public function getTamaraOrder($order_id)
    {
        $queryString = sprintf("SELECT * FROM `%s` WHERE `order_id` = %d LIMIT 1", DB_PREFIX . "tamara_orders",
            (int)$order_id);
        return $this->db->query($queryString)->row;
    }

    public function createClient($credentials)
    {
        if (!isset($credentials['timeout'])) {
            $credentials['timeout'] = self::API_REQUEST_TIMEOUT;
        }
        $configuration = Configuration::create(
            $credentials['url'],
            $credentials['token'],
            $credentials['timeout']
        );

        return Client::create($configuration);
    }

    public function log($data)
    {
        if ($this->config->get('payment_tamarapay_debug')) {
            $backtrace = debug_backtrace();
            $log = new Log('tamarapay.log');
            $log->write('(' . $backtrace[1]['class'] . '::' . $backtrace[1]['function'] . ') - ' . print_r($data,
                    true));
        }
    }

    public function install()
    {
        $this->addDbSchema();
        $this->addEvents();
        $this->initData();
        $this->copyTemplates();
    }

    /**
     * Get version of extension in db
     * @return string
     */
    public function getCurrentVersionInDb() {
        if (!empty($schemaVersion = $this->getTamaraConfigValue('version'))) {
            return $schemaVersion;
        }
        return self::SCHEMA_VERSION;
    }

    public function updateTamaraConfig($key, $value) {
        $query = "UPDATE `".DB_PREFIX."tamara_config` SET `value` = '{$value}', `updated_at` = NOW() WHERE `key` = '{$key}'";
        $this->db->query($query);
    }

    private function initData() {
        $sql = "INSERT INTO ". DB_PREFIX ."tamara_config (id, `key`, value, created_at, updated_at) VALUES(null, 'version', '1.0.0', NOW(), NOW())";
        $this->db->query($sql);
    }

    private function addDbSchema() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `". DB_PREFIX. "tamara_config` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `key` varchar(255) NOT NULL,
              `value` text NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tamara_config';
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_orders` (
              `tamara_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Tamara_id',
              `order_id` int(10) unsigned NOT NULL COMMENT 'Order_id',
              `tamara_order_id` varchar(255) NOT NULL COMMENT 'Tamara_order_id',
              `redirect_url` varchar(255) DEFAULT NULL COMMENT 'Redirect_url',
              `is_authorised` smallint(6) NOT NULL DEFAULT '0' COMMENT 'Is_authorised',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              `is_active` tinyint(1) DEFAULT '0' COMMENT 'is active',
              PRIMARY KEY (`tamara_id`),
              KEY `TAMARA_ORDERS_TAMARA_ID` (`tamara_id`,`order_id`,`tamara_order_id`),
              KEY `tamara_orders_tamara_order_id` (`tamara_order_id`),
              KEY `tamara_orders_order_id` (`order_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COMMENT='tamara_orders';
		");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_captures` (
              `capture_id` varchar(255) NOT NULL COMMENT 'Capture_id',
              `order_id` int(10) unsigned NOT NULL COMMENT 'Order_id',
              `tamara_order_id` varchar(255) NOT NULL COMMENT 'Tamara_order_id',
              `total_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Total amount',
              `tax_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Tax amount',
              `shipping_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Shipping amount',
              `discount_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Discount amount',
              `shipping_info` text COMMENT 'Shipping_info',
              `currency` varchar(3) DEFAULT NULL COMMENT 'Currency',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`capture_id`,`order_id`,`tamara_order_id`),
              KEY `TAMARA_CAPTURES_CAPTURE_ID` (`tamara_order_id`,`order_id`,`capture_id`),
              KEY `tamara_captures_order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tamara_captures'
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_capture_items` (
              `order_item_id` int(10) unsigned NOT NULL COMMENT 'Order_item_id',
              `order_id` int(10) unsigned NOT NULL COMMENT 'Order_id',
              `capture_id` varchar(255) NOT NULL COMMENT 'Capture_id',
              `product_id` int(11) DEFAULT NULL COMMENT 'product id',
              `name` varchar(255) NOT NULL COMMENT 'Name',
              `image_url` varchar(255) DEFAULT NULL COMMENT 'store image url of item',
              `sku` varchar(255) NOT NULL COMMENT 'Sku',
              `type` varchar(255) NOT NULL COMMENT 'Type',
              `quantity` int(10) unsigned NOT NULL COMMENT 'Quantity',
              `unit_price` decimal(20,4) DEFAULT '0.0000' COMMENT 'Unit price',
              `total_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Total item amount',
              `tax_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Tax amount',
              `discount_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Discount amount',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`order_item_id`,`order_id`,`capture_id`),
              KEY `TAMARA_CAPTURE_ITEMS_ORDER_ITEM_ID` (`order_item_id`,`order_id`,`capture_id`),
              KEY `tamara_capture_items_order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tamara_capture_items';
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_cancels` (
              `cancel_id` varchar(255) NOT NULL COMMENT 'Cancel_id',
              `order_id` int(10) unsigned NOT NULL COMMENT 'Order_id',
              `tamara_order_id` varchar(255) NOT NULL COMMENT 'Tamara_order_id',
              `request` text COMMENT 'Request',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`cancel_id`,`order_id`,`tamara_order_id`),
              KEY `TAMARA_CANCELS_CAPTURE_ID` (`tamara_order_id`,`order_id`,`cancel_id`),
              KEY `tamara_cancels_order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tamara_cancels'
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_customer_whitelist` (
              `whitelist_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Whitelist_id',
              `customer_email` varchar(255) NOT NULL COMMENT 'Customer_email',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`whitelist_id`,`customer_email`),
              UNIQUE KEY `TAMARA_CUSTOMER_WHITELIST_CUSTOMER_EMAIL` (`customer_email`)
            ) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='tamara_customer_whitelist';
         ");
    }

    private function addEvents() {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent(self::TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE, 'catalog/model/checkout/order/addOrderHistory/after', 'extension/payment/tamarapay/handleOrderStatusChange', 1);
        $this->model_setting_event->addEvent(self::TAMARA_EVENT_ADD_PROMO_WIDGET_CODE, 'catalog/view/product/product/after', 'extension/payment/tamarapay/addPromoWidgetForProduct', 1, 999);
    }

    public function uninstall()
    {
        $this->deleteTables();
        $this->removeEvents();
    }

    private function deleteTables() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_config`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_orders`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_captures`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_capture_items`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_cancels`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_customer_whitelist`;");
    }

    private function removeEvents() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEvent(self::TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE);
        $this->model_setting_event->deleteEvent(self::TAMARA_EVENT_ADD_PROMO_WIDGET_CODE);
    }

    /**
     * @param $client \TMS\Tamara\Client
     * @return mixed
     * @throws \TMS\Tamara\Exception\RequestDispatcherException
     */
    public function getPaymentTypesOfClient($client) {
        return $client->getPaymentTypes($this->getStoreCountryCode());
    }

    public function getStoreCountry() {
        $this->load->model('localisation/country');
        return $this->model_localisation_country->getCountry($this->config->get('config_country_id'));
    }

    public function getStoreCountryCode() {
        return strtoupper($this->getStoreCountry()['iso_code_2']);
    }

    public function getCatalogBaseUrl()
    {
        return HTTPS_CATALOG;
    }

    /**
     * Register webhook
     * @param $url
     * @param $token
     * @return string
     * @throws \TMS\Tamara\Exception\RequestDispatcherException
     */
    public function registerWebhook($url, $token) {
        $webhookUrl = rtrim($this->getCatalogBaseUrl(),"/") . '/' . self::WEBHOOK_URL;
        $request = new RegisterWebhookRequest(
            $webhookUrl,
            self::ALLOWED_WEBHOOKS
        );
        $credentials = ['url' => $url, 'token' => $token];
        $response = $this->createClient($credentials)->registerWebhook($request);
        if (!$response->isSuccess()) {
            $errorLogs = [$response->getContent()];
            $this->log($errorLogs);
            throw new \Exception($response->getMessage());
        }
        return $response->getWebhookId();
    }

    /**
     * Remove webhook
     * @throws \TMS\Tamara\Exception\RequestDispatcherException
     */
    public function removeWebhook() {
        $webhookId = $this->config->get('payment_tamarapay_webhook_id');
        if (!empty($webhookId)) {
            $request = new \TMS\Tamara\Request\Webhook\RemoveWebhookRequest($webhookId);
            $response = $this->getTamaraClient()->removeWebhook($request);
            if (!$response->isSuccess()) {
                $errorLogs = [$response->getContent()];
                $this->log($errorLogs);
                throw new \Exception($response->getMessage());
            }
            $this->deleteConfig('payment_tamarapay_webhook_id');
        }
        return true;
    }

    public function deleteConfig($key, $storeId = 0) {
        $this->db->query("DELETE FROM `".DB_PREFIX."setting` WHERE `code`='payment_tamarapay' AND `key` = '{$key}' AND store_id = '{$storeId}'");
    }

    public function saveConfig($key, $value, $serialized = false, $storeId = 0) {
        if (!$serialized) {
            $serialized = 0;
        } else {
            $serialized = 1;
        }
        $query ="SELECT `setting_id` FROM `" . DB_PREFIX . "setting` WHERE `code`='payment_tamarapay' AND `key`='{$key}' AND `store_id`='$storeId' LIMIT 1";
        $result = $this->db->query($query);
        if ($result->num_rows > 0) {
            $settingId = $result->row['setting_id'];
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '{$value}' WHERE `setting_id` = '{$settingId}'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting`(`setting_id`,`store_id`,`code`,`key`,`value`, `serialized`) VALUES (null,'{$storeId}','payment_tamarapay','{$key}','{$value}', '{$serialized}')");
        }
    }

    public function getTamaraClient() {
        $url = $this->getApiUrl();
        $token = $this->config->get('payment_tamarapay_token');
        return $this->createClient(['url' => $url, 'token' => $token]);
    }

    public function getProductionApiUrl() {
        return self::PRODUCTION_API_URL;
    }

    public function getProductionApiEnvironment() {
        return self::PRODUCTION_API_ENVIRONMENT;
    }

    public function getSandboxApiUrl() {
        return self::SANDBOX_API_URL;
    }

    public function getSandboxApiEnvironment() {
        return self::SANDBOX_API_ENVIRONMENT;
    }

    public function getApiUrl($environment = null) {
        if ($environment !== null && $environment != self::PRODUCTION_API_ENVIRONMENT && $environment != self::SANDBOX_API_ENVIRONMENT) {
            throw new \Exception("API environment incorrect!");
        }
        if ($environment === null) {
            $environment = $this->config->get('payment_tamarapay_api_environment');
        }
        if ($environment == self::PRODUCTION_API_ENVIRONMENT) {
            return $this->getProductionApiUrl();
        } else {
            return $this->getSandboxApiUrl();
        }
    }

    public function removeTamaraCache() {
        $this->db->query("UPDATE `" . DB_PREFIX . "tamara_config` SET `value` = '' WHERE `key`='payment_types'");
        $this->db->query("UPDATE `" . DB_PREFIX . "tamara_config` SET `value` = '' WHERE `key`='single_checkout_enabled'");
    }

    /**
     * @param $key
     * @return array|null
     */
    public function getTamaraConfig($key) {
        $query = "SELECT * FROM `" . DB_PREFIX . "tamara_config` WHERE `key`='{$key}' LIMIT 1";
        $result = $this->db->query($query);
        if ($result->num_rows > 0) {
            return $result->row;
        }
        return null;
    }

    /**
     * @param $key
     * @return string|null
     */
    public function getTamaraConfigValue($key) {
        $data = $this->getTamaraConfig($key);
        if ($data !== null) {
            return $data['value'];
        }
        return null;
    }

    /**
     * @param $key
     * @return array
     */
    public function getTamaraCacheConfigFromDb($key) {
        $value = $this->getTamaraConfigValue($key);
        if (!empty($value)) {
            return json_decode($value, true);
        }
        return [];
    }

    public function saveTamaraConfig($key, $value) {
        $query = "SELECT * FROM `" . DB_PREFIX . "tamara_config` WHERE `key`='{$key}' LIMIT 1";
        $result = $this->db->query($query);
        if ($result->num_rows > 0) {
            $this->db->query("UPDATE `" . DB_PREFIX . "tamara_config` SET `value` = '{$value}' WHERE `key` = '{$key}'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "tamara_config`(id, `key`, value, created_at, updated_at) VALUES(NULL, '{$key}', '{$value}', NOW(), NOW())");
        }
    }

    public function isSingleCheckoutVersion() {
        return self::IS_SINGLE_CHECKOUT_VERSION;
    }

    public function endsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    private function copyTemplates() {
        $themeDir = sprintf("%sview%stheme%s", DIR_CATALOG, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
        $themes = glob($themeDir . '*' , GLOB_ONLYDIR);
        $defaultThemePaymentDir = $themeDir . sprintf("default%stemplate%sextension%spayment%s", DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
        $needToCopies = ["tamarapay.twig", "tamarapay_success.twig"];
        foreach ($themes as $theme) {
            if ($this->endsWith($theme, "default")) {
                continue;
            }
            $path = $theme . sprintf("%stemplate%sextension%spayment%s", DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            foreach ($needToCopies as $file) {
                $source = $defaultThemePaymentDir . $file;
                $dest = $path . $file;
                copy($source, $dest);
            }
        }
    }
}