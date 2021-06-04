<?php
class ControllerExtensionPaymentTamarapay extends Controller {
    private $error = array();
    private $contextSchemaVersion;

    private function getSchemaVersion() {
        $this->load->model('extension/payment/tamarapay');
        return $this->model_extension_payment_tamarapay->getSchemaVersion();
    }

    private function getCurrentDbVersion() {
        $this->load->model('extension/payment/tamarapay');
        return $this->model_extension_payment_tamarapay->getCurrentVersionInDb();
    }

    private function processUpgrade() {
        $this->contextSchemaVersion = $this->getCurrentDbVersion();
        return $this->upgradeData();
    }

    public function index() {
        if (!$this->isVendorAutoloadExist()) {
            $this->addVendorAutoload();
        }
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('localisation/order_status');
        $this->load->model('extension/payment/tamarapay');
        $this->processUpgrade();

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if ('POST' === ($this->request->server['REQUEST_METHOD']) && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_tamarapay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['extension_version'] = $this->model_extension_payment_tamarapay->getExtensionVersion();
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_url'] = $this->error['url'] ?? '';
        $data['error_token'] = $this->error['token'] ?? '';
        $data['error_token_notification'] = $this->error['token_notification'] ?? '';
        $data['error_merchant_success_url'] = $this->error['merchant_success_url'] ?? '';
        $data['error_merchant_failure_url'] = $this->error['merchant_failure_url'] ?? '';
        $data['error_merchant_cancel_url'] = $this->error['merchant_cancel_url'] ?? '';
        $data['error_merchant_notification_url'] = $this->error['merchant_notification_url'] ?? '';

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/tamarapay', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/payment/tamarapay', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_tamarapay_api_environment'])) {
            $data['payment_tamarapay_api_environment'] = $this->request->post['payment_tamarapay_api_environment'];
        } else {
            $data['payment_tamarapay_api_environment'] = $this->config->get('payment_tamarapay_api_environment');
        }

        if (isset($this->request->post['payment_tamarapay_token_notification'])) {
            $data['payment_tamarapay_token_notification'] = $this->request->post['payment_tamarapay_token_notification'];
        } else {
            $data['payment_tamarapay_token_notification'] = $this->config->get('payment_tamarapay_token_notification');
        }

        if (isset($this->request->post['payment_tamarapay_token'])) {
            $data['payment_tamarapay_token'] = $this->request->post['payment_tamarapay_token'];
        } else {
            $data['payment_tamarapay_token'] = $this->config->get('payment_tamarapay_token');
        }

        if (isset($this->request->post['payment_tamarapay_merchant_success_url'])) {
            $data['payment_tamarapay_merchant_success_url'] = $this->request->post['payment_tamarapay_merchant_success_url'];
        } else {
            $data['payment_tamarapay_merchant_success_url'] = $this->config->get('payment_tamarapay_merchant_success_url');
        }

        if (isset($this->request->post['payment_tamarapay_merchant_failure_url'])) {
            $data['payment_tamarapay_merchant_failure_url'] = $this->request->post['payment_tamarapay_merchant_failure_url'];
        } else {
            $data['payment_tamarapay_merchant_failure_url'] = $this->config->get('payment_tamarapay_merchant_failure_url');
        }

        if (isset($this->request->post['payment_tamarapay_merchant_cancel_url'])) {
            $data['payment_tamarapay_merchant_cancel_url'] = $this->request->post['payment_tamarapay_merchant_cancel_url'];
        } else {
            $data['payment_tamarapay_merchant_cancel_url'] = $this->config->get('payment_tamarapay_merchant_cancel_url');
        }

        if (isset($this->request->post['payment_tamarapay_merchant_notification_url'])) {
            $data['payment_tamarapay_merchant_notification_url'] = $this->request->post['payment_tamarapay_merchant_notification_url'];
        } else {
            $data['payment_tamarapay_merchant_notification_url'] = $this->config->get('payment_tamarapay_merchant_notification_url');
        }

        if (isset($this->request->post['payment_tamarapay_debug'])) {
            $data['payment_tamarapay_debug'] = $this->request->post['payment_tamarapay_debug'];
        } else {
            $data['payment_tamarapay_debug'] = $this->config->get('payment_tamarapay_debug');
        }

        if (isset($this->request->post['payment_tamarapay_trigger_actions_enabled'])) {
            $data['payment_tamarapay_trigger_actions_enabled'] = $this->request->post['payment_tamarapay_trigger_actions_enabled'];
        } else {
            $data['payment_tamarapay_trigger_actions_enabled'] = $this->config->get('payment_tamarapay_trigger_actions_enabled');
        }

        if (isset($this->request->post['payment_tamarapay_order_status_success_id'])) {
            $data['payment_tamarapay_order_status_success_id'] = $this->request->post['payment_tamarapay_order_status_success_id'];
        } else {
            $data['payment_tamarapay_order_status_success_id'] = $this->config->get('payment_tamarapay_order_status_success_id');
        }

        if (isset($this->request->post['payment_tamarapay_order_status_failure_id'])) {
            $data['payment_tamarapay_order_status_failure_id'] = $this->request->post['payment_tamarapay_order_status_failure_id'];
        } else {
            $data['payment_tamarapay_order_status_failure_id'] = $this->config->get('payment_tamarapay_order_status_failure_id');
        }

        if (isset($this->request->post['payment_tamarapay_order_status_canceled_id'])) {
            $data['payment_tamarapay_order_status_canceled_id'] = $this->request->post['payment_tamarapay_order_status_canceled_id'];
        } else {
            $data['payment_tamarapay_order_status_canceled_id'] = $this->config->get('payment_tamarapay_order_status_canceled_id');
        }

        if (isset($this->request->post['payment_tamarapay_order_status_authorised_id'])) {
            $data['payment_tamarapay_order_status_authorised_id'] = $this->request->post['payment_tamarapay_order_status_authorised_id'];
        } else {
            $data['payment_tamarapay_order_status_authorised_id'] = $this->config->get('payment_tamarapay_order_status_authorised_id');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_later_enabled'])) {
            $data['payment_tamarapay_types_pay_by_later_enabled'] = $this->request->post['payment_tamarapay_types_pay_by_later_enabled'];
        } else {
            $data['payment_tamarapay_types_pay_by_later_enabled'] = $this->config->get('payment_tamarapay_types_pay_by_later_enabled');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_later_title'])) {
            $data['payment_tamarapay_types_pay_by_later_title'] = $this->request->post['payment_tamarapay_types_pay_by_later_title'];
        } else {
            $data['payment_tamarapay_types_pay_by_later_title'] = $this->config->get('payment_tamarapay_types_pay_by_later_title');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_instalments_enabled'])) {
            $data['payment_tamarapay_types_pay_by_instalments_enabled'] = $this->request->post['payment_tamarapay_types_pay_by_instalments_enabled'];
        } else {
            $data['payment_tamarapay_types_pay_by_instalments_enabled'] = $this->config->get('payment_tamarapay_types_pay_by_instalments_enabled');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_instalments_title'])) {
            $data['payment_tamarapay_types_pay_by_instalments_title'] = $this->request->post['payment_tamarapay_types_pay_by_instalments_title'];
        } else {
            $data['payment_tamarapay_types_pay_by_instalments_title'] = $this->config->get('payment_tamarapay_types_pay_by_instalments_title');
        }

        if (isset($this->request->post['payment_tamarapay_capture_order_status_id'])) {
            $data['payment_tamarapay_capture_order_status_id'] = $this->request->post['payment_tamarapay_capture_order_status_id'];
        } else {
            $data['payment_tamarapay_capture_order_status_id'] = $this->config->get('payment_tamarapay_capture_order_status_id');
        }

        if (isset($this->request->post['payment_tamarapay_cancel_order_status_id'])) {
            $data['payment_tamarapay_cancel_order_status_id'] = $this->request->post['payment_tamarapay_cancel_order_status_id'];
        } else {
            $data['payment_tamarapay_cancel_order_status_id'] = $this->config->get('payment_tamarapay_cancel_order_status_id');
        }

        if (isset($this->request->post['payment_tamarapay_enable_tamara_checkout_success_page'])) {
            $data['payment_tamarapay_enable_tamara_checkout_success_page'] = $this->request->post['payment_tamarapay_enable_tamara_checkout_success_page'];
        } else {
            $data['payment_tamarapay_enable_tamara_checkout_success_page'] = $this->config->get('payment_tamarapay_enable_tamara_checkout_success_page');
        }

        if (isset($this->request->post['payment_tamarapay_pdp_wg_exclude_product_ids'])) {
            $data['payment_tamarapay_pdp_wg_exclude_product_ids'] = $this->request->post['payment_tamarapay_pdp_wg_exclude_product_ids'];
        } else {
            $data['payment_tamarapay_pdp_wg_exclude_product_ids'] = $this->config->get('payment_tamarapay_pdp_wg_exclude_product_ids');
        }

        if (isset($this->request->post['payment_tamarapay_pdp_wg_exclude_category_ids'])) {
            $data['payment_tamarapay_pdp_wg_exclude_category_ids'] = $this->request->post['payment_tamarapay_pdp_wg_exclude_category_ids'];
        } else {
            $data['payment_tamarapay_pdp_wg_exclude_category_ids'] = $this->config->get('payment_tamarapay_pdp_wg_exclude_category_ids');
        }

        if (isset($this->request->post['payment_tamarapay_webhook_enabled'])) {
            $webHookEnabled = $this->request->post['payment_tamarapay_webhook_enabled'];
        } else {
            $webHookEnabled = $this->config->get('payment_tamarapay_webhook_enabled');
            if ($webHookEnabled === null) {
                $webHookEnabled = 1;
            }
        }
        $webHookEnabled = intval($webHookEnabled);
        $data['payment_tamarapay_webhook_enabled'] = $webHookEnabled;

        if ($webHookEnabled) {
            if (!empty($this->config->get('payment_tamarapay_webhook_id'))) {
                $data['payment_tamarapay_webhook_id'] = $this->config->get('payment_tamarapay_webhook_id');
            } else {
                $data['payment_tamarapay_webhook_id'] = $this->language->get('text_save_config_get_webhook_id');
            }
        } else {
            $data['payment_tamarapay_webhook_id'] = $this->language->get("text_none_webhook_id");
        }

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_tamarapay_geo_zone_id'])) {
            $data['payment_tamarapay_geo_zone_id'] = $this->request->post['payment_tamarapay_geo_zone_id'];
        } else {
            $data['payment_tamarapay_geo_zone_id'] = $this->config->get('payment_tamarapay_geo_zone_id');
        }

