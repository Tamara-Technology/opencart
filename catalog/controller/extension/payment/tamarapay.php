<?php

use Tamara\Notification\NotificationService;

class ControllerExtensionPaymentTamarapay extends Controller
{
    private const INDEX_TEMPLATE = "extension/payment/tamarapay";
    const ORDER_CANCELED_STATUS_ID = 7;

    public function index()
    {
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('extension/payment/tamarapay');

        //validate currency
        if (!$this->model_extension_payment_tamarapay->isCurrencySupported()) {
            return $this->renderIndexTemplate(['error_get_payment' => $this->language->get('error_wrong_currency')]);
        }
        $methods = $this->model_extension_payment_tamarapay->getPaymentMethodsForCheckoutPage();
        if (empty($methods)) {
            return $this->renderIndexTemplate(['error_get_payment' => $this->language->get('error_no_method_available')]);
        }
        $installmentMinLimit = 0;
        $totalTypeAvailable = 0;
        foreach ($methods as $method) {
            if ($method['name'] == "PAY_BY_INSTALMENTS") {
                $installmentMinLimit = $method['min_limit'];
            }
            if ($method['is_available']) {
                $totalTypeAvailable++;
            }
        }
        $data['installment_min_limit'] = $installmentMinLimit;
        $data['methods'] = $methods;
        $data['total_method_available'] = $totalTypeAvailable;
        $data['use_iframe_checkout'] = $this->config->get('payment_tamarapay_iframe_checkout_enabled');
        $data['merchant_urls'] = $this->model_extension_payment_tamarapay->getMerchantUrls();
        $data['order_data'] = $this->model_extension_payment_tamarapay->getOrder( $this->model_extension_payment_tamarapay->getOrderIdFromSession());
        $data['language_code'] = $this->language->get('code');
        return $this->renderIndexTemplate($data);
    }

    protected function renderIndexTemplate($data)
    {
        return $this->renderTemplate(self::INDEX_TEMPLATE, $data);
    }

    private function renderTemplate($template, $data)
    {
        return $this->load->view($template, $data);
    }

    public function send()
    {
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('extension/payment/tamarapay');

        $error = [];

        if (!isset($this->request->post['payment_type'])) {
            $error = ['error' => $this->language->get('error_missing_payment_type')];
        }

        if (!empty($error)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($error));
            return;
        }

        $url = $this->config->get('payment_tamarapay_url');
        $token = $this->config->get('payment_tamarapay_token');

        $response = $this->model_extension_payment_tamarapay->createCheckout($url, $token,
            $this->request->post['payment_type']);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    public function success()
    {
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('extension/payment/tamarapay');

        if (!isset($this->session->data['order_id'])) {
            $this->response->redirect($this->url->link('common/home', '', true));
        }

        $data['order_id'] = $this->session->data['order_id'];
        $tamaraOrder = $this->model_extension_payment_tamarapay->getTamaraOrder($data['order_id']);
        if (!$tamaraOrder['is_authorised']) {

            //set order status
            $successStatusId = $this->config->get('payment_tamarapay_order_status_success_id');
            $this->model_extension_payment_tamarapay->addOrderComment($this->session->data['order_id'], $successStatusId, "Tamara - Pay success", 0);
        }

        //render success pay
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_basket'),
            'href' => $this->url->link('checkout/cart')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_checkout'),
            'href' => $this->url->link('checkout/checkout', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_success'),
            'href' => $this->url->link('checkout/success')
        );

        if ($this->customer->isLogged()) {
            $data['text_message'] = sprintf($this->language->get('text_customer'),
                $this->url->link('account/account', '', true), $this->url->link('account/order', '', true),
                $this->url->link('account/download', '', true), $this->url->link('information/contact'));
        } else {
            $data['text_message'] = sprintf($this->language->get('text_guest'),
                $this->url->link('information/contact'));
        }

        $data['continue'] = $this->url->link('common/home');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $this->clearCheckoutSession();

        $this->response->setOutput($this->load->view('extension/payment/tamarapay_success', $data));
    }

    private function clearCheckoutSession()
    {
        $this->cart->clear();

        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);
        unset($this->session->data['guest']);
        unset($this->session->data['comment']);
        unset($this->session->data['order_id']);
        unset($this->session->data['coupon']);
        unset($this->session->data['reward']);
        unset($this->session->data['voucher']);
        unset($this->session->data['vouchers']);
        unset($this->session->data['totals']);
    }

    public function failure()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/tamarapay');
        $this->load->language('extension/payment/tamarapay');

        if (isset($this->session->data['order_id'])) {
            $this->model_extension_payment_tamarapay->addOrderComment($this->session->data['order_id'], $this->config->get('payment_tamarapay_order_status_failure_id'), "Tamara - Pay failed", 0);
        }

        $this->session->data['error'] = $this->language->get('text_order_pay_failure');
        $this->response->redirect($this->url->link('checkout/cart', '', true));
    }

    public function cancel()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/tamarapay');
        $this->load->language('extension/payment/tamarapay');
        if (isset($this->session->data['order_id'])) {
            $this->model_extension_payment_tamarapay->addOrderComment($this->session->data['order_id'], $this->config->get('payment_tamarapay_order_status_canceled_id'), "Tamara - Pay canceled", 0);
        }

        $this->session->data['error'] = $this->language->get('text_order_canceled');
        $this->response->redirect($this->url->link('checkout/cart', '', true));
    }

    private function responseJson($response)
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    public function notification()
    {
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('extension/payment/tamarapay');
        $this->load->model('checkout/order');

        $tokenNotification = $this->config->get('payment_tamarapay_token_notification');

        $notificationService = NotificationService::create($tokenNotification);
        try {
            $authorise = $notificationService->processAuthoriseNotification();
        } catch (Exception $exception) {
            $this->log($exception->getMessage());
            $response = ['error' => $this->language->get('error_cannot_process_authorise_notification')];
            return $this->responseJson($response);
        }

        $orderId = $authorise->getOrderId();

        try {
            $this->model_extension_payment_tamarapay->getTamaraOrderByTamaraOrderId($orderId);
        } catch (Exception $exception) {
            $this->log($exception->getMessage());
            $response = ['error' => $this->language->get('error_not_found_order')];
            return $this->responseJson($response);
        }

        $response = $this->model_extension_payment_tamarapay->authoriseOrder($orderId);

        $this->responseJson($response);
    }

    public function log($data, $class_step = 6, $function_step = 6)
    {
        if ($this->config->get('payment_tamarapay_debug')) {
            $backtrace = debug_backtrace();
            $log = new Log('tamarapay.log');
            $log->write('(' . $backtrace[$class_step]['class'] . '::' . $backtrace[$function_step]['function'] . ') - ' . print_r($data,
                    true));
        }
    }

    public function handleOrderStatusChange($route, $args, $output)
    {
        if ($this->config->get('payment_tamarapay_trigger_actions_enabled')) {
            try {
                $this->load->model('extension/payment/tamarapay');
                $orderId = $args[0];
                $statusId = $args[1];
                $tamaraOrder = $this->model_extension_payment_tamarapay->getTamaraOrder($orderId);
                if ($statusId == $this->config->get('payment_tamarapay_capture_order_status_id')) {
                    $this->model_extension_payment_tamarapay->captureOrder($tamaraOrder['tamara_order_id']);
                }
                if ($statusId == self::ORDER_CANCELED_STATUS_ID) {
                    $this->model_extension_payment_tamarapay->cancelOrder($tamaraOrder['tamara_order_id']);
                }
            } catch (\Exception $exception) {
                $this->log($exception->getMessage());
            }
        } else {
            $this->log("Tamara trigger is disabled");
        }
    }

    public function addPromoWidgetForProduct($route, $data, $output) {
        $productId = $data['product_id'];
        $this->load->model('catalog/product');

        $productInfo = $this->model_catalog_product->getProduct($productId);
        if ($productInfo) {
            $this->load->model('extension/payment/tamarapay');
            $installmentsConfig = $this->model_extension_payment_tamarapay->getPayByInstallmentsConfig();
            $data['payByInstallmentsConfig'] = $installmentsConfig;
            $finalPrice = 0.00;
            if ((float)$productInfo['special']) {
                $finalPrice = $this->tax->calculate($productInfo['special'], $productInfo['tax_class_id'], $this->config->get('config_tax'));
            } else {
                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $finalPrice = $this->tax->calculate($productInfo['price'], $productInfo['tax_class_id'], $this->config->get('config_tax'));
                }
            }
            $canPayByInstallments = true;
            if ($finalPrice < floatval($installmentsConfig['min_limit'])) {
                $canPayByInstallments = false;
            }
            $str = "";
            if ($canPayByInstallments) {
                $str .= ('<div id="tamara-product-widget" class="tamara-product-widget" data-lang="" data-price="'.$finalPrice.'" data-currency="' .$installmentsConfig['currency']. '" data-payment-type="installment" data-installment-minimum-amount="' .$installmentsConfig['min_limit']. '" ></div>');
            } else {
                $str .= ('<div id="tamara-product-widget" class="tamara-product-widget" data-lang="" data-inject-template="true"></div>');
            }
            $str .= '<script charset="utf-8" src="https://cdn.tamara.co/widget/product-widget.min.js"></script> <script type="text/javascript">let langCode="'.$this->language->get('code').'";window.langCode=langCode;window.checkTamaraProductWidgetCount=0;document.getElementById("tamara-product-widget").setAttribute("data-lang",langCode);var existTamaraProductWidget=setInterval(function(){if(window.TamaraProductWidget){window.TamaraProductWidget.init({lang:window.langCode});window.TamaraProductWidget.render();clearInterval(existTamaraProductWidget);} window.checkTamaraProductWidgetCount+=1;if(window.checkTamaraProductWidgetCount>15){clearInterval(existTamaraProductWidget);}},300);</script>';
            $str = ("\n\n" . "<div class='tamara-promo' style='margin-bottom: 10px;'>" . $str . "</div>" . "\n\n");
            $positionToInsert = strpos($output, '<div id="product"');

            $output = substr_replace($output, $str, $positionToInsert, 0);
            return $output;
        }
    }
}
