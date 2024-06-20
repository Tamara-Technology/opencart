<?php
namespace Opencart\Catalog\Controller\Extension\Tamarapay\Payment;

use TMS\Tamara\Notification\NotificationService;
use TMS\Tamara\Request\Order\AuthoriseOrderRequest;

/**
 * Class Tamarapay
 *
 * @package
 */
class Tamarapay extends \Opencart\System\Engine\Controller
{
    const ORDER_STATUS_APPROVED = "approved";
    private const INDEX_TEMPLATE = "extension/tamarapay/payment/tamarapay";

    public function index()
    {
        $this->load->language('extension/tamarapay/payment/tamarapay');
        $this->load->model('extension/tamarapay/payment/tamarapay');

        if (!empty($this->session->data['tamara_methods_for_checkout_page'])) {
            $methods = $this->session->data['tamara_methods_for_checkout_page'];
        } else {
            $methods = $this->model_extension_tamarapay_payment_tamarapay->getPaymentMethodsForCheckoutPage();
        }
        if (empty($methods)) {
            return $this->renderIndexTemplate(['error_get_payment' => $this->language->get('error_no_method_available')]);
        }
        $data['single_checkout_enabled'] = $this->model_extension_tamarapay_payment_tamarapay->isSingleCheckoutVersion();
        $tmpArr = array_slice($methods, 0, 1);
        $firstMethod = array_shift($tmpArr);
        $data['first_method'] = $firstMethod;
        $data['is_none_validated_method'] = 0;
        foreach ($methods as $method) {
            if ($method['is_none_validated_method']) {
                $data['is_none_validated_method'] = 1;
                break;
            }
        }
        $data['methods'] = $methods;
        $data['merchant_public_key'] = $this->model_extension_tamarapay_payment_tamarapay->getMerchantPublicKey();
        $this->addExtraDataForCommonVersion($methods, $data);
        $data['is_sandbox_mode'] = $this->model_extension_tamarapay_payment_tamarapay->isSandboxMode();
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
        $orderData = $this->model_extension_tamarapay_payment_tamarapay->getOrder($this->model_extension_tamarapay_payment_tamarapay->getOrderIdFromSession());
        $data['order_data'] = $orderData;
        $data['language_code'] = $this->model_extension_tamarapay_payment_tamarapay->getLanguageCodeFromSession();
        $data['country_code'] = $this->model_extension_tamarapay_payment_tamarapay->getSupportedCountriesByCurrency($this->model_extension_tamarapay_payment_tamarapay->getCurrencyCodeFromSession())[0];
        $data['current_time'] = time();
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
        try {
            $this->load->language('extension/tamarapay/payment/tamarapay');
            $this->load->model('extension/tamarapay/payment/tamarapay');

            //validate data
            $orderData = $this->model_extension_tamarapay_payment_tamarapay->getOrder($this->model_extension_tamarapay_payment_tamarapay->getOrderIdFromSession());

          

            $countryCode = $this->model_extension_tamarapay_payment_tamarapay->getOrderCountryCode($orderData);
            if (empty($countryCode)) {
                throw new \Exception('Can not find the country code for your order, it is required');
            }
            $phoneNumber = $this->model_extension_tamarapay_payment_tamarapay->getCustomerPhoneNumberFromSession();
            if (empty($phoneNumber)) {
                $phoneNumber = $orderData['telephone'];
            }
            if (empty($phoneNumber)) {
                throw new \Exception('Can not find the phone number for your order, it is required. Please edit your account and update it');
            }
            if (!isset($this->request->post['payment_type'])) {
                throw new \Exception($this->language->get('error_missing_payment_type'));
            } else {
                $paymentType = $this->request->post['payment_type'];
            }
            if (!empty($this->request->post['is_none_validated_method'])) {
                $countryCode = strtoupper($countryCode);
                if (!$this->validatePaymentTypeSubmitted($paymentType, $countryCode, $orderData['total_in_currency'], $phoneNumber)) {
                    throw new \Exception('You are unable to checkout with this payment type, please choose another one!');
                }
            }

            //update payment method
            $this->updatePaymentMethodLabel($this->model_extension_tamarapay_payment_tamarapay->getOrderIdFromSession());

            $response = $this->model_extension_tamarapay_payment_tamarapay->createCheckout(
                $paymentType
            );

            return $this->responseJson($response);
        } catch (\Exception $exception) {
            return $this->responseJson(['error' => $exception->getMessage()]);
        }
    }

