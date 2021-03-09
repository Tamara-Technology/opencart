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

        if (isset($this->request->post['payment_tamarapay_url'])) {
            $data['payment_tamarapay_url'] = $this->request->post['payment_tamarapay_url'];
        } else {
            $data['payment_tamarapay_url'] = $this->config->get('payment_tamarapay_url');
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

        if (isset($this->request->post['payment_tamarapay_iframe_checkout_enabled'])) {
            $data['payment_tamarapay_iframe_checkout_enabled'] = $this->request->post['payment_tamarapay_iframe_checkout_enabled'];
        } else {
            $data['payment_tamarapay_iframe_checkout_enabled'] = $this->config->get('payment_tamarapay_iframe_checkout_enabled');
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

        if (isset($this->request->post['payment_tamarapay_types_pay_by_later_min_limit'])) {
            $data['payment_tamarapay_types_pay_by_later_min_limit'] = $this->request->post['payment_tamarapay_types_pay_by_later_min_limit'];
        } else {
            $data['payment_tamarapay_types_pay_by_later_min_limit'] = $this->config->get('payment_tamarapay_types_pay_by_later_min_limit');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_later_max_limit'])) {
            $data['payment_tamarapay_types_pay_by_later_max_limit'] = $this->request->post['payment_tamarapay_types_pay_by_later_max_limit'];
        } else {
            $data['payment_tamarapay_types_pay_by_later_max_limit'] = $this->config->get('payment_tamarapay_types_pay_by_later_max_limit');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_later_currency'])) {
            $data['payment_tamarapay_types_pay_by_later_currency'] = $this->request->post['payment_tamarapay_types_pay_by_later_currency'];
        } else {
            $data['payment_tamarapay_types_pay_by_later_currency'] = $this->config->get('payment_tamarapay_types_pay_by_later_currency');
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

        if (isset($this->request->post['payment_tamarapay_types_pay_by_instalments_min_limit'])) {
            $data['payment_tamarapay_types_pay_by_instalments_min_limit'] = $this->request->post['payment_tamarapay_types_pay_by_instalments_min_limit'];
        } else {
            $data['payment_tamarapay_types_pay_by_instalments_min_limit'] = $this->config->get('payment_tamarapay_types_pay_by_instalments_min_limit');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_instalments_max_limit'])) {
            $data['payment_tamarapay_types_pay_by_instalments_max_limit'] = $this->request->post['payment_tamarapay_types_pay_by_instalments_max_limit'];
        } else {
            $data['payment_tamarapay_types_pay_by_instalments_max_limit'] = $this->config->get('payment_tamarapay_types_pay_by_instalments_max_limit');
        }

        if (isset($this->request->post['payment_tamarapay_types_pay_by_instalments_currency'])) {
            $data['payment_tamarapay_types_pay_by_instalments_currency'] = $this->request->post['payment_tamarapay_types_pay_by_instalments_currency'];
        } else {
            $data['payment_tamarapay_types_pay_by_instalments_currency'] = $this->config->get('payment_tamarapay_types_pay_by_instalments_currency');
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

        if (!$this->request->post['payment_tamarapay_url']) {
            $this->error['url'] = $this->language->get('error_url');

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

        if ($check_credentials) {
            $url = $this->request->post['payment_tamarapay_url'];
            $token = $this->request->post['payment_tamarapay_token'];
            if ($url != $this->getTamaraPaymentUrlFromConfig() || $token != $this->getTamaraPaymentTokenFromConfig()) {
                try {
                    $paymentTypes = $this->model_extension_payment_tamarapay->getPaymentTypes($url, $token, true);
                    $this->request->post = $this->addPaymentsTypeToRequest($this->request->post, $paymentTypes);
                } catch (\Exception $exception) {
                    $this->error['token'] = $this->language->get('error_token_invalid');
                }
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    private function addPaymentsTypeToRequest($requestData, $paymentTypes) {
        $keys = ['min_limit', 'max_limit', 'currency'];
        foreach ($paymentTypes as $type) {
            $name = strtolower($type['name']);
            foreach ($keys as $key) {
                $_key = 'payment_tamarapay_types_'.$name.'_' . $key;
                if ($key == 'currency') {
                    $requestData[$_key] = $type['max_limit']['currency'];
                } else {
                    $requestData[$_key] = $type[$key]['amount'];
                }
            }
        }
        return $requestData;
    }

    public function install() {
        $this->load->model('extension/payment/tamarapay');

        $this->model_extension_payment_tamarapay->install();
    }

    public function uninstall() {
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
        }
        return;
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
            $paymentTypes = $this->model_extension_payment_tamarapay->getPaymentTypes($url, $token, true);
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
        return $this->config->get('payment_tamarapay_url');
    }

    /**
     * @return string
     */
    private function getTamaraPaymentTokenFromConfig() {
        return $this->config->get('payment_tamarapay_token');
    }
}