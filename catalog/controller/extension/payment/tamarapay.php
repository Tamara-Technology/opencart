<?php

use TMS\Tamara\Notification\NotificationService;
use TMS\Tamara\Request\Order\AuthoriseOrderRequest;

class ControllerExtensionPaymentTamarapay extends Controller
{
    const ORDER_STATUS_APPROVED = "approved";
    private const INDEX_TEMPLATE = "extension/payment/tamarapay";

    public function index()
    {
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('extension/payment/tamarapay');

        //validate currency
        if (!$this->model_extension_payment_tamarapay->isCurrencySupported()) {
            return $this->renderIndexTemplate(['error_get_payment' => $this->language->get('error_wrong_currency')]);
        }
        $data['single_checkout_enabled'] = $this->model_extension_payment_tamarapay->isSingleCheckoutVersion();
        if (!empty($this->session->data['methods_for_checkout_page'])) {
            $methods = $this->session->data['methods_for_checkout_page'];
        } else {
            $methods = $this->model_extension_payment_tamarapay->getPaymentMethodsForCheckoutPage();
        }
        $data['error_no_method_available'] = $this->language->get('error_no_method_available');
        if (empty($methods)) {
            return $this->renderIndexTemplate(['error_get_payment' => $data['error_no_method_available']]);
        }
        $paymentAvailableForCurrency = $this->getCurrencyFromPaymentMethods($methods);
        $data['exists_pay_now'] = $this->model_extension_payment_tamarapay->isExistPayNow($methods);
        if ($data['single_checkout_enabled']) {
            $this->addExtraDataForSingleCheckoutVersion($methods, $data);
        } else {
            $this->addExtraDataForCommonVersion($methods, $data);
        }
        $data['methods'] = $methods;
        $data['merchant_public_key']  = $this->model_extension_payment_tamarapay->getMerchantPublicKey();
//        $data['is_use_widget_v1'] = empty($data['merchant_public_key']);
//        if ($data['total_method_available'] > 1) {
//            $data['is_use_widget_v1'] = true;
//        }
        $data['is_use_widget_v1'] = true;
        $data['use_iframe_checkout'] = false;
        $data['merchant_urls'] = $this->model_extension_payment_tamarapay->getMerchantUrls();
        $data['is_sandbox_mode'] = $this->model_extension_payment_tamarapay->isSandboxMode();
        $data['tamara_widget_url'] = 'https://cdn.tamara.co/widget/tamara-widget.min.js';
        $data['tamara_product_widget_url'] = 'https://cdn.tamara.co/widget/product-widget.min.js';
        $data['tamara_installments_plan_widget_url'] = 'https://cdn.tamara.co/widget/installment-plan.min.js';
        $data['information_widget_v2_url'] = 'https://cdn.tamara.co/widget-v2/tamara-widget.js';
        if ($data['is_sandbox_mode']) {
            $data['tamara_widget_url'] = 'https://cdn-sandbox.tamara.co/widget/tamara-widget.min.js';
            $data['tamara_product_widget_url'] = 'https://cdn-sandbox.tamara.co/widget/product-widget.min.js';
            $data['tamara_installments_plan_widget_url'] = 'https://cdn-sandbox.tamara.co/widget/installment-plan.min.js';
            $data['information_widget_v2_url'] = 'https://cdn-sandbox.tamara.co/widget-v2/tamara-widget.js';
        }
        $orderData = $this->model_extension_payment_tamarapay->getOrder( $this->model_extension_payment_tamarapay->getOrderIdFromSession());
        if (strtoupper($orderData['currency_code']) != $paymentAvailableForCurrency) {
            return $this->renderIndexTemplate(['error_get_payment' => $this->language->get('error_wrong_currency')]);
        }
        $data['order_data'] = $orderData;
        $data['language_code'] = $this->language->get('code');
        if (empty($data['language_code'])) {
            $data['language_code'] = 'en';
        }
        $data['country_code'] = $this->model_extension_payment_tamarapay->getSupportedCountriesByCurrency($paymentAvailableForCurrency)[0];
        $data['current_time'] = time();
        $data['text_choose_payment'] = $this->language->get('text_choose_payment');
        $data['text_min_amount'] = $this->language->get('text_min_amount');
        $data['text_max_amount'] = $this->language->get('text_max_amount');
        $data['text_under_over_limit'] = $this->language->get('text_under_over_limit');
        $data['text_more_details'] = $this->language->get('text_more_details');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['error_get_payment'] = null;
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

        $paymentType = "";
        if ($this->model_extension_payment_tamarapay->isSingleCheckoutVersion()){
            if (!isset($this->request->post['payment_type'])){
                $paymentType = "single_checkout";
            } else {
                $paymentType = $this->request->post['payment_type'];
            }
        } else {
            $error = [];

            if (!isset($this->request->post['payment_type'])) {
                $error = ['error' => $this->language->get('error_missing_payment_type')];
            } else {
                $paymentType = $this->request->post['payment_type'];
            }

            if (!empty($error)) {
                return $this->responseJson($error);
            }
        }

        //update payment method
        $this->updatePaymentMethodLabel($this->model_extension_payment_tamarapay->getOrderIdFromSession());

        $response = $this->model_extension_payment_tamarapay->createCheckout(
            $paymentType);

        return $this->responseJson($response);
    }

