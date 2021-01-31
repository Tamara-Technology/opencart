<?php

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Tamara\Client;
use Tamara\Configuration;

class ModelExtensionPaymentTamarapay extends Model
{

    public const
        VERSION = '1.0.0',
        COUNTRY_ISO = 'SA',
        CACHE_TAMARAPAY_PAYMENT_TYPES = 'cache_tamarapay_payment_types',
        TTL = 86400;

    private const TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE = 'tamara_order_status_change';
    private const TAMARA_EVENT_ADD_PROMO_WIDGET_CODE = 'tamara_promo_wg';

    private $cacheTamarapay;

    /**
     * Get extension version
     */
    public function getExtensionVersion() {
        return self::VERSION;
    }

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->cacheTamarapay = new FilesystemAdapter('', self::TTL, DIR_CACHE);
    }

    public function getTamaraOrder($order_id)
    {
        $queryString = sprintf("SELECT * FROM `%s` WHERE `order_id` = %d LIMIT 1", DB_PREFIX . "tamara_orders",
            (int)$order_id);
        return $this->db->query($queryString)->row;
    }

    public function createClient($credentials)
    {
        $configuration = Configuration::create(
            $credentials['url'],
            $credentials['token']
        );

        return Client::create($configuration);
    }

    public function verifyCredentials($client)
    {
        try {
            $response = $client->getPaymentTypes(self::COUNTRY_ISO);
            return true;
        } catch (Exception $e) {
            $this->log($e->getMessage());

            return false;
        }
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
    }

    /**
     * Get version of extension in db
     * @return string
     */
    public function getCurrentVersionInDb() {
        $version = $this->getTamaraConfig('version');
        return $version['value'];
    }

    /**
     * Get Tamara config by key
     * @param $key
     * @return array
     */
    public function getTamaraConfig($key) {
        $query = $this->db->query("SELECT * FROM ". DB_PREFIX . "tamara_config WHERE `key`='{$key}' LIMIT 1");
        return $query->row;
    }

    private function initData() {
        $currentVersion = $this->getExtensionVersion();
        $sql = "INSERT INTO ". DB_PREFIX ."tamara_config (id, `key`, value, created_at, updated_at) VALUES(null, 'version', '{$currentVersion}', NOW(), NOW())";
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
        $this->model_setting_event->addEvent(self::TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE, 'catalog/model/checkout/order/addOrderHistory/after', 'extension/payment/tamarapay/handleOrderStatusChange');
        $this->model_setting_event->addEvent(self::TAMARA_EVENT_ADD_PROMO_WIDGET_CODE, 'catalog/view/product/product/after', 'extension/payment/tamarapay/addPromoWidgetForProduct');
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
     * Get Tamara payment types
     * @param $url
     * @param $token
     * @param bool $forceReload force reload without cache
     * @return array
     */
    public function getPaymentTypes($url, $token, $forceReload = false)
    {
        try {
            // Initialize and check cache
            $cacheItem = $this->cacheTamarapay->getItem(self::CACHE_TAMARAPAY_PAYMENT_TYPES);

            if ($forceReload || !$cacheItem->isHit()) {

                $client = Client::create(Configuration::create($url, $token));
                $paymentTypes = [];

                $response = $client->getPaymentTypes(self::COUNTRY_ISO);

                if (!$response->isSuccess()) {
                    throw new Exception($response->getMessage());
                }

                foreach ($response->getPaymentTypes() as $paymentType) {
                    $paymentTypes[] = $paymentType->toArray();
                }

                // Cache the response
                $cacheItem->set($paymentTypes);
                $this->cacheTamarapay->save($cacheItem);
            }

            return $cacheItem->get();
        } catch (Exception $exception) {
            $this->log($exception->getMessage());
            throw $exception;
        }
        return [];
    }
}