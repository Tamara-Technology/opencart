<?php



namespace Opencart\Admin\Controller\Extension\Tamarapay\Payment;

/**
 *
 * Class Tamarapay
 *
 * @package Opencart\Admin\Controller\Extension\Tamarapay\Payment
 */
class Tamarapay extends \Opencart\System\Engine\Controller {

    private $error = array();
    private $contextSchemaVersion;

    private function getSchemaVersion() {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        return $this->model_extension_tamarapay_payment_tamarapay->getSchemaVersion();
    }

    private function getCurrentDbVersion() {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        return $this->model_extension_tamarapay_payment_tamarapay->getCurrentVersionInDb();
    }

    private function processUpgrade() {
        $this->contextSchemaVersion = $this->getCurrentDbVersion();
        return $this->upgradeData();
    }

    public function index() {
        $this->load->language('extension/tamarapay/payment/tamarapay');
        $this->load->model('localisation/order_status');
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->processUpgrade();
        if (!$this->isVendorAutoloadExist()) {
            $this->addVendorAutoload();
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->response->setOutput($this->load->view('extension/tamarapay/payment/tamarapay', $this->getDataForIndexPage()));
    }

    protected function getDataForIndexPage() {
        $data = [];
        $this->prepareLayoutDataForIndexPage($data);
        $this->prepareTextDataForIndexPage($data);
        $this->prepareExtensionConfigDataForIndexPage($data);
        $this->preparePaymentTypesDataForIndexPage($data);
        $this->prepareGeneralConfigDataForIndexPage($data);
        return $data;
    }

    protected function prepareLayoutDataForIndexPage(&$data) {

        // ---------------------


        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/tamarapay/payment/tamarapay', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/tamarapay/payment/tamarapay.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');


        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
    
    }