    private function validatePaymentTypeSubmitted($name, $countryCode, $orderValue, $phoneNumber)
    {
        $paymentOptionsAvailability = $this->model_extension_tamarapay_payment_tamarapay->checkPaymentOptionsAvailability($countryCode, $orderValue, $phoneNumber);
        if ($paymentOptionsAvailability['has_available_payment_options']) {
            if ($paymentOptionsAvailability['single_checkout_enabled']) {
                return true;
            } else {
                $availablePaymentTypes = $paymentOptionsAvailability['payment_types'];
                foreach ($availablePaymentTypes as $type) {
                    if ($type['name'] == $name) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function updatePaymentMethodLabel($orderId)
    {
        $data = [
            'code' => 'tamarapay.tamarapay',
            'name' => '<img class=\'payment-icon\' src=\'https://cdn.tamara.co/assets/svg/tamara-logo-badge-en.svg\' alt=\'Tamara\'>'
        ];
    
        $jsonEncodedData = $this->db->escape(json_encode($data));
    
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order` SET `payment_method` = '" . $jsonEncodedData . "' WHERE `order_id` = '" . (int)$orderId . "'"
        );
    }
    

    public function success()
    {
        $this->load->model('checkout/order');
        $this->load->language('extension/tamarapay/payment/tamarapay');
        $this->load->model('extension/tamarapay/payment/tamarapay');
        if (!isset($this->session->data['order_id'])) {
            if (!empty($this->request->get['orderId'])) {
                try {
                    $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrderByTamaraOrderId($this->request->get['orderId']);
                    $data['order_id'] = $tamaraOrder['order_id'];
                } catch (\Exception $exception) {
                    return $this->response->redirect($this->url->link('common/home', '', true));
                }
            } else {
                return $this->response->redirect($this->url->link('common/home', '', true));
            }
        } else {
            $data['order_id'] = $this->session->data['order_id'];
            if (!$this->model_extension_tamarapay_payment_tamarapay->isPayWithTamara($data['order_id'])) {
                return $this->redirectToCartPage();
            }
            $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrder($data['order_id']);
        }
        if (!$tamaraOrder['is_authorised']) {

            //set order status
            //$successStatusId = $this->config->get('payment_tamarapay_order_status_success_id');
            //$this->model_checkout_order->addHistory($data['order_id'], $successStatusId, "Tamara - Pay success", false);

            //call authorise
            $this->model_extension_tamarapay_payment_tamarapay->authoriseOrder($tamaraOrder['tamara_order_id']);
        }

        if (!empty($successUrl = $this->config->get('payment_tamarapay_checkout_success_url'))) {
            return $this->response->redirect($successUrl);
        }

        if (!$this->config->get('payment_tamarapay_enable_tamara_checkout_success_page')) {
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
            'href' => $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true)
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
            $data['text_message'] = sprintf(
                $this->language->get('text_customer'),
                $this->url->link('account/account', '', true),
                $this->url->link('account/order', '', true),
                $this->url->link('account/download', '', true),
                $this->url->link('information/contact')
            );
        } else {
            $data['text_message'] = sprintf(
                $this->language->get('text_guest'),
                $this->url->link('information/contact')
            );
        }

        $data['continue'] = $this->url->link('common/home');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $this->clearCheckoutSession();

        $this->response->setOutput($this->load->view('extension/tamarapay/payment/tamarapay_success', $data));
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
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->load->language('extension/tamarapay/payment/tamarapay');
        $orderMessage = "Tamara - Pay failed";
        $orderShowOnFront = $this->language->get('text_order_pay_failure');

        if (isset($this->session->data['order_id'])) {
            $orderId = $this->session->data['order_id'];
        } else {
            if (!empty($this->request->get['orderId'])) {
                try {
                    $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrderByTamaraOrderId($this->request->get['orderId']);
                    $orderId = $tamaraOrder['order_id'];
                } catch (\Exception $exception) {
                    return $this->redirectToCartPage();
                }
            } else {
                return $this->redirectToCartPage();
            }
        }
        if (!$this->model_extension_tamarapay_payment_tamarapay->isPayWithTamara($orderId)) {
            return $this->redirectToCartPage();
        }
        $failureStatusId = $this->config->get('payment_tamarapay_order_status_failure_id');
        $order = $this->model_extension_tamarapay_payment_tamarapay->getOrder($orderId);
        if ($order['order_status_id'] == $failureStatusId) {
            return $this->processRedirect('failure', $orderId, $failureStatusId, $orderMessage, $orderShowOnFront);
        }
        if (!$this->canCancelByRedirect($orderId)) {
            return $this->redirectToCartPage();
        }
        $this->processRedirect('failure', $orderId, $failureStatusId, $orderMessage, $orderShowOnFront);
    }

    public function cancel()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->load->language('extension/tamarapay/payment/tamarapay');
        $orderMessage = "Tamara - Payment canceled";
        $orderShowOnFront = $this->language->get('text_order_canceled');
        if (isset($this->session->data['order_id'])) {
			$orderId = $this->session->data['order_id'];
        } else {
            if (!empty($this->request->get['orderId'])) {
                try {
                    $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrderByTamaraOrderId($this->request->get['orderId']);
                    $orderId = $tamaraOrder['order_id'];
                } catch (\Exception $exception) {
                    return $this->redirectToCartPage();
                }
            } else {
                return $this->redirectToCartPage();
            }
        }

        if (!$this->model_extension_tamarapay_payment_tamarapay->isPayWithTamara($orderId)) {
            return $this->redirectToCartPage();
        }
        $cancelStatusId = $this->config->get('payment_tamarapay_order_status_canceled_id');
        $order = $this->model_extension_tamarapay_payment_tamarapay->getOrder($orderId);
        if ($order['order_status_id'] == $cancelStatusId) {
            return $this->processRedirect('cancel', $orderId, $cancelStatusId, $orderMessage, $orderShowOnFront);
        }
        if (!$this->canCancelByRedirect($orderId)) {
            return $this->redirectToCartPage();
        }
        $this->processRedirect('cancel', $orderId, $cancelStatusId, $orderMessage, $orderShowOnFront);
    }

    private function processRedirect($type, $orderId, $orderStatusId, $orderMessage, $messageShowOnFrontend)
    {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $redirectUrl = $this->config->get('payment_tamarapay_checkout_' . $type . '_url');
        if (!empty($redirectUrl)) {
            return $this->response->redirect($redirectUrl);
        }
        $this->redirectToCartPage($messageShowOnFrontend, 'error');
    }

    private function responseJson($response)
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
        return;
    }

    public function notification()
    {
        $this->load->language('extension/tamarapay/payment/tamarapay');
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->load->model('checkout/order');

        $tokenNotification = $this->config->get('payment_tamarapay_token_notification');

        $notificationService = NotificationService::create($tokenNotification);
        $authorise = $notificationService->processAuthoriseNotification();

        $tamaraOrderId = $authorise->getOrderId();

        try {
            $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrderByTamaraOrderId($tamaraOrderId, false, false);
            if (!$tamaraOrder['is_active']) {

                //deactivate others sessions
                $this->db->query(
                    sprintf(
                        "UPDATE `%s` SET `is_active` = '0' WHERE `order_id` = '%s'",
                        DB_PREFIX . 'tamara_orders',
                        $tamaraOrder['order_id']
                    )
                );

                //active this session
                $this->db->query(
                    sprintf(
                        "UPDATE `%s` SET `is_active` = '1' WHERE `tamara_order_id` = '%s'",
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
            $response = $this->model_extension_tamarapay_payment_tamarapay->authoriseOrder($tamaraOrderId);
            $this->responseJson($response);
        }
    }

    public function webhook()
    {
        return $this->responseJson(['status' => "success", 'message' => "Webhook no longer exists"]);
    }

    public function log($data, $class_step = 6, $function_step = 6)
    {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->model_extension_tamarapay_payment_tamarapay->log($data, $class_step, $function_step);
    }

  

    /**
     * @param $message
     * @param $type
     */
    
    public function redirectToCartPage($message = null, $type = null)
    {
        if ($message !== null) {
            $this->session->data[$type] = $message;
        }
        $this->response->redirect($this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true));
    }

    public function canCancelByRedirect($orderId)
    {
        try {
            $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrder($orderId);
            if ($tamaraOrder['is_authorised']) {
                return false;
            } else {
                $remoteOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrderFromRemoteByTamaraOrderId($tamaraOrder['tamara_order_id']);
                if ($remoteOrder === null) {
                    return false;
                }
                if ($remoteOrder->getStatus() == "approved") {
                    return false;
                }
            }
            return true;
        } catch (\Exception $exception) {
            $this->log(["Exception when canCancelByRedirect " . $exception->getMessage()]);
        }
        return false;
    }

    private function addExtraDataForCommonVersion($methods, &$data)
    {
        $totalTypeAvailable = 0;
        $numberOfInstallments = [];
        $methodsNameInWidget = [];
        $existsPayLaterOrPayNextMonth = false;
        $existsPayInX = false; //number of installments > 4
        $existsPayByInstallments = false; //number of installments <= 4
        $existsPayNow = false;
        $minLimitAllMethods = null;
        $totalTypeAvailableForWidget = 0;
        $totalInstallmentTypes = 0;
        foreach ($methods as $method) {
            if ($method['is_in_limit']) {
                $totalTypeAvailable++;
                if ($minLimitAllMethods === null) {
                    $minLimitAllMethods = $method['min_limit'];
                } else {
                    if ($method['min_limit'] < $minLimitAllMethods) {
                        $minLimitAllMethods = $method['min_limit'];
                    }
                }
                if ($method['is_installment']) {
                    $totalInstallmentTypes++;
                    $totalTypeAvailableForWidget++;
                    $methodsNameInWidget[] = "installment";
                    $numberOfInstallments[] = $method['number_of_instalments'];
                    if (intval($method['number_of_instalments']) > 4) {
                        $existsPayInX = true;
                    } else {
                        $existsPayByInstallments = true;
                    }
                } else {
                    if ($method['name'] == "pay_by_later") {
                        $totalTypeAvailableForWidget++;
                        $methodsNameInWidget[] = "paylater";
                        $existsPayLaterOrPayNextMonth = true;
                    } else {
                        if ($method['name'] == "pay_next_month") {
                            $totalTypeAvailableForWidget++;
                            $methodsNameInWidget[] = "pay-next-month";
                            $existsPayLaterOrPayNextMonth = true;
                        } else {
                            if ($this->model_extension_tamarapay_payment_tamarapay->isPayNowPayment($method['name'])) {
                                $existsPayNow = true;
                            }
                        }
                    }
                }
            }
        }
        $data['total_method_available'] = $totalTypeAvailable;
        $data['number_of_installments'] = implode(";", $numberOfInstallments);
        $data['methods_name_in_widget'] = implode(";", array_unique($methodsNameInWidget));
        $data['exists_pay_later_or_pay_next_month'] = $existsPayLaterOrPayNextMonth;
        $data['exists_pay_in_x'] = $existsPayInX;
        $data['exists_pay_by_installments'] = $existsPayByInstallments;
        $data['min_limit_all_methods'] = $minLimitAllMethods;
        $data['total_installments_types'] = $totalInstallmentTypes;
        if ($existsPayNow) {
            $data['exists_pay_now'] = true;
            if ($totalTypeAvailable == 1) {
                $data['only_pay_now'] = true;
            }
        } else {
            $data['exists_pay_now'] = false;
            $data['only_pay_now'] = false;
        }
        $data['use_widget_version'] = 'mixed';
        if (empty($data['merchant_public_key'])) {
            $data['use_widget_version'] = 'v1';
        } else {
            if ($data['single_checkout_enabled']) {
                $data['use_widget_version'] = 'v2';
            }
            if ($totalTypeAvailable < 2) {
                $data['use_widget_version'] = 'v2';
            }
        }
    }
}