    private function updatePaymentMethodLabel($orderId) {
        $this->db->query(sprintf("UPDATE `%s` SET `payment_method` = '%s' WHERE `order_id` = '%s'",
            DB_PREFIX . 'order',
            '<img class="payment-icon" src="https://cdn.tamara.co/assets/svg/tamara-logo-badge-en.svg" alt="Tamara">',
            $orderId
        ));
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
        if (!$this->model_extension_payment_tamarapay->isPayWithTamara($data['order_id'])) {
            return $this->redirectToCartPage();
        }
        $tamaraOrder = $this->model_extension_payment_tamarapay->getTamaraOrder($data['order_id']);
        if (!$tamaraOrder['is_authorised']) {

            //set order status
            $successStatusId = $this->config->get('tamarapay_order_status_success_id');
            $this->model_checkout_order->addOrderHistory($data['order_id'], $successStatusId, "Tamara - Pay success", false);

            //call authorise
            $this->model_extension_payment_tamarapay->authoriseOrder($tamaraOrder['tamara_order_id']);
        }

        $this->model_extension_payment_tamarapay->updatePaymentTypeAfterCheckout($tamaraOrder);

        if (!empty($successUrl = $this->config->get('tamarapay_checkout_success_url'))) {
            return $this->response->redirect($successUrl);
        }

        if (!$this->config->get('tamarapay_enable_tamara_checkout_success_page')) {
            return $this->response->redirect($this->url->link('checkout/success', '', true));
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
        $data['heading_title'] = $this->language->get('heading_title');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['button_continue'] = $this->language->get('button_continue');
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
        $orderMessage = "Tamara - Pay failed";
        $orderShowOnFront = $this->language->get('text_order_pay_failure');

        if (isset($this->session->data['order_id'])) {
            $orderId = $this->session->data['order_id'];
            if (!$this->model_extension_payment_tamarapay->isPayWithTamara($orderId)) {
                return $this->redirectToCartPage();
            }
            $failureStatusId = $this->config->get('tamarapay_order_status_failure_id');
            $order = $this->model_extension_payment_tamarapay->getOrder($orderId);
            if ($order['order_status_id'] == $failureStatusId) {
                return $this->processRedirect('failure', $orderId, $failureStatusId, $orderMessage, $orderShowOnFront);
            }
            if (!$this->canCancelByRedirect($orderId)) {
                return $this->redirectToCartPage();
            }
            return $this->processRedirect('failure', $orderId, $failureStatusId, $orderMessage, $orderShowOnFront);
        }

        $this->redirectToCartPage($this->language->get('text_order_pay_failure'), 'error');
    }

    public function cancel()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/tamarapay');
        $this->load->language('extension/payment/tamarapay');
        $orderMessage = "Tamara - Pay canceled";
        $orderShowOnFront = $this->language->get('text_order_canceled');
        if (isset($this->session->data['order_id'])) {
            $orderId = $this->session->data['order_id'];
            if (!$this->model_extension_payment_tamarapay->isPayWithTamara($orderId)) {
                return $this->redirectToCartPage();
            }
            $cancelStatusId = $this->config->get('tamarapay_order_status_canceled_id');
            $order = $this->model_extension_payment_tamarapay->getOrder($orderId);
            if ($order['order_status_id'] == $cancelStatusId) {
                return $this->processRedirect('cancel', $orderId, $cancelStatusId, $orderMessage, $orderShowOnFront);
            }
            if (!$this->canCancelByRedirect($orderId)) {
                return $this->redirectToCartPage();
            }
            return $this->processRedirect('cancel', $orderId, $cancelStatusId, $orderMessage, $orderShowOnFront);
        }

        $this->redirectToCartPage($orderShowOnFront, 'error');
    }