    public function save(): void {

        $this->load->language('extension/tamarapay/payment/tamarapay');

        if ('POST' === ($this->request->server['REQUEST_METHOD']) && $this->validate() ) {

            $this->model_setting_setting->editSetting('payment_tamarapay', $this->request->post);

            $json['success'] = $this->language->get('text_success');

        } else {
            $json['error'] = $this->error;
        }

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    protected function prepareTextDataForIndexPage(&$data) {
        $data['extension_version'] = $this->model_extension_tamarapay_payment_tamarapay->getExtensionVersion();
        $this->prepareVersionMessageForIndexPage($data);
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_url'] = $this->error['url'] ?? '';
        $data['error_token'] = $this->error['token'] ?? '';
        $data['error_token_notification'] = $this->error['token_notification'] ?? '';
        $data['error_merchant_success_url'] = $this->error['merchant_success_url'] ?? '';
        $data['error_merchant_failure_url'] = $this->error['merchant_failure_url'] ?? '';
        $data['error_merchant_cancel_url'] = $this->error['merchant_cancel_url'] ?? '';
        $data['error_merchant_notification_url'] = $this->error['merchant_notification_url'] ?? '';
        $data['error_merchant_public_key'] = $this->error['merchant_public_key'] ?? '';
    }

    protected function prepareVersionMessageForIndexPage(&$data) {
        $githubVersionLink = "https://raw.githubusercontent.com/tamara-solution/opencart/v40x/VERSION.txt";
        $githubVersion = @file_get_contents($githubVersionLink);
        if ($githubVersion) {
            $downloadLink = "https://github.com/tamara-solution/opencart/archive/refs/heads/master.zip";
            $readmeLink = "https://github.com/tamara-solution/opencart/blob/master/README.md";
            $data['github'] = ['download_link' => $downloadLink, 'readme_link' => $readmeLink];
            if (version_compare($data['extension_version'], $githubVersion, '<')) {
                $data['is_using_latest_version'] = false;
            } else {
                $data['is_using_latest_version'] = true;
            }
        }
    }

    protected function prepareExtensionConfigDataForIndexPage(&$data) {
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

        if (isset($this->request->post['payment_tamarapay_merchant_public_key'])) {
            $data['payment_tamarapay_merchant_public_key'] = $this->request->post['payment_tamarapay_merchant_public_key'];
        } else {
            $data['payment_tamarapay_merchant_public_key'] = $this->config->get('payment_tamarapay_merchant_public_key');
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
            if ($this->config->get('payment_tamarapay_trigger_actions_enabled') === null) {
                $data['payment_tamarapay_trigger_actions_enabled'] = 1;
            } else {
                $data['payment_tamarapay_trigger_actions_enabled'] = $this->config->get('payment_tamarapay_trigger_actions_enabled');
            }
        }

        if (isset($this->request->post['payment_tamarapay_order_status_create_id'])) {
            $data['payment_tamarapay_order_status_create_id'] = $this->request->post['payment_tamarapay_order_status_create_id'];
        } else {
            $data['payment_tamarapay_order_status_create_id'] = $this->config->get('payment_tamarapay_order_status_create_id');
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

        if (isset($this->request->post['payment_tamarapay_enable_under_over_warning'])) {
            $data['payment_tamarapay_enable_under_over_warning'] = $this->request->post['payment_tamarapay_enable_under_over_warning'];
        } else {
            if ($this->config->get('payment_tamarapay_enable_under_over_warning') === null) {
                $data['payment_tamarapay_enable_under_over_warning'] = 1;
            } else {
                $data['payment_tamarapay_enable_under_over_warning'] = $this->config->get('payment_tamarapay_enable_under_over_warning');
            }
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

        if (isset($this->request->post['payment_tamarapay_only_show_for_these_customer'])) {
            $data['payment_tamarapay_only_show_for_these_customer'] = $this->request->post['payment_tamarapay_only_show_for_these_customer'];
        } else {
            $data['payment_tamarapay_only_show_for_these_customer'] = $this->config->get('payment_tamarapay_only_show_for_these_customer');
        }

        if (isset($this->request->post['payment_tamarapay_checkout_success_url'])) {
            $data['payment_tamarapay_checkout_success_url'] = $this->request->post['payment_tamarapay_checkout_success_url'];
        } else {
            $data['payment_tamarapay_checkout_success_url'] = $this->config->get('payment_tamarapay_checkout_success_url');
        }

        if (isset($this->request->post['payment_tamarapay_checkout_cancel_url'])) {
            $data['payment_tamarapay_checkout_cancel_url'] = $this->request->post['payment_tamarapay_checkout_cancel_url'];
        } else {
            $data['payment_tamarapay_checkout_cancel_url'] = $this->config->get('payment_tamarapay_checkout_cancel_url');
        }

        if (isset($this->request->post['payment_tamarapay_checkout_failure_url'])) {
            $data['payment_tamarapay_checkout_failure_url'] = $this->request->post['payment_tamarapay_checkout_failure_url'];
        } else {
            $data['payment_tamarapay_checkout_failure_url'] = $this->config->get('payment_tamarapay_checkout_failure_url');
        }
    }

    protected function preparePaymentTypesDataForIndexPage(&$data) {
        $data['notifications'] = [];
        $data['payment_tamarapay_merchant_public_key_extra_class'] = ' required';
    }

    protected function prepareGeneralConfigDataForIndexPage(&$data) {
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
    }

    protected function validate() {
        $this->load->model('extension/tamarapay/payment/tamarapay');

        $check_credentials = true;

        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->error['warning'] = $this->language->get('error_php_version');
        }

        if (!$this->user->hasPermission('modify', 'extension/tamarapay/payment/tamarapay')) {
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

        if (!$this->request->post['payment_tamarapay_merchant_public_key']) {
            $this->error['merchant_public_key'] = $this->language->get('error_field_is_required');

            $check_credentials = false;
        }

        $this->request->post['payment_tamarapay_token'] = preg_replace("/\s+/", "", $this->request->post['payment_tamarapay_token']);
        $this->request->post['payment_tamarapay_token_notification'] = preg_replace("/\s+/", "", $this->request->post['payment_tamarapay_token_notification']);

        if ($check_credentials) {
            $url = $this->model_extension_tamarapay_payment_tamarapay->getApiUrl($this->request->post['payment_tamarapay_api_environment']);
            $token = $this->request->post['payment_tamarapay_token'];
            if ($this->isChangedConfig('payment_tamarapay_api_environment') || $this->isChangedConfig('payment_tamarapay_token')) {
                try {
                    $client = $this->model_extension_tamarapay_payment_tamarapay->createClient(['url' => $url, 'token' => $token]);

                    /**
                     * @var $response \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse
                     */
                    $response = $this->model_extension_tamarapay_payment_tamarapay->getPaymentTypesOfClient($client);
                    if ($response->getStatusCode() == 401) {
                        throw new \Exception("Merchant token is invalid");
                    }
                    $this->model_extension_tamarapay_payment_tamarapay->removeTamaraCache();
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
        $data = PHP_EOL . "//Add Tamara vendor autoload".PHP_EOL . "if (is_file(DIR_EXTENSION . 'tamarapay/library/vendor/autoload.php')) {require_once(DIR_EXTENSION . 'tamarapay/library/vendor/autoload.php');}";
        $fp = fopen($this->getSystemStartupFilePath(), 'a');
        fwrite($fp, $data);
    }

    private function removeVendorAutoload() {
        $contents = file_get_contents($this->getSystemStartupFilePath());
        $contents = str_replace("//Add Tamara vendor autoload", '', $contents);
        $contents = str_replace("if (is_file(DIR_EXTENSION . 'tamarapay/library/vendor/autoload.php')) {require_once(DIR_EXTENSION . 'tamarapay/library/vendor/autoload.php');}", '', $contents);
        file_put_contents($this->getSystemStartupFilePath(), $contents);
    }

    private function isVendorAutoloadExist() {
        $contents = file_get_contents($this->getSystemStartupFilePath());
        if (strpos($contents, "require_once(DIR_EXTENSION . 'tamarapay/library/vendor/autoload.php')") !== false) {
            return true;
        }
        return false;
    }

    public function deleteDir($dir) {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    private function getSystemStartupFilePath() {
        return DIR_SYSTEM . 'startup.php';
    }

    public function install() {
        if (!$this->isVendorAutoloadExist()) {
            $this->addVendorAutoload();
        }
        $this->load->model('extension/tamarapay/payment/tamarapay');

        $this->model_extension_tamarapay_payment_tamarapay->install();
    }

    public function uninstall() {
        if ($this->isVendorAutoloadExist()) {
            $this->removeVendorAutoload();
        }
        $this->load->model('extension/tamarapay/payment/tamarapay');

        $this->model_extension_tamarapay_payment_tamarapay->uninstall();
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
    }

    private function updateSchemaVersion($newVersion) {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->model_extension_tamarapay_payment_tamarapay->updateTamaraConfig('version', $newVersion);
        $this->contextSchemaVersion = $newVersion;
    }

    /**
     * Flush Tamara cache
     */
    public function flushTamaraCache() {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->model_extension_tamarapay_payment_tamarapay->removeTamaraCache();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    /**
     * @return string
     */
    private function getTamaraPaymentUrlFromConfig() {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        return $this->model_extension_tamarapay_payment_tamarapay->getApiUrl();
    }

    /**
     * @return string
     */
    private function getTamaraPaymentTokenFromConfig() {
        return $this->config->get('payment_tamarapay_token');
    }
}