        if (isset($this->request->post['payment_tamarapay_status'])) {
            $data['payment_tamarapay_status'] = $this->request->post['payment_tamarapay_status'];
        } else {
            $data['payment_tamarapay_status'] = $this->config->get('payment_tamarapay_status');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_tamarapay_sort_order'])) {
            $data['payment_tamarapay_sort_order'] = $this->request->post['payment_tamarapay_sort_order'];
        } else {
            $data['payment_tamarapay_sort_order'] = $this->config->get('payment_tamarapay_sort_order');
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/tamarapay', $data));
    }


    protected function validate() {
        $this->load->model('extension/payment/tamarapay');

        $check_credentials = true;

        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->error['warning'] = $this->language->get('error_php_version');
        }

        if (!$this->user->hasPermission('modify', 'extension/payment/tamarapay')) {
            $this->error['warning'] = $this->language->get('error_permission');

            $check_credentials = false;
        }

        if (!$this->request->post['payment_tamarapay_token']) {
            $this->error['token'] = $this->language->get('error_token');

            $check_credentials = false;
        }

        if (!$this->request->post['payment_tamarapay_token_notification']) {
            $this->error['token_notification'] = $this->language->get('error_notification_token_required');

            $check_credentials = false;
        }

        $this->request->post['payment_tamarapay_token'] = preg_replace("/\s+/", "", $this->request->post['payment_tamarapay_token']);
        $this->request->post['payment_tamarapay_token_notification'] = preg_replace("/\s+/", "", $this->request->post['payment_tamarapay_token_notification']);