    private function processRedirect($type, $orderId, $orderStatusId, $orderMessage, $messageShowOnFrontend) {
        $this->load->model('extension/payment/tamarapay');
        $redirectUrl = $this->config->get('tamarapay_checkout_' . $type . '_url');
        if (!empty($redirectUrl)) {
            return $this->response->redirect($redirectUrl);
        }
        return $this->redirectToCartPage($messageShowOnFrontend, 'error');
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

        $tokenNotification = $this->config->get('tamarapay_token_notification');

        $notificationService = NotificationService::create($tokenNotification);
        $authorise = $notificationService->processAuthoriseNotification();

        $tamaraOrderId = $authorise->getOrderId();

        try {
            $tamaraOrder = $this->model_extension_payment_tamarapay->getTamaraOrderByTamaraOrderId($tamaraOrderId, false, false);
            if (!$tamaraOrder['is_active']) {

                //deactivate others sessions
                $this->db->query(sprintf("UPDATE `%s` SET `is_active` = '0' WHERE `order_id` = '%s'",
                    DB_PREFIX . 'tamara_orders',
                    $tamaraOrder['order_id']
                ));

                //active this session
                $this->db->query(
                    sprintf("UPDATE `%s` SET `is_active` = '1' WHERE `tamara_order_id` = '%s'",
                        DB_PREFIX . 'tamara_orders',
                        $tamaraOrderId
                    )
                );
            }
        } catch (Exception $exception) {
            $this->log($exception->getMessage());
            $response = ['error' => $this->language->get('error_not_found_order')];
            return $this->responseJson($response);
        }

        if (!$tamaraOrder['is_authorised']) {
            $response = $this->model_extension_payment_tamarapay->authoriseOrder($tamaraOrderId);
            $this->responseJson($response);
        }
    }

    public function webhook() {
        $this->log(['Start to webhook']);
        $response = ['status' => "success", 'success' => "Webhook processed"];
        try {
            $this->load->model('extension/payment/tamarapay');
            $this->model_extension_payment_tamarapay->webhook();
        } catch (\Exception $exception) {
            $this->log(["Error when execute webhook: " . $exception->getMessage()]);
            $response = ['status' => 'error', 'error' => $exception->getMessage()];
        }
        $this->log(['End Webhook']);
        return $this->responseJson($response);
    }

    public function log($data, $class_step = 6, $function_step = 6)
    {
        $this->load->model('extension/payment/tamarapay');
        $this->model_extension_payment_tamarapay->log($data, $class_step, $function_step);
    }

    public function handleOrderStatusChange($route, $args, $output)
    {
        $this->log(["Tamara - function handleOrderStatusChange"]);
        if ($this->config->get('tamarapay_trigger_actions_enabled')) {
            try {
                $this->load->model('extension/payment/tamarapay');
                $orderId = $args[0];
                if (!$this->model_extension_payment_tamarapay->isPayWithTamara($orderId)) {
                    return;
                }
                $statusId = $args[1];
                $this->log([
                    "event" => "handleOrderStatusChange",
                    "order id" => $orderId,
                    "status id" => $statusId
                ]);
                $tamaraOrder = $this->model_extension_payment_tamarapay->getTamaraOrder($orderId);
                if ($statusId == $this->config->get('tamarapay_capture_order_status_id')) {
                    $this->log(["Tamara - handleOrderStatusChange - will go to capture, order id: " . $orderId . ", tamara order id: " . $tamaraOrder['tamara_order_id']]);
                    $this->model_extension_payment_tamarapay->captureOrder($tamaraOrder['tamara_order_id']);
                    return;
                }
                if ($statusId == $this->config->get('tamarapay_cancel_order_status_id')) {
                    $this->log(["Tamara - handleOrderStatusChange - will go to cancel, order id: " . $orderId . ", tamara order id: " . $tamaraOrder['tamara_order_id']]);
                    $this->model_extension_payment_tamarapay->cancelOrder($tamaraOrder['tamara_order_id']);
                    return;
                }
                $this->log(["Tamara - handleOrderStatusChange - not processing, order id: " . $orderId . ", tamara order id: " . $tamaraOrder['tamara_order_id']]);
            } catch (\Exception $exception) {
                $this->log(["handleOrderStatusChange - Exception when capture or cancel order"]);
                $this->log($exception->getMessage());
            }
        } else {
            $this->log("Tamara trigger is disabled");
        }
    }

    public function addPromoWidgetForProduct($route, $data, $output) {
        preg_match('/<div .*id="product"/', $output, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0][1])) {
            return $output;
        }
        if (!$this->config->get("tamarapay_status")) {
            return $output;
        }
        if (empty($data['product_id'])) {
            return $output;
        }
        $productId = $data['product_id'];
        $excludeProductIds = explode(",",strval($this->config->get('tamarapay_pdp_wg_exclude_product_ids')));
        if (in_array($productId, $excludeProductIds)) {
            return $output;
        }
        $excludeCategoryIds = explode(",",strval($this->config->get('tamarapay_pdp_wg_exclude_category_ids')));
        $productCategories = [];
        if (isset($this->request->get['path'])) {
            $productCategories = explode("_", $this->request->get['path']);
        }

        $this->load->model('catalog/product');
        if (!empty(array_intersect($productCategories, $excludeCategoryIds))) {
            return $output;
        }

        $productInfo = $this->model_catalog_product->getProduct($productId);
        if ($productInfo) {
            $this->load->model('extension/payment/tamarapay');
            $finalPrice = $this->model_extension_payment_tamarapay->getValueInCurrency($this->tax->calculate($productInfo['price'], $productInfo['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            if ((float)$productInfo['special']) {
                $finalPrice = $this->model_extension_payment_tamarapay->getValueInCurrency($this->tax->calculate($productInfo['special'], $productInfo['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            }
            $widgetHtml = $this->getWidgetHtml($finalPrice);
            if (!empty($widgetHtml)) {
                $output = substr_replace($output, $widgetHtml, $matches[0][1], 0);
            }
        }
        return $output;
    }

    public function addPromoWidgetForCartPage($route, $data, $output) {
        preg_match('/<div .*class="buttons clearfix"/', $output, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0][1])) {
            return $output;
        }
        if (!$this->config->get("tamarapay_status")) {
            return $output;
        }
        $excludeProductIds = explode(",",strval($this->config->get('tamarapay_pdp_wg_exclude_product_ids')));
        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            if (in_array($product['product_id'], $excludeProductIds)) {
                return $output;
            }
        }
        $this->load->model('extension/payment/helper/tamara_opencart');
        $cartValue = $this->model_extension_payment_helper_tamara_opencart->getTotalAmountInCurrency();
        if (empty($cartValue)) {
            return $output;
        }
        $widgetHtml = $this->getWidgetHtml($cartValue);
        if (!empty($widgetHtml)) {
            $widgetHtml .= '<style>#tamara_promo_widget {text-align: right;}</style>';
            $output = substr_replace($output, $widgetHtml, $matches[0][1], 0);
        }
        return $output;
    }

    public function getWidgetHtml($price) {
        $result = '';
        $this->load->model('extension/payment/tamarapay');
        $bestMethodForCustomer = $this->model_extension_payment_tamarapay->getBestMethodForCustomer($price, $this->model_extension_payment_tamarapay->getCurrencyCodeFromSession());
        if (empty($bestMethodForCustomer)) {
            return $result;
        }

        //list of the payment types which does not have the widget
        if (in_array($bestMethodForCustomer['name'], ['pay_now'])) {
            return $result;
        }

        $countryCode = $this->model_extension_payment_tamarapay->getSupportedCountriesByCurrency($bestMethodForCustomer['currency'])[0];
        $languageCode = $this->language->get('code');
        if (empty($languageCode)) {
            $languageCode = 'en';
        }
        $publicKey = $this->config->get('tamarapay_merchant_public_key');
        $isUseWidgetV1 = empty($publicKey);
        if ($isUseWidgetV1) {
            $result = '<div id="tamara-product-widget" class="tamara-product-widget" data-lang="'. $languageCode .'" data-price="'. $price .'" data-currency="'. $bestMethodForCustomer['currency'] .'" data-country-code="'. $countryCode .'" data-installment-available-amount="'. $bestMethodForCustomer['min_limit'] .'"';
            if ($this->model_extension_payment_tamarapay->isInstallmentsPayment($bestMethodForCustomer['name'])) {
                $result .= (' data-payment-type="installment" data-number-of-installments="'.$bestMethodForCustomer['number_of_instalments'].'" data-installment-minimum-amount="' .$bestMethodForCustomer['min_limit']. '" data-installment-maximum-amount="'.$bestMethodForCustomer['max_limit'].'" data-disable-paylater="true"></div>');
            } else {
                if ($bestMethodForCustomer['name'] == "pay_by_later") {
                    $result .= (' data-payment-type="paylater" data-disable-paylater="false" data-disable-product-limit="true" data-disable-installment="true" data-pay-later-max-amount="'. $bestMethodForCustomer['max_limit'] .'"></div>');
                } else {
                    if ($bestMethodForCustomer['name'] == "pay_next_month") {
                        $result .= (' data-payment-type="pay-next-month" data-disable-paylater="true" data-disable-installment="false"></div>');
                    }
                }
            }

            $productWidgetUrl = "https://cdn.tamara.co/widget/product-widget.min.js";
            if ($this->model_extension_payment_tamarapay->isSandboxMode()) {
                $productWidgetUrl = "https://cdn-sandbox.tamara.co/widget/product-widget.min.js";
            }
            $result .= '
            <script type="text/javascript">
                window.tamara = [];
                window.langCode = "'.$languageCode.'";
                window.tamara.currencyCode = "'.$bestMethodForCustomer["currency"].'";
                window.checkTamaraProductWidgetCount = 0;
                window.tamara.widgetPublicKey = "'.$this->config->get("tamarapay_merchant_public_key").'";
                document.getElementById("tamara-product-widget").setAttribute("data-lang", window.langCode);
                var existTamaraProductWidget = setInterval(function () {
                    if (window.TamaraProductWidget) {
                        window.TamaraProductWidget.init({ lang: window.tamara.langCode, currency: window.tamara.currencyCode});
                        window.TamaraProductWidget.render();
                        clearInterval(existTamaraProductWidget);
                    }
                    window.checkTamaraProductWidgetCount += 1;
                    if (window.checkTamaraProductWidgetCount > 33) {
                        clearInterval(existTamaraProductWidget);
                    }
                }, 300);
            </script>
            <script charset="utf-8" defer src="'. $productWidgetUrl .'?t='.time().'"></script>
            ';
        } else {
            if ($this->model_extension_payment_tamarapay->isSandboxMode()) {
                $widgetUrl = "https://cdn-sandbox.tamara.co/widget-v2/tamara-widget.js";
            } else {
                $widgetUrl = "https://cdn.tamara.co/widget-v2/tamara-widget.js";
            }
            $result = '<tamara-widget id="tamara_promo_widget" type="tamara-summary" amount="' . $price . '" inline-type="2"></tamara-widget>';
            $result .= ('<script>
                    var tamaraWidgetConfig = {
                        lang: "'. $languageCode .'",
                        country: "'. $countryCode .'",
                        publicKey: "'. $publicKey .'"
                    }
                    </script>
                    <script charset="utf-8" defer src="'. $widgetUrl .'?t='.time().'"></script>'
            );
        }
        return ('<div class="tamara-promo" style="margin-bottom: 10px;">' . $result . '</div>');
    }

    /**
     * @param $message
     * @param $type
     */
    public function redirectToCartPage($message = null, $type = null) {
        if ($message !== null) {
            $this->session->data[$type] = $message;
        }
        return $this->response->redirect($this->url->link('checkout/cart', '', true));
    }

    public function canCancelByRedirect($orderId) {
        try {
            $tamaraOrder = $this->model_extension_payment_tamarapay->getTamaraOrder($orderId);
            if ($tamaraOrder['is_authorised']) {
                return false;
            } else {
                if ($this->model_extension_payment_tamarapay->getTamaraOrderFromRemoteByTamaraOrderId($tamaraOrder['tamara_order_id'])->getStatus() == "approved") {
                    return false;
                }
            }
            return true;
        } catch (\Exception $exception) {
            $this->log(["Exception when canCancelByRedirect " . $exception->getMessage()]);
        }
        return false;
    }

    private function addExtraDataForSingleCheckoutVersion($methods, &$data) {
        $orderValue = $this->model_extension_payment_tamarapay->getOrderTotalFromSession();
        $countryCode = $this->model_extension_payment_tamarapay->getCustomerCountryCodeFromSession();
        if ($this->customer->isLogged()) {
            $phoneNumber = $this->customer->getTelephone();
        } else {
            $phoneNumber = $this->session->data['guest']['telephone'];
        }
        $data['single_checkout_available_for_this_customer'] = $this->model_extension_payment_tamarapay->isCustomerHasAvailablePaymentOptions($countryCode, $orderValue, $phoneNumber);
        $totalTypeAvailable = 0;
        $payNowInLimit = false;
        if ($data['exists_pay_now']) {
            foreach ($methods as $method) {
                if ($this->model_extension_payment_tamarapay->isPayNowPayment($method['name'])) {
                    if ($method['is_in_limit']){
                        $payNowInLimit = true;
                    }
                }
            }
            if ($payNowInLimit) {
                $totalTypeAvailable++;
            }
        }
        if ($data['single_checkout_available_for_this_customer']) {
            $totalTypeAvailable++;
        }
        $data['single_checkout_payment_title'] = $this->model_extension_payment_tamarapay->getPaymentTitle($methods);
        $data['total_method_available'] = $totalTypeAvailable;
    }

    private function addExtraDataForCommonVersion($methods, &$data) {
        $totalTypeAvailable = 0;
        $numberOfInstallments = [];
        $methodsNameInWidget = [];
        $existsPayLaterOrPayNextMonth = false;
        $existsPayInX = false; //number of installments > 4
        $existsPayByInstallments = false; //number of installments <= 4
        $existsPayNow = false;
        $minLimitAllMethods = null;
        foreach ($methods as $method) {
            $isInstallments = false;
            if ($this->model_extension_payment_tamarapay->isInstallmentsPayment($method['name'])) {
                $isInstallments = true;
            }
            if ($method['is_in_limit']) {
                $totalTypeAvailable++;
                if ($minLimitAllMethods === null) {
                    $minLimitAllMethods = $method['min_limit'];
                } else {
                    if ($method['min_limit'] < $minLimitAllMethods) {
                        $minLimitAllMethods = $method['min_limit'];
                    }
                }
                if ($isInstallments) {
                    $methodsNameInWidget[] = "installment";
                    $numberOfInstallments[] = $method['number_of_instalments'];
                    if (intval($method['number_of_instalments']) > 4) {
                        $existsPayInX = true;
                    } else {
                        $existsPayByInstallments = true;
                    }
                } else {
                    if ($method['name'] == "pay_by_later") {
                        $methodsNameInWidget[] = "paylater";
                        $existsPayLaterOrPayNextMonth = true;
                    } else {
                        if ($method['name'] == "pay_next_month") {
                            $methodsNameInWidget[] = "pay-next-month";
                            $existsPayLaterOrPayNextMonth = true;
                        } else {
                            if ($this->model_extension_payment_tamarapay->isPayNowPayment($method['name'])) {
                                $existsPayNow = true;
                            }
                        }
                    }
                }
            }
        }
        $data['total_method_available'] = $totalTypeAvailable;
        $data['number_of_installments'] = implode(";" , $numberOfInstallments);
        $data['methods_name_in_widget'] = implode(";", array_unique($methodsNameInWidget));
        $data['exists_pay_later_or_pay_next_month'] = $existsPayLaterOrPayNextMonth;
        $data['exists_pay_in_x'] = $existsPayInX;
        $data['exists_pay_by_installments'] = $existsPayByInstallments;
        $data['min_limit_all_methods'] = $minLimitAllMethods;
        if ($existsPayNow && $totalTypeAvailable == 1) {
            $data['only_pay_now'] = true;
        } else {
            $data['only_pay_now'] = false;
        }
    }

    public function getCurrencyFromPaymentMethods($methods) {
        foreach ($methods as $method) {
            return $method['currency'];
        }
    }
}
