<?php
namespace Opencart\Catalog\Controller\Extension\Tamarapay\Payment\Api;

class Tamarapay extends \Opencart\System\Engine\Model {
    /**
     * Get checkout information by order id
     */
    public function checkout_information() {
        $this->load->language('extension/tamarapay/payment/api/tamarapay');
        $json = array();

        if (!$this->isValidCredential()) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('extension/tamarapay/payment/tamarapay');
            $opencartOrderId = (int) $this->request->get['order_id'];
            try {
                $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrder($opencartOrderId);
                $merchantUrls = $this->model_extension_tamarapay_payment_tamarapay->getMerchantUrls();
                $result = [
                    'checkout_success_url' => $merchantUrls['success'],
                    'checkout_failure_url' => $merchantUrls['failure'],
                    'checkout_cancel_url' => $merchantUrls['cancel'],
                    'opencart_order_id' => $opencartOrderId,
                    'tamara_order_id' => $tamaraOrder['tamara_order_id'],
                    'checkout_redirect_url' => $tamaraOrder['redirect_url']
                ];
                $json['success'] = $this->language->get('text_success');
                $json['checkout_data'] = $result;
            } catch (Exception $exception) {
                $this->model_extension_tamarapay_payment_tamarapay->log([$exception->getMessage()]);
                $json['error'] = $exception->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * @return bool
     */
    private function isValidCredential() {
        $this->load->model('account/api');
        if (!isset($this->request->get['key'])) {
            return false;
        }

        // Login with API Key
        if(isset($this->request->get['username'])) {
            $api_info = $this->model_account_api->login($this->request->get['username'], $this->request->get['key']);
        } else {
            $api_info = $this->model_account_api->login('Default', $this->request->get['key']);
        }

        return !empty($api_info);
    }
}