        if ($check_credentials) {
            $url = $this->model_extension_payment_tamarapay->getApiUrl($this->request->post['payment_tamarapay_api_environment']);
            $token = $this->request->post['payment_tamarapay_token'];
            if ($this->isChangedConfig('payment_tamarapay_api_environment') || $this->isChangedConfig('payment_tamarapay_token')) {
                try {
                    $this->model_extension_payment_tamarapay->getPaymentTypes($url, $token, true);
                } catch (\Exception $exception) {
                    $this->error['token'] = $this->language->get('error_token_invalid');
                }
            }
        }

        $this->validateWebhook();

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    /**
     * @return bool
     */
    private function validateWebhook() {
        if ($this->request->post['payment_tamarapay_webhook_id'] == $this->language->get('text_none_webhook_id')
            || $this->request->post['payment_tamarapay_webhook_id'] == $this->language->get('text_save_config_get_webhook_id')) {
            $this->request->post['payment_tamarapay_webhook_id'] = "";
        }
        if (!$this->isChangedConfig('payment_tamarapay_webhook_enabled')) {
            return true;
        }
        $result = true;
        $webhookEnabled = $this->request->post['payment_tamarapay_webhook_enabled'];
        $webhookId = $this->config->get("payment_tamarapay_webhook_id");
        if (!empty($webhookEnabled)) {
            if (empty($webhookId)) {
                try {
                    $webhookId = $this->model_extension_payment_tamarapay->registerWebhook($this->model_extension_payment_tamarapay->getApiUrl($this->request->post['payment_tamarapay_api_environment']),
                        $this->request->post['payment_tamarapay_token']);
                } catch (\Exception $exception) {
                    $this->model_extension_payment_tamarapay->log($exception->getMessage());
                    $webhookEnabled = 0;
                    $this->error['warning'] = $exception->getMessage();
                    $result = false;
                }
            }
        } else {
            if (!empty($webhookId)) {
                try {
                    $this->model_extension_payment_tamarapay->removeWebhook();
                } catch (\Exception $exception) {
                    $this->model_extension_payment_tamarapay->log("Error when remove webhook: " . $exception->getMessage());
                }
                $webhookId = "";
                $result = true;
            }
        }
        $this->request->post['payment_tamarapay_webhook_enabled'] = $webhookEnabled;
        $this->request->post['payment_tamarapay_webhook_id'] = $webhookId;
        return $result;
    }

    /**
     * @param $key
     * @return bool
     */
    private function isChangedConfig($key) {
        return $this->request->post[$key] != $this->config->get($key);
    }

    private function addVendorAutoload() {

        //backup file
        $backUpFilePath = DIR_SYSTEM. "startup-".date( "Ymd-His", strtotime( "now" )) .".php.bak";
        copy($this->getSystemStartupFilePath(), $backUpFilePath);
        $data = PHP_EOL . "//Add Tamara vendor autoload".PHP_EOL . "if (is_file(DIR_SYSTEM . '../tamara/vendor/autoload.php')) {require_once(DIR_SYSTEM . '../tamara/vendor/autoload.php');}";
        $fp = fopen($this->getSystemStartupFilePath(), 'a');
        fwrite($fp, $data);
    }

    private function removeVendorAutoload() {
        $contents = file_get_contents($this->getSystemStartupFilePath());
        $contents = str_replace("//Add Tamara vendor autoload", '', $contents);
        $contents = str_replace("if (is_file(DIR_SYSTEM . '../tamara/vendor/autoload.php')) {require_once(DIR_SYSTEM . '../tamara/vendor/autoload.php');}", '', $contents);
        file_put_contents($this->getSystemStartupFilePath(), $contents);
    }

    private function isVendorAutoloadExist() {
        $contents = file_get_contents($this->getSystemStartupFilePath());
        if (strpos($contents, "require_once(DIR_SYSTEM . '../tamara/vendor/autoload.php')") !== false) {
            return true;
        }
        return false;
    }

    private function getSystemStartupFilePath() {
        return DIR_SYSTEM . 'startup.php';
    }

    public function install() {
        if (!$this->isVendorAutoloadExist()) {
            $this->addVendorAutoload();
        }
        $this->load->model('extension/payment/tamarapay');

        $this->model_extension_payment_tamarapay->install();
    }

    public function uninstall() {
        if ($this->isVendorAutoloadExist()) {
            $this->removeVendorAutoload();
        }
        $this->load->model('extension/payment/tamarapay');

        $this->model_extension_payment_tamarapay->uninstall();
    }

