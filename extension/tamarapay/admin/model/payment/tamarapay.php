<?php

namespace Opencart\Admin\Model\Extension\Tamarapay\Payment;

use TMS\Tamara\Request\Webhook\RegisterWebhookRequest;
use TMS\Tamara\Client;
use TMS\Tamara\Configuration;

/**
 * Class Map
 *
 * @package Opencart\Admin\Controller\Extension\Tamarapay\Payment
 */
class Tamarapay extends \Opencart\System\Engine\Model 
{
    /**
     * Define version of extension
     */
    public const VERSION = '1.8.9';

    /**
     * Define schema version
     */
    public const SCHEMA_VERSION = '1.0.0';

    private const TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE = 'tamara_order_status_change';
    private const TAMARA_EVENT_ADD_PROMO_WIDGET_PRODUCT = 'tamara_promo_wg_product';
    private const TAMARA_EVENT_ADD_PROMO_WIDGET_CART = 'tamara_promo_wg_cart';
    private const TAMARA_EVENT_ADD_TELEPHONE_TO_SHIPPING_ADDRESS = 'tamara_add_telephone_to_shipping_address';
    private const TAMARA_EVENT_ADD_CSS_TO_PAYMENT_METHOD = 'tamara_add_css_to_payment_method';

    // const WEBHOOK_URL = 'index.php?route=extension/tamarapay/payment/tamarapay/webhook', ALLOWED_WEBHOOKS = ['order_expired', 'order_declined'];

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
            $log = new \Opencart\System\Library\Log('tamarapay.log');
            if (!empty($backtrace[1]['class']) && !empty($backtrace[1]['function'])) {
                $log->write('(' . $backtrace[1]['class'] . '::' . $backtrace[1]['function'] . ') - ' . print_r($data,
                        true));
            }
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
        $this->saveTamaraConfig('version', '1.0.0');
//        $this->addCustomFields();
        $this->updateOpencartConfig();
        $this->addDefaultConfig();
    }

    private function addDbSchema() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_config` (
              `id` int NOT NULL AUTO_INCREMENT,
              `key` varchar(255) NOT NULL,
              `value` text NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='tamara_config';
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_orders` (
              `tamara_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Tamara_id',
              `order_id` int unsigned NOT NULL COMMENT 'Order_id',
              `tamara_order_id` varchar(255) NOT NULL COMMENT 'Tamara_order_id',
              `redirect_url` varchar(255) DEFAULT NULL COMMENT 'Redirect_url',
              `is_authorised` smallint NOT NULL DEFAULT '0' COMMENT 'Is_authorised',
              `is_active` tinyint(1) DEFAULT '0' COMMENT 'is active',
              `captured_from_console` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Captured from console',
              `canceled_from_console` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Canceled from console',
              `reference_id` varchar(255) DEFAULT NULL COMMENT 'order reference id',
              `payment_type` varchar(255) DEFAULT NULL COMMENT 'order reference id',
              `number_of_installments` int unsigned DEFAULT NULL COMMENT 'number of installments',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`tamara_id`),
              KEY `TAMARA_ORDERS_TAMARA_ID` (`tamara_id`,`order_id`,`tamara_order_id`),
              KEY `tamara_orders_tamara_order_id` (`tamara_order_id`),
              KEY `tamara_orders_order_id` (`order_id`),
              KEY `idx_console_query` (`is_authorised`,`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='tamara_orders';
		");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_captures` (
              `capture_id` varchar(255) NOT NULL COMMENT 'Capture_id',
              `order_id` int unsigned NOT NULL COMMENT 'Order_id',
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='tamara_captures';
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_capture_items` (
              `order_item_id` int unsigned NOT NULL COMMENT 'Order_item_id',
              `order_id` int unsigned NOT NULL COMMENT 'Order_id',
              `capture_id` varchar(255) NOT NULL COMMENT 'Capture_id',
              `product_id` int DEFAULT NULL COMMENT 'product id',
              `name` varchar(255) NOT NULL COMMENT 'Name',
              `image_url` varchar(255) DEFAULT NULL COMMENT 'store image url of item',
              `sku` varchar(255) NOT NULL COMMENT 'Sku',
              `type` varchar(255) NOT NULL COMMENT 'Type',
              `quantity` int unsigned NOT NULL COMMENT 'Quantity',
              `unit_price` decimal(20,4) DEFAULT '0.0000' COMMENT 'Unit price',
              `total_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Total item amount',
              `tax_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Tax amount',
              `discount_amount` decimal(20,4) DEFAULT '0.0000' COMMENT 'Discount amount',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`order_item_id`,`order_id`,`capture_id`),
              KEY `TAMARA_CAPTURE_ITEMS_ORDER_ITEM_ID` (`order_item_id`,`order_id`,`capture_id`),
              KEY `tamara_capture_items_order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='tamara_capture_items';
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tamara_cancels` (
              `cancel_id` varchar(255) NOT NULL COMMENT 'Cancel_id',
              `order_id` int unsigned NOT NULL COMMENT 'Order_id',
              `tamara_order_id` varchar(255) NOT NULL COMMENT 'Tamara_order_id',
              `request` text COMMENT 'Request',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
              PRIMARY KEY (`cancel_id`,`order_id`,`tamara_order_id`),
              KEY `TAMARA_CANCELS_CAPTURE_ID` (`tamara_order_id`,`order_id`,`cancel_id`),
              KEY `tamara_cancels_order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='tamara_cancels';
        ");
    }

    public function addEvents() {
        
        $this->load->model('setting/event');

        // event add telephone field to checkout page
//        $event = [
//            'code' => self::TAMARA_EVENT_ADD_TELEPHONE_TO_SHIPPING_ADDRESS,
//            'description' => 'Add telephone to shipping address',
//            'trigger' => 'catalog/model/account/custom_field/getCustomFields/after',
//            'action' => 'extension/tamarapay/event/tamarapay.checkTelephoneForTamaraPayment',
//            'sort_order' => 999,
//            'status' => true
//        ];
//        $this->model_setting_event->addEvent($event);


        // event add widget to product page 
        $event = [
            'code' => self::TAMARA_EVENT_ADD_PROMO_WIDGET_PRODUCT,
            'description' => 'Add widget to product page',
            'trigger' => 'catalog/view/product/product/after',
            'action' => 'extension/tamarapay/event/tamarapay.addPromoWidgetForProduct',
            'sort_order' => 999,
            'status' => true
        ];
        $this->model_setting_event->addEvent($event);

        // event add widget to cart page 
        $event = [
            'code' => self::TAMARA_EVENT_ADD_PROMO_WIDGET_CART,
            'description' => 'Add widget to cart page',
            'trigger' => 'catalog/view/checkout/cart/after',
            'action' => 'extension/tamarapay/event/tamarapay.addPromoWidgetForCartPage',
            'sort_order' => 999,
            'status' => true
        ];
        $this->model_setting_event->addEvent($event);

        // add event order status change code
        $event = [
            'code' => self::TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE,
            'description' => 'Add event order status change code',
            'trigger' => 'catalog/model/checkout/order/addHistory/after',
            'action' => 'extension/tamarapay/event/tamarapay.handleOrderStatusChange',
            'sort_order' => 999,
            'status' => true
        ];
        $this->model_setting_event->addEvent($event);

        // add event add css to payment method label
        $event = [
            'code' => self::TAMARA_EVENT_ADD_CSS_TO_PAYMENT_METHOD,
            'description' => 'Add css to label tamara in payment method',
            'trigger' => 'catalog/view/checkout/checkout/after',
            'action' => 'extension/tamarapay/event/tamarapay.addCssToPaymentMethod',
            'sort_order' => 999,
            'status' => true
        ];
        $this->model_setting_event->addEvent($event);

    }

    public function addDefaultConfig() {
        $data = [
            ['key' => 'payment_tamarapay_trigger_actions_enabled', 'value' => '1'],
            ['key' => 'payment_tamarapay_api_environment', 'value' => '1']
        ];

        //get order status id by language
        $sql = "SELECT oos.order_status_id, oos.name, oos.language_id, ol.code FROM " . DB_PREFIX . "order_status oos INNER JOIN " . DB_PREFIX . "language ol ON oos.language_id = ol.language_id WHERE ol.code = 'en-gb';";
        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if ($row['name'] == "Pending") {
                    $data[] = [
                        'key' => 'payment_tamarapay_order_status_success_id',
                        'value' => $row['order_status_id']
                    ];
                }
                if ($row['name'] == "Canceled") {
                    $data[] = [
                        'key' => 'payment_tamarapay_order_status_canceled_id',
                        'value' => $row['order_status_id']
                    ];
                    $data[] = [
                        'key' => 'payment_tamarapay_cancel_order_status_id',
                        'value' => $row['order_status_id']
                    ];
                }
                if ($row['name'] == "Failed") {
                    $data[] = [
                        'key' => 'payment_tamarapay_order_status_failure_id',
                        'value' => $row['order_status_id']
                    ];
                }
                if ($row['name'] == "Processing") {
                    $data[] = [
                        'key' => 'payment_tamarapay_order_status_authorised_id',
                        'value' => $row['order_status_id']
                    ];
                }
                if ($row['name'] == "Shipped") {
                    $data[] = [
                        'key' => 'payment_tamarapay_capture_order_status_id',
                        'value' => $row['order_status_id']
                    ];
                }
            }
        }
        $sql = "";
        foreach ($data as $row) {
            if (empty($sql)) {
                $sql .= "INSERT INTO `". DB_PREFIX ."setting` (setting_id, store_id, code, `key`, value, serialized) VALUES(NULL, 0, 'payment_tamarapay', '". $row['key'] ."', '". $row['value'] ."', 0)";
            } else {
                $sql .= ", (NULL, 0, 'payment_tamarapay', '". $row['key'] ."', '". $row['value'] ."', 0)";
            }
        }
        $this->db->query($sql);
    }



    public function addCustomFields() {

        // add custom field telephone
        $this->addCustomFieldTelephone();
    }

    public function updateOpencartConfig() {
        $this->updateTelephoneConfig();
        $this->updateCookieSameSite();
        $this->requireBillingAddress();
    }

    public function updateTelephoneConfig() {
        $this->db->query("UPDATE `". DB_PREFIX ."setting` SET `value`='1' WHERE `key` = 'config_telephone_display' OR `key` = 'config_telephone_required'");
    }

    public function updateCookieSameSite() {
        $value = $this->config->get("config_session_samesite");
        if ($value == "Strict") {
            $this->db->query("UPDATE `". DB_PREFIX ."setting` SET `value`='Lax' WHERE `key` = 'config_session_samesite'");
        }
    }

    public function requireBillingAddress() {
        $this->db->query("UPDATE `". DB_PREFIX ."setting` SET `value`='1' WHERE `key` = 'config_checkout_payment_address'");
    }

    protected function addCustomFieldTelephone() {
        $configData = ['exists_custom_field_disabled_ids' => [], 'custom_field_id' => 0];

        //check if the telephone exists
        $arabicLanguageId = false;
        $englishLanguageIds = [];
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        foreach ($languages as $language) {
            if (str_starts_with($language['code'], 'en')) {
                $englishLanguageIds[] = $language['language_id'];
            }
            if (str_starts_with($language['code'], 'ar')) {
                $arabicLanguageId = $language['language_id'];
            }
        }

        $isExistCustomField = false;
        $existsCustomFieldDisabledIds = [];

        //query for English
        $sql = "SELECT ocfd.custom_field_id, ocfd.language_id, ocf.status FROM ". DB_PREFIX ."custom_field_description ocfd INNER JOIN ". DB_PREFIX ."custom_field ocf ON ocfd.custom_field_id = ocf.custom_field_id WHERE ocf.`type` = 'text' AND ocf.location = 'address' AND ocfd.name = 'Telephone';";
        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $isExistCustomField = true;
                if (empty($row['status'])) {
                    $existsCustomFieldDisabledIds[] = $row['custom_field_id'];
                }
            }
        }
        //query for Arabic
        if ($arabicLanguageId) {
            $sql = "SELECT ocfd.custom_field_id, ocfd.language_id, ocf.status FROM ". DB_PREFIX ."custom_field_description ocfd INNER JOIN ". DB_PREFIX ."custom_field ocf ON ocfd.custom_field_id = ocf.custom_field_id WHERE ocf.`type` = 'text' AND ocf.location = 'address' AND ocfd.name = 'هاتف';";
            $query = $this->db->query($sql);
            if ($query->num_rows) {
                $isExistCustomField = true;
                foreach ($query->rows as $row) {
                    if (empty($row['status'])) {
                        $existsCustomFieldDisabledIds[] = $row['custom_field_id'];
                    }
                }
            }
        }

        if ($isExistCustomField) {
            if (!empty($existsCustomFieldDisabledIds)) {
                $existsCustomFieldDisabledIds = array_unique($existsCustomFieldDisabledIds);

                //Enable custom fields
                $sql = "UPDATE `" . DB_PREFIX . "custom_field` SET `status` = '1' WHERE `custom_field_id` IN (". implode(",", $existsCustomFieldDisabledIds). ");";
                $this->db->query($sql);
                $configData['exists_custom_field_disabled_ids'] = $existsCustomFieldDisabledIds;
            }
        } else {
            $this->load->model('customer/custom_field');
            $customFieldDescriptions = [];
            foreach ($englishLanguageIds as $languageId) {
                $customFieldDescriptions[$languageId] = ['name' => 'Telephone'];
            }
            if ($arabicLanguageId) {
                $customFieldDescriptions[$arabicLanguageId] = ['name' => 'هاتف'];
            }
            $data = array (
                'custom_field_description' => $customFieldDescriptions,
                'location' => 'address',
                'type' => 'text',
                'value' => '',
                'validation' => '',
                'custom_field_customer_group' =>
                    array (
                        0 =>
                            array (
                                'customer_group_id' => '1',
                                'required' => '1',
                            ),
                    ),
                'status' => '1',
                'sort_order' => '999',
                'custom_field_id' => '0',
            );
            $customFieldId = $this->model_customer_custom_field->addCustomField($data);
            $configData['custom_field_id'] = $customFieldId;
        }
        $this->saveTamaraConfig('custom_field_telephone', \json_encode($configData));
    }

    public function deleteCustomFields() {
        $this->deleteCustomFieldTelephone();
    }

    protected function deleteCustomFieldTelephone() {
        $customFieldData = $this->getTamaraConfigValue('custom_field_telephone');
        if ($customFieldData !== null) {
            $customFieldData = \json_decode($customFieldData, true);
            if (!empty($customFieldData['custom_field_id'])) {
                $this->load->model('customer/custom_field');
                $this->model_customer_custom_field->deleteCustomField($customFieldData['custom_field_id']);
            } else {
                if (!empty($customFieldData['exists_custom_field_disabled_ids'])) {
                    //disable custom fields
                    $sql = "UPDATE `" . DB_PREFIX . "custom_field` SET `status` = '0' WHERE `custom_field_id` IN (". implode(",", $customFieldData['exists_custom_field_disabled_ids']). ");";
                    $this->db->query($sql);
                }
            }
        }
    }

    public function uninstall()
    {
//        $this->deleteCustomFields();
//        $this->deleteTables();
        $this->removeEvents();
    }

    private function deleteTables() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_config`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_orders`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_captures`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_capture_items`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tamara_cancels`;");
    }

    private function removeEvents() {

        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode(self::TAMARA_EVENT_ADD_TELEPHONE_TO_SHIPPING_ADDRESS);
        $this->model_setting_event->deleteEventByCode(self::TAMARA_EVENT_ORDER_STATUS_CHANGE_CODE);
        $this->model_setting_event->deleteEventByCode(self::TAMARA_EVENT_ADD_PROMO_WIDGET_PRODUCT);
        $this->model_setting_event->deleteEventByCode(self::TAMARA_EVENT_ADD_PROMO_WIDGET_CART);
        $this->model_setting_event->deleteEventByCode(self::TAMARA_EVENT_ADD_CSS_TO_PAYMENT_METHOD);
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

