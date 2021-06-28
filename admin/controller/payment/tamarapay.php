<?php
class ControllerPaymentTamarapay extends Controller {
    private $error = array();
    private $contextSchemaVersion;

    private function getSchemaVersion() {
        $this->load->model('payment/tamarapay');
        return $this->model_payment_tamarapay->getSchemaVersion();
    }

    private function getCurrentDbVersion() {
        $this->load->model('payment/tamarapay');
        return $this->model_payment_tamarapay->getCurrentVersionInDb();
    }

    private function processUpgrade() {
        $this->contextSchemaVersion = $this->getCurrentDbVersion();
        return $this->upgradeData();
    }

    public function index() {
        $this->removeLegacyResources();
        if (!$this->isVendorAutoloadExist()) {
            $this->addVendorAutoload();
        }
        $this->load->language('payment/tamarapay');
        $this->load->model('localisation/order_status');
        $this->load->model('payment/tamarapay');
        $this->processUpgrade();

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if ('POST' === ($this->request->server['REQUEST_METHOD']) && $this->validate()) {
            $this->model_setting_setting->editSetting('tamarapay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['extension_version'] = $this->model_payment_tamarapay->getExtensionVersion();
        $data['heading_title'] = $this->language->get('heading_title');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['entry_url'] = $this->language->get('entry_url');
        $data['entry_token'] = $this->language->get('entry_token');
        $data['entry_token_notification'] = $this->language->get('entry_token_notification');
        $data['error_token_notification'] = $this->error['token_notification'] ?? '';
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['entry_enable_trigger_actions'] = $this->language->get('entry_enable_trigger_actions');
        $data['entry_enable_iframe_checkout'] = $this->language->get('entry_enable_iframe_checkout');
        $data['entry_enable'] = $this->language->get('entry_enable');
        $data['entry_title'] = $this->language->get('entry_title');
        $data['entry_min_limit_amount'] = $this->language->get('entry_min_limit_amount');
        $data['entry_max_limit_amount'] = $this->language->get('entry_max_limit_amount');
        $data['entry_order_status_success'] = $this->language->get('entry_order_status_success');
        $data['entry_order_status_failure'] = $this->language->get('entry_order_status_failure');
        $data['entry_order_status_canceled'] = $this->language->get('entry_order_status_canceled');
        $data['entry_order_status_authorised'] = $this->language->get('entry_order_status_authorised');
        $data['entry_capture_order_status'] = $this->language->get('entry_capture_order_status');
        $data['entry_cancel_order_status'] = $this->language->get('entry_cancel_order_status');
        $data['entry_enable_debug'] = $this->language->get('entry_enable_debug');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_auto_fetching'] = $this->language->get('entry_auto_fetching');

        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_url'] = $this->error['url'] ?? '';
        $data['error_token'] = $this->error['token'] ?? '';
        $data['error_merchant_success_url'] = $this->error['merchant_success_url'] ?? '';
        $data['error_merchant_failure_url'] = $this->error['merchant_failure_url'] ?? '';
        $data['error_merchant_cancel_url'] = $this->error['merchant_cancel_url'] ?? '';
        $data['error_merchant_notification_url'] = $this->error['merchant_notification_url'] ?? '';

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/tamarapay', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['action'] = $this->url->link('payment/tamarapay', 'token=' . $this->session->data['token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['tamarapay_url'])) {
            $data['tamarapay_url'] = $this->request->post['tamarapay_url'];
        } else {
            $data['tamarapay_url'] = $this->config->get('tamarapay_url');
        }

        if (isset($this->request->post['tamarapay_token_notification'])) {
            $data['tamarapay_token_notification'] = $this->request->post['tamarapay_token_notification'];
        } else {
            $data['tamarapay_token_notification'] = $this->config->get('tamarapay_token_notification');
        }

        if (isset($this->request->post['tamarapay_token'])) {
            $data['tamarapay_token'] = $this->request->post['tamarapay_token'];
        } else {
            $data['tamarapay_token'] = $this->config->get('tamarapay_token');
        }

        if (isset($this->request->post['tamarapay_merchant_success_url'])) {
            $data['tamarapay_merchant_success_url'] = $this->request->post['tamarapay_merchant_success_url'];
        } else {
            $data['tamarapay_merchant_success_url'] = $this->config->get('tamarapay_merchant_success_url');
        }

        if (isset($this->request->post['tamarapay_merchant_failure_url'])) {
            $data['tamarapay_merchant_failure_url'] = $this->request->post['tamarapay_merchant_failure_url'];
        } else {
            $data['tamarapay_merchant_failure_url'] = $this->config->get('tamarapay_merchant_failure_url');
        }

        if (isset($this->request->post['tamarapay_merchant_cancel_url'])) {
            $data['tamarapay_merchant_cancel_url'] = $this->request->post['tamarapay_merchant_cancel_url'];
        } else {
            $data['tamarapay_merchant_cancel_url'] = $this->config->get('tamarapay_merchant_cancel_url');
        }

        if (isset($this->request->post['tamarapay_merchant_notification_url'])) {
            $data['tamarapay_merchant_notification_url'] = $this->request->post['tamarapay_merchant_notification_url'];
        } else {
            $data['tamarapay_merchant_notification_url'] = $this->config->get('tamarapay_merchant_notification_url');
        }

        if (isset($this->request->post['tamarapay_debug'])) {
            $data['tamarapay_debug'] = $this->request->post['tamarapay_debug'];
        } else {
            $data['tamarapay_debug'] = $this->config->get('tamarapay_debug');
        }

        if (isset($this->request->post['tamarapay_trigger_actions_enabled'])) {
            $data['tamarapay_trigger_actions_enabled'] = $this->request->post['tamarapay_trigger_actions_enabled'];
        } else {
            $data['tamarapay_trigger_actions_enabled'] = $this->config->get('tamarapay_trigger_actions_enabled');
        }

        if (isset($this->request->post['tamarapay_iframe_checkout_enabled'])) {
            $data['tamarapay_iframe_checkout_enabled'] = $this->request->post['tamarapay_iframe_checkout_enabled'];
        } else {
            $data['tamarapay_iframe_checkout_enabled'] = $this->config->get('tamarapay_iframe_checkout_enabled');
        }

        if (isset($this->request->post['tamarapay_order_status_success_id'])) {
            $data['tamarapay_order_status_success_id'] = $this->request->post['tamarapay_order_status_success_id'];
        } else {
            $data['tamarapay_order_status_success_id'] = $this->config->get('tamarapay_order_status_success_id');
        }

        if (isset($this->request->post['tamarapay_order_status_failure_id'])) {
            $data['tamarapay_order_status_failure_id'] = $this->request->post['tamarapay_order_status_failure_id'];
        } else {
            $data['tamarapay_order_status_failure_id'] = $this->config->get('tamarapay_order_status_failure_id');
        }

        if (isset($this->request->post['tamarapay_order_status_canceled_id'])) {
            $data['tamarapay_order_status_canceled_id'] = $this->request->post['tamarapay_order_status_canceled_id'];
        } else {
            $data['tamarapay_order_status_canceled_id'] = $this->config->get('tamarapay_order_status_canceled_id');
        }

        $data['entry_enable_tamara_checkout_success_page'] = $this->language->get('entry_enable_tamara_checkout_success_page');
        if (isset($this->request->post['tamarapay_enable_tamara_checkout_success_page'])) {
            $data['tamarapay_enable_tamara_checkout_success_page'] = $this->request->post['tamarapay_enable_tamara_checkout_success_page'];
        } else {
            $data['tamarapay_enable_tamara_checkout_success_page'] = $this->config->get('tamarapay_enable_tamara_checkout_success_page');
        }

        $data['entry_only_show_for_these_customer'] = $this->language->get('entry_only_show_for_these_customer');
        if (isset($this->request->post['tamarapay_only_show_for_these_customer'])) {
            $data['tamarapay_only_show_for_these_customer'] = $this->request->post['tamarapay_only_show_for_these_customer'];
        } else {
            $data['tamarapay_only_show_for_these_customer'] = $this->config->get('tamarapay_only_show_for_these_customer');
        }

        if (isset($this->request->post['tamarapay_order_status_authorised_id'])) {
            $data['tamarapay_order_status_authorised_id'] = $this->request->post['tamarapay_order_status_authorised_id'];
        } else {
            $data['tamarapay_order_status_authorised_id'] = $this->config->get('tamarapay_order_status_authorised_id');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_later_enabled'])) {
            $data['tamarapay_types_pay_by_later_enabled'] = $this->request->post['tamarapay_types_pay_by_later_enabled'];
        } else {
            $data['tamarapay_types_pay_by_later_enabled'] = $this->config->get('tamarapay_types_pay_by_later_enabled');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_later_title'])) {
            $data['tamarapay_types_pay_by_later_title'] = $this->request->post['tamarapay_types_pay_by_later_title'];
        } else {
            $data['tamarapay_types_pay_by_later_title'] = $this->config->get('tamarapay_types_pay_by_later_title');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_later_min_limit'])) {
            $data['tamarapay_types_pay_by_later_min_limit'] = $this->request->post['tamarapay_types_pay_by_later_min_limit'];
        } else {
            $data['tamarapay_types_pay_by_later_min_limit'] = $this->config->get('tamarapay_types_pay_by_later_min_limit');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_later_max_limit'])) {
            $data['tamarapay_types_pay_by_later_max_limit'] = $this->request->post['tamarapay_types_pay_by_later_max_limit'];
        } else {
            $data['tamarapay_types_pay_by_later_max_limit'] = $this->config->get('tamarapay_types_pay_by_later_max_limit');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_later_currency'])) {
            $data['tamarapay_types_pay_by_later_currency'] = $this->request->post['tamarapay_types_pay_by_later_currency'];
        } else {
            $data['tamarapay_types_pay_by_later_currency'] = $this->config->get('tamarapay_types_pay_by_later_currency');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_instalments_enabled'])) {
            $data['tamarapay_types_pay_by_instalments_enabled'] = $this->request->post['tamarapay_types_pay_by_instalments_enabled'];
        } else {
            $data['tamarapay_types_pay_by_instalments_enabled'] = $this->config->get('tamarapay_types_pay_by_instalments_enabled');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_instalments_title'])) {
            $data['tamarapay_types_pay_by_instalments_title'] = $this->request->post['tamarapay_types_pay_by_instalments_title'];
        } else {
            $data['tamarapay_types_pay_by_instalments_title'] = $this->config->get('tamarapay_types_pay_by_instalments_title');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_instalments_min_limit'])) {
            $data['tamarapay_types_pay_by_instalments_min_limit'] = $this->request->post['tamarapay_types_pay_by_instalments_min_limit'];
        } else {
            $data['tamarapay_types_pay_by_instalments_min_limit'] = $this->config->get('tamarapay_types_pay_by_instalments_min_limit');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_instalments_max_limit'])) {
            $data['tamarapay_types_pay_by_instalments_max_limit'] = $this->request->post['tamarapay_types_pay_by_instalments_max_limit'];
        } else {
            $data['tamarapay_types_pay_by_instalments_max_limit'] = $this->config->get('tamarapay_types_pay_by_instalments_max_limit');
        }

        if (isset($this->request->post['tamarapay_types_pay_by_instalments_currency'])) {
            $data['tamarapay_types_pay_by_instalments_currency'] = $this->request->post['tamarapay_types_pay_by_instalments_currency'];
        } else {
            $data['tamarapay_types_pay_by_instalments_currency'] = $this->config->get('tamarapay_types_pay_by_instalments_currency');
        }

        if (isset($this->request->post['tamarapay_capture_order_status_id'])) {
            $data['tamarapay_capture_order_status_id'] = $this->request->post['tamarapay_capture_order_status_id'];
        } else {
            $data['tamarapay_capture_order_status_id'] = $this->config->get('tamarapay_capture_order_status_id');
        }

        if (isset($this->request->post['tamarapay_cancel_order_status_id'])) {
            $data['tamarapay_cancel_order_status_id'] = $this->request->post['tamarapay_cancel_order_status_id'];
        } else {
            $data['tamarapay_cancel_order_status_id'] = $this->config->get('tamarapay_cancel_order_status_id');
        }

        $data['entry_enable_webhook'] = $this->language->get('entry_enable_webhook');
        $data['entry_webhook_id'] = $this->language->get('entry_webhook_id');

        if (isset($this->request->post['tamarapay_webhook_enabled'])) {
            $webHookEnabled = $this->request->post['tamarapay_webhook_enabled'];
        } else {
            $webHookEnabled = $this->config->get('tamarapay_webhook_enabled');
            if ($webHookEnabled === null) {
                $webHookEnabled = 1;
            }
        }
        $webHookEnabled = intval($webHookEnabled);
        $data['tamarapay_webhook_enabled'] = $webHookEnabled;

        if ($webHookEnabled) {
            if (!empty($this->config->get('tamarapay_webhook_id'))) {
                $data['tamarapay_webhook_id'] = $this->config->get('tamarapay_webhook_id');
            } else {
                $data['tamarapay_webhook_id'] = $this->language->get('text_save_config_get_webhook_id');
            }
        } else {
            $data['tamarapay_webhook_id'] = $this->language->get("text_none_webhook_id");
        }

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['tamarapay_geo_zone_id'])) {
            $data['tamarapay_geo_zone_id'] = $this->request->post['tamarapay_geo_zone_id'];
        } else {
            $data['tamarapay_geo_zone_id'] = $this->config->get('tamarapay_geo_zone_id');
        }

        if (isset($this->request->post['tamarapay_status'])) {
            $data['tamarapay_status'] = $this->request->post['tamarapay_status'];
        } else {
            $data['tamarapay_status'] = $this->config->get('tamarapay_status');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['tamarapay_sort_order'])) {
            $data['tamarapay_sort_order'] = $this->request->post['tamarapay_sort_order'];
        } else {
            $data['tamarapay_sort_order'] = $this->config->get('tamarapay_sort_order');
        }

        $data['token'] = $this->session->data['token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/tamarapay.tpl', $data));
    }


    protected function validate() {
        $this->load->model('payment/tamarapay');

        $check_credentials = true;

        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->error['warning'] = $this->language->get('error_php_version');
        }

        if (!$this->user->hasPermission('modify', 'payment/tamarapay')) {
            $this->error['warning'] = $this->language->get('error_permission');

            $check_credentials = false;
        }

        if (!$this->request->post['tamarapay_url']) {
            $this->error['url'] = $this->language->get('error_url');

            $check_credentials = false;
        }

        if (!$this->request->post['tamarapay_token']) {
            $this->error['token'] = $this->language->get('error_token');

            $check_credentials = false;
        }

        if (!$this->request->post['tamarapay_token_notification']) {
            $this->error['token_notification'] = $this->language->get('error_notification_token_required');

            $check_credentials = false;
        }

        $this->request->post['tamarapay_url'] = preg_replace("/\s+/", "", $this->request->post['tamarapay_url']);
        $this->request->post['tamarapay_token'] = preg_replace("/\s+/", "", $this->request->post['tamarapay_token']);
        $this->request->post['tamarapay_token_notification'] = preg_replace("/\s+/", "", $this->request->post['tamarapay_token_notification']);

        if ($check_credentials) {
            $url = $this->request->post['tamarapay_url'];
            $token = $this->request->post['tamarapay_token'];
            if ($this->isChangedConfig('tamarapay_url') || $this->isChangedConfig('tamarapay_token')) {
                try {
                    $paymentTypes = $this->model_payment_tamarapay->getPaymentTypes($url, $token, true);
                    $this->request->post = $this->addPaymentsTypeToRequest($this->request->post, $paymentTypes);
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
        if ($this->request->post['tamarapay_webhook_id'] == $this->language->get('text_none_webhook_id')
            || $this->request->post['tamarapay_webhook_id'] == $this->language->get('text_save_config_get_webhook_id')) {
            $this->request->post['tamarapay_webhook_id'] = "";
        }
        if (!$this->isChangedConfig('tamarapay_webhook_enabled')) {
            return true;
        }
        $result = true;
        $webhookEnabled = $this->request->post['tamarapay_webhook_enabled'];
        $webhookId = $this->config->get("tamarapay_webhook_id");
        if (!empty($webhookEnabled)) {
            if (empty($webhookId)) {
                try {
                    $webhookId = $this->model_payment_tamarapay->registerWebhook($this->config->get('tamarapay_url'),
                        $this->request->post['tamarapay_token']);
                } catch (\Exception $exception) {
                    $this->model_payment_tamarapay->log($exception->getMessage());
                    $webhookEnabled = 0;
                    $this->error['warning'] = $exception->getMessage();
                    $result = false;
                }
            }
        } else {
            if (!empty($webhookId)) {
                try {
                    $this->model_payment_tamarapay->removeWebhook();
                } catch (\Exception $exception) {
                    $this->model_payment_tamarapay->log("Error when remove webhook: " . $exception->getMessage());
                }
                $webhookId = "";
                $result = true;
            }
        }
        $this->request->post['tamarapay_webhook_enabled'] = $webhookEnabled;
        $this->request->post['tamarapay_webhook_id'] = $webhookId;
        return $result;
    }

    /**
     * @param $key
     * @return bool
     */
    private function isChangedConfig($key) {
        return $this->request->post[$key] != $this->config->get($key);
    }

    private function addPaymentsTypeToRequest($requestData, $paymentTypes) {
        $keys = ['min_limit', 'max_limit', 'currency'];
        foreach ($paymentTypes as $type) {
            $name = strtolower($type['name']);
            foreach ($keys as $key) {
                $_key = 'tamarapay_types_'.$name.'_' . $key;
                if ($key == 'currency') {
                    $requestData[$_key] = $type['max_limit']['currency'];
                } else {
                    $requestData[$_key] = $type[$key]['amount'];
                }
            }
        }
        return $requestData;
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

    private function removeLegacyResources() {
        $this->removeLegacyVendorAutoloadIfExist();
        $this->removeLegacyDirectoryIfExist();
    }

    private function removeLegacyVendorAutoloadIfExist() {
        $contents = file_get_contents($this->getSystemStartupFilePath());
        if (strpos($contents, "require_once DIR_SYSTEM . '../tamarapay/vendor/autoload.php'") !== false) {
            $contents = str_replace("//Add Tamara vendor autoload", '', $contents);
            $contents = str_replace("require_once DIR_SYSTEM . '../tamarapay/vendor/autoload.php';", '', $contents);
            file_put_contents($this->getSystemStartupFilePath(), $contents);
        }
    }

    private function removeLegacyDirectoryIfExist() {
        $path = realpath ( DIR_SYSTEM . '../tamarapay');
        if (is_dir($path)) {
            $this->deleteDirectory($path);
        }
    }

    public function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }
        return rmdir($dir);
    }

    private function getSystemStartupFilePath() {
        return DIR_SYSTEM . 'startup.php';
    }

    public function install() {
        if (!$this->isVendorAutoloadExist()) {
            $this->addVendorAutoload();
        }
        $this->load->model('payment/tamarapay');
        $this->model_payment_tamarapay->install();
    }

    public function uninstall() {
        if ($this->isVendorAutoloadExist()) {
            $this->removeVendorAutoload();
        }
        $this->load->model('payment/tamarapay');
        $this->model_payment_tamarapay->uninstall();
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
        }
        return;
    }

    private function addConsoleColumnsToTamaraOrder() {
        $addColumnsQuery = "ALTER TABLE `".DB_PREFIX."tamara_orders` 
                            ADD `captured_from_console` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Captured from console',
                            ADD `canceled_from_console` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Canceled from console',
                            ADD `refunded_from_console` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Refunded from console'";

        $this->db->query($addColumnsQuery);
    }

    private function updateSchemaVersion($newVersion) {
        $this->load->model('payment/tamarapay');
        $this->model_payment_tamarapay->updateTamaraConfig('version', $newVersion);
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
            $this->load->model('payment/tamarapay');
            $paymentTypes = $this->model_payment_tamarapay->getPaymentTypes($url, $token, true);
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
        return $this->config->get('tamarapay_url');
    }

    /**
     * @return string
     */
    private function getTamaraPaymentTokenFromConfig() {
        return $this->config->get('tamarapay_token');
    }
}