    /**
     * Upgrade data and schema
     * Example
     *  if (version_compare($this->contextSchemaVersion, '1.1.0', '<')) {
            $query = "ALTER TABLE `".DB_PREFIX."tamara_config` ADD `email` varchar(255)";
            $this->db->query($query);
            $this->updateSchemaVersion("1.1.0");
        }
        if (version_compare($this->contextSchemaVersion, '1.2.0', '<')) {
            $query = "ALTER TABLE `".DB_PREFIX."tamara_config` ADD `email_2` varchar(255)";
            $this->db->query($query);
            $this->updateSchemaVersion("1.2.0");
        }
     *
     */
    private function upgradeData() {
        if (version_compare($this->contextSchemaVersion, $this->getSchemaVersion() , '<')) {
            //Process upgrade here
            if (version_compare($this->contextSchemaVersion, '1.1.0', '<')) {
                $this->addConsoleColumnsToTamaraOrder();
                $this->updateSchemaVersion("1.1.0");
            }
            if (version_compare($this->contextSchemaVersion, '1.2.0', '<')) {
                $this->removeWrongValueWebhookIdConfig();
                $this->updateSchemaVersion("1.2.0");
            }
            if (version_compare($this->contextSchemaVersion, '1.3.0', '<')) {
                $this->moveApiUrlConfig();
                $this->updateSchemaVersion("1.3.0");
            }
        }
        return;
    }

    private function moveApiUrlConfig() {
        $url = $this->config->get("payment_tamarapay_url");
        if ($url == $this->model_extension_payment_tamarapay->getProductionApiUrl()) {
            $apiEnvironment = $this->model_extension_payment_tamarapay->getProductionApiEnvironment();
        } else {
            $apiEnvironment = $this->model_extension_payment_tamarapay->getSandboxApiEnvironment();
        }
        $this->model_extension_payment_tamarapay->saveConfig("payment_tamarapay_api_environment", $apiEnvironment);
        $this->config->set("payment_tamarapay_api_environment", $apiEnvironment);
    }

    private function removeWrongValueWebhookIdConfig() {
        $webhookId = $this->config->get("payment_tamarapay_webhook_id");
        if ($webhookId == $this->language->get('text_none_webhook_id') || $webhookId == $this->language->get('text_save_config_get_webhook_id')) {
            $this->db->query("DELETE FROM " .DB_PREFIX . "setting WHERE `key`='payment_tamarapay_webhook_id'");
        }
    }

    private function addConsoleColumnsToTamaraOrder() {
        $addColumnsQuery = "ALTER TABLE `".DB_PREFIX."tamara_orders` 
                            ADD `captured_from_console` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Captured from console',
                            ADD `canceled_from_console` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Canceled from console',
                            ADD `refunded_from_console` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Refunded from console'";

        $this->db->query($addColumnsQuery);

        $addIndexQuery = "ALTER TABLE `".DB_PREFIX."tamara_orders` ADD INDEX `idx_console_query` (`is_authorised`,`created_at`)";
        $this->db->query($addIndexQuery);
    }

    private function updateSchemaVersion($newVersion) {
        $this->load->model('extension/payment/tamarapay');
        $this->model_extension_payment_tamarapay->updateTamaraConfig('version', $newVersion);
        $this->contextSchemaVersion = $newVersion;
    }

    /**
     * Retrieve payments config from admin
     */
    public function retrievePaymentConfig() {
        $url = $this->getTamaraPaymentUrlFromConfig();
        $token = $this->getTamaraPaymentTokenFromConfig();
        $result = ['success' => false];
        if (!empty($url) && !empty($token)) {
            $this->load->model('extension/payment/tamarapay');
            try {
                $paymentTypes = $this->model_extension_payment_tamarapay->getPaymentTypes($url, $token, true);
            } catch (\Exception $exception) {
                $result['error'] = $exception->getMessage();
            }
            if (!empty($paymentTypes)) {
                $result['payment_types'] = $paymentTypes;
                $result['success'] = true;
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
    }

    /**
     * @return string
     */
    private function getTamaraPaymentUrlFromConfig() {
        $this->load->model('extension/payment/tamarapay');
        return $this->model_extension_payment_tamarapay->getApiUrl();
    }

    /**
     * @return string
     */
    private function getTamaraPaymentTokenFromConfig() {
        return $this->config->get('payment_tamarapay_token');
    }
}