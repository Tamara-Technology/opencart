<?php

namespace Opencart\Catalog\Model\Extension\Tamarapay\Payment;


use TMS\Tamara\Model\Checkout\PaymentType;
use TMS\Tamara\Client;
use TMS\Tamara\Configuration;
use TMS\Tamara\Model\Money;
use TMS\Tamara\Model\Order\Address;
use TMS\Tamara\Model\Order\Consumer;
use TMS\Tamara\Model\Order\Discount;
use TMS\Tamara\Model\Order\MerchantUrl;
use TMS\Tamara\Model\Order\Order;
use TMS\Tamara\Model\Order\OrderItem;
use TMS\Tamara\Model\Order\OrderItemCollection;
use TMS\Tamara\Model\Payment\Capture;
use TMS\Tamara\Model\ShippingInfo;
use TMS\Tamara\Request\Checkout\CreateCheckoutRequest;
use TMS\Tamara\Request\Order\AuthoriseOrderRequest;
use TMS\Tamara\Request\Order\CancelOrderRequest;
use TMS\Tamara\Request\Payment\CaptureRequest;
use TMS\Tamara\Response\Payment\CancelResponse;
use DateTimeZone;
use DateTime;
use Exception;
use InvalidArgumentException;
use DateTimeImmutable;



class Tamarapay extends \Opencart\System\Engine\Model 
{
    /**
     * Define version of extension
     */
    public const VERSION = '1.8.9';

    public const
        MAX_LIMIT = 'max_limit',
        MIN_LIMIT = 'min_limit',
        PAY_LATER = 'PAY_BY_LATER',
        PAY_BY_INSTALMENTS = 'PAY_BY_INSTALMENTS',
        PAY_NEXT_MONTH = 'PAY_NEXT_MONTH',
        PAY_NOW = 'PAY_NOW',
        PLATFORM = 'OpenCart',
        EMPTY_STRING = 'N/A',
        MAXIMUM_CACHED = 500;
    const PAY_LATER_CODE = 'pay_by_later';
    const PAY_BY_INSTALMENTS_CODE = 'pay_by_instalments';
    const PAY_NEXT_MONTH_CODE = 'pay_next_month';
    const PAY_NOW_CODE = 'pay_now';
    const SINGLE_CHECKOUT_CODE = 'single_checkout';
    const ALLOWED_WEBHOOKS = ['order_expired', 'order_declined'];
    const PAYMENT_TYPES_CACHE_LIFE_TIME = 86400; //1day
    const DISABLE_TAMARA_CACHE_IDENTIFIER = "disable_tamara";
    const DISABLE_TAMARA_CACHE_LIFE_TIME = 900;
    const SANDBOX_API_URL = "https://api-sandbox.tamara.co";
    const SANDBOX_API_ENVIRONMENT = "1";
    const PRODUCTION_API_URL = "https://api.tamara.co";
    const PRODUCTION_API_ENVIRONMENT = "2";
    const ORDER_FAILED_STATUSES = [
        'Canceled', 'Denied', 'Canceled Reversal', 'Failed', 'Refunded', 'Reversed', 'Chargeback', 'Pending', 'Voided', 'Expired',
        'ملغي', 'مرفوض', 'إلغاء عكس الطلب', 'فشل', 'مردود', 'تم عكس الطلب', 'إعادة المبلغ', 'معلق', 'الطلب باطل', 'انتهاء الوقت'
    ];
    const ORDER_DELIVERED_STATUSES = [
        'Shipped', 'Complete', 'تم شحن الطلب', 'مكتمل'
    ];

    const SUPPORTED_CURRENCIES = [
        'SAR', 'AED', 'KWD', 'BHD', 'QAR', 'OMR'
    ];
    const SUPPORTED_COUNTRIES = [
        'SA', 'AE', 'KW', 'BH', 'QA', 'OM'
    ];

    const TAMARA_PAYMENT_CODE = 'tamarapay.tamarapay';

    const CURRENCIES_COUNTRIES_ALLOWED = [
        'SAR' => 'SA',
        'AED' => 'AE',
        'KWD' => 'KW',
        'BHD' => 'BH',
        'QAR' => 'QA',
        'OMR' => 'OM'
    ];

    const API_REQUEST_TIMEOUT = 30; //in seconds

    private $orders = [];
    private $tamaraOrders = [];
    private $tamaraOrdersByOrderId = [];
    private $orderTotalsArr = [];
    private $currencies = [];
    private $paymentTypesCache = [];
    private $disableTamara = null;
    private $orderTotal = null;

    public function getMethods(array $address = []): array {

        $methodData = [];
        $this->load->language('extension/tamarapay/payment/tamarapay');


        if (!$this->isTamaraEnabled()) {
            return [];
        }
        if ($this->getDisableTamara()) {
            return [];
        }
        if (!$this->isTamaraAvailableForThisCustomer()) {
            return [];
        }
        if (!$this->isCurrencySupported()) {
            return [];
        }
        if (!$this->validateCartItems()) {
            return [];
        }
        if (!empty($this->session->data['shipping_address']['iso_code_2'])) {
            $address['iso_code_2'] = $this->session->data['shipping_address']['iso_code_2'];
        }
        if (empty($address['iso_code_2'])) {
            $address['iso_code_2'] = "";
        } else {
            $address['iso_code_2'] = strtoupper(strval($address['iso_code_2']));
        }
        if (!$this->validateTamaraPaymentByAddress($address)) {
            return [];
        }

        //get methods for checkout page and save it to session
        $availableMethods = $this->getPaymentMethodsForCheckoutPage();
        $this->session->data['tamara_methods_for_checkout_page'] = $availableMethods;
        return $this->getTamaraMethodData($availableMethods);
	}
	

    public function getMethod($address, $total)
    {
        if (!$this->isTamaraEnabled()) {
            return [];
        }
        if ($this->getDisableTamara()) {
            return [];
        }
        if (!$this->isTamaraAvailableForThisCustomer()) {
            return [];
        }
        if (!$this->isCurrencySupported()) {
            return [];
        }
        if (!$this->validateCartItems()) {
            return [];
        }
        if (!empty($this->session->data['shipping_address']['iso_code_2'])) {
            $address['iso_code_2'] = $this->session->data['shipping_address']['iso_code_2'];
        }
        if (empty($address['iso_code_2'])) {
            $address['iso_code_2'] = "";
        } else {
            $address['iso_code_2'] = strtoupper(strval($address['iso_code_2']));
        }
        if (!$this->validateTamaraPaymentByAddress($address)) {
            return [];
        }

        //get methods for checkout page and save it to session
        $availableMethods = $this->getPaymentMethodsForCheckoutPage();
        $this->session->data['tamara_methods_for_checkout_page'] = $availableMethods;
        return $this->getTamaraMethodData($availableMethods);
    }

    private function getTamaraMethodData($availableMethods) {
        if (empty($availableMethods)) {
            return [];
        }
        $languageCode = $this->getLanguageCodeFromSession();
        $this->load->language('extension/tamarapay/payment/tamarapay');
        if ($languageCode == "ar") {
            $termAndConditionLink = "https://www.tamara.co/terms-and-conditions.html";
        } else {
            $termAndConditionLink = "https://www.tamara.co/en/terms-and-conditions.html";
        }

        if (count($availableMethods) == 1) {
            $optionData['tamarapay'] = [
                'code' => 'tamarapay.tamarapay',
                'name' => reset($availableMethods)['title']
            ];
    
            $methodData = [
                'code'       => 'tamarapay',
                'name'       => $this->language->get('text_tamarapay'),
                'option'     => $optionData,
                'sort_order' => $this->config->get('payment_tamarapay_sort_order')
            ];
            return $methodData;
        }


        $optionData['tamarapay'] = [
            'code' => 'tamarapay.tamarapay',
            'name' => $this->language->get('text_title_normal')
        ];

        $methodData = [
            'code'       => 'tamarapay',
            'name'       => $this->language->get('text_tamarapay'),
            'option'     => $optionData,
            'sort_order' => $this->config->get('payment_tamarapay_sort_order')
        ];

        return $methodData;
    }

    public function validateCartItems() {
        $products = $this->cart->getProducts();
        $cartProductIds = [];
        foreach ($products as $product) {
            $cartProductIds[] = $product['product_id'];
        }
        if (!empty($cartProductIds)) {
            $excludeProductIds = explode(",",strval($this->config->get('payment_tamarapay_pdp_wg_exclude_product_ids')));
            $intersect = array_intersect($excludeProductIds, $cartProductIds);
            if (count($intersect) > 0) {
                return false;
            }
            $in = implode(',', $cartProductIds);
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE `product_id` IN (". $in . ")");
            $rows = $query->rows;
            if (!empty($rows)) {
                $productDataByCategory = [];
                foreach ($rows as $row) {
                    $productDataByCategory[$row['category_id']][] = $row['product_id'];
                }
                $excludeCategoryIds = explode(",",strval($this->config->get('payment_tamarapay_pdp_wg_exclude_category_ids')));
                $categoryIds = array_keys($productDataByCategory);
                $intersect = array_intersect($excludeCategoryIds, $categoryIds);
                if (count($intersect) > 0) {
                    return false;
                }
            }
        }
        return true;
    }

    public function validateTamaraPaymentByAddress($address)
    {
        //validate country
        if (empty($address['iso_code_2'])) {
            return true;
        }
        if (!in_array($address['iso_code_2'], self::SUPPORTED_COUNTRIES)) {
            return false;
        }
        $supportedCountries = $this->getSupportedCountriesByCurrency($this->getCurrencyCodeFromSession());
        if (!empty($supportedCountries)) {
            $countryPairWithCurrency = array_values($supportedCountries)[0];
            if ($address['iso_code_2'] != $countryPairWithCurrency) {
                return false;
            }
        }

        //validate geo zone
        if ($this->config->get('payment_tamarapay_geo_zone_id')) {
            $zoneToGeoZoneRecords = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('payment_tamarapay_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");
            if (!$zoneToGeoZoneRecords->num_rows) {
                return false;
            }
        }

        return true;
    }

    public function getStoreCountry() {
        $this->load->model('localisation/country');
        return $this->model_localisation_country->getCountry($this->config->get('config_country_id'));
    }

    public function getStoreCountryCode() {
        return strtoupper($this->getStoreCountry()['iso_code_2']);
    }

    public function getLanguageCodeFromSession() {
        $this->load->language('extension/tamarapay/payment/tamarapay');
        $languageCode = $this->language->get('code');
        if ($languageCode == "ar") {
            return $languageCode;
        }
        return "en";
    }

    /**
     * Get tamara order by order id
     *
     * @param $orderId
     * @param $forceReload
     *
     * @return mixed
     */
    public function getTamaraOrder($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->tamaraOrdersByOrderId[$orderId])) {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "tamara_orders` WHERE `order_id` = '" . (int) $orderId . "' AND `is_active` = '1' LIMIT 1");
            $tamaraOrder = $query->row;
            if (empty($tamaraOrder)) {
                throw new InvalidArgumentException(sprintf("Order requested does not exist"));
            }
            $this->cacheData($this->tamaraOrdersByOrderId, $orderId, self::MAXIMUM_CACHED, $tamaraOrder);
            $tamaraOrderId = $tamaraOrder['tamara_order_id'];
            $this->cacheData($this->tamaraOrders, $tamaraOrderId, self::MAXIMUM_CACHED, $tamaraOrder);
        }

        return $this->tamaraOrdersByOrderId[$orderId];
    }

    private function cacheData(&$model, $key, $limit, $data)
    {
        if (count($model) == $limit) {
            array_splice($model, $limit / 2);
        }
        $model[$key] = $data;

        return $model;
    }

    public function createCheckout($paymentType)
    {
        try {
            if ($this->getDisableTamara()) {
                throw new \Exception("Tamara is unavailable for your order currently");
            }
            $this->log("Start create checkout");
            $this->load->language('extension/tamarapay/payment/tamarapay');
            $orderId = $this->session->data['order_id'];


            //deactivate recent session order
            $this->deactivateRecentTamaraOrder($orderId);
            $this->log(["Session data: "]);
            $this->log($this->session->data);
            $orderReferenceId = $this->generateOrderReferenceId($orderId);
            $order = $this->prepareOrder($paymentType, $orderId, $orderReferenceId);
            $this->log(["Prepare order " . $orderId]);
            $this->log($order->toArray());
            $request = new CreateCheckoutRequest($order);

            $response = $this->getTamaraClient()->createCheckout($request);

            if (!$response->isSuccess()) {
                $errors = $response->getErrors();
                $errorCode = $errors[0]['error_code'] ?? '';
                throw new Exception($errorCode);
            }

            $checkoutResponse = $response->getCheckoutResponse();

            if ($checkoutResponse === null) {
                throw new Exception($response->getContent());
            }

            $tamaraOrderId = $checkoutResponse->getOrderId();
            $redirectUrl = $checkoutResponse->getCheckoutUrl();

            $saveData = [
                'order_id' => $orderId,
                'tamara_order_id' => $tamaraOrderId,
                'redirect_url' => $redirectUrl,
                'reference_id' => $orderReferenceId,
                'payment_type' => $paymentType,
                'number_of_installments' => $order->getInstalments()
            ];

            $this->addTamaraOrder($saveData);
            $this->load->model('checkout/order');

            $this->log([
                'msg' => 'Created tamara checkout',
                'order_id' => $saveData['order_id'],
                'tamara_order_id' => $tamaraOrderId,
                'redirect_url' => $redirectUrl,
                'reference order id' => $orderReferenceId
            ]);

            // $this->session->data['tamara_order_id'] = $saveData['order_id'];

            return ['redirectUrl' => $redirectUrl];

        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
            return ['error' => "Tamara is unavailable for your order currently"];
        }
        catch (Exception $exception) {
            $this->log($exception->getMessage());

            $errorMessage = !empty($exception->getMessage()) ? $exception->getMessage() : 'error_create_checkout';

            return ['error' => $this->language->get($errorMessage)];
        }
    }

    private function deactivateRecentTamaraOrder($opencartOrderId)
    {
        try {
            $sql = "UPDATE " . DB_PREFIX . "tamara_orders SET is_active = 0 WHERE order_id = '{$opencartOrderId}'";
            $this->db->query($sql);
            $this->tamaraOrdersByOrderId = [];
            $this->resetTamaraOrderCache();
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }
    }

    private function resetTamaraOrderCache()
    {
        $this->tamaraOrdersByOrderId = [];
        $this->tamaraOrders = [];
    }

    /**
     * @return bool
     */
    public function isCurrencySupported()
    {
        return in_array($this->getCurrencyCodeFromSession(), self::SUPPORTED_CURRENCIES, true);
    }

    public function getCurrencyCodeFromSession()
    {
        $currencyCode = '';
        if (!empty($this->session->data['currency'])) {
            $currencyCode = $this->session->data['currency'];
        }
        if (empty($currencyCode)) {
            $this->load->model('setting/setting');
            if ($configCurrency = $this->model_setting_setting->getSettingValue('config_currency')) {
                $currencyCode = $configCurrency;
            }
        }
        return $currencyCode;
    }

    private function prepareOrder($paymentType, $orderId, $orderReferenceId)
    {
        $orderData = $this->getOrder($orderId);
        $order = new Order();

        $order->setOrderReferenceId($orderReferenceId);
        $order->setOrderNumber($orderId);
        $languageCode = $this->getLanguageCodeFromSession();
        if ($languageCode == "en") {
            $locale = "en_GB";
        } else {
            $locale = "ar_SA";
        }
        $order->setLocale($locale);
        $order->setCurrency($this->getCurrencyCodeFromSession());
        $order->setTotalAmount($this->formatMoney($orderData['total'], $orderData['currency_code'], $orderData['currency_value']));
        $order->setCountryCode($this->getOrderCountryCode($orderData));
        if ($this->isInstallmentsPayment($paymentType)) {
            $order->setInstalments($this->getInstallmentsNumberByPaymentCode($paymentType));
        }
        $paymentType = $this->convertPaymentTypeFromOpenCartToTamara($paymentType);
        $order->setPaymentType($paymentType);
        $order->setPlatform($this->getPlatformDescription());
        $order->setDescription($this->config->get('config_name'));
        $orderItems = $this->getOrderItems($orderId);
        $order->setItems($this->getOrderItemCollection($orderItems));

        $billing = new Address();
        $shipping = new Address();
        $consumer = new Consumer();
        $merchantUrl = new MerchantUrl();

        $billing->setFirstName($orderData['payment_firstname']);
        $billing->setLastName($orderData['payment_lastname']);
        $billing->setLine1($orderData['payment_address_1']);
        $billing->setLine2($orderData['payment_address_2']);
        $billingRegion = $orderData['payment_zone'];
        $billing->setRegion($billingRegion);
        $billingCity = $orderData['payment_city'];
        if (empty($billingCity)) {
            $billingCity = $billingRegion;
        }
        $billing->setCity($billingCity);
        $phoneNumber = preg_replace("/\s+/", "", $orderData['telephone']);
        $billing->setPhoneNumber($phoneNumber);
        $billing->setCountryCode($orderData['payment_iso_code_2']);

        $shipping->setFirstName($orderData['shipping_firstname']);
        $shipping->setLastName($orderData['shipping_lastname']);
        $shipping->setLine1($orderData['shipping_address_1']);
        $shipping->setLine2($orderData['shipping_address_2']);
        $shippingRegion = $orderData['shipping_zone'];
        $shipping->setRegion($shippingRegion);
        $shippingCity = $orderData['shipping_city'];
        if (empty($shippingCity)) {
            $shippingCity = $shippingRegion;
        }
        $shipping->setCity($shippingCity);
        $shipping->setPhoneNumber($phoneNumber);
        $shipping->setCountryCode($orderData['shipping_iso_code_2']);

        $consumer->setFirstName($orderData['shipping_firstname']);
        $consumer->setLastName($orderData['shipping_lastname']);
        $consumer->setEmail($orderData['email']);
        $consumer->setPhoneNumber($phoneNumber);

        $merchantUrlData = $this->getMerchantUrls();
        $merchantUrl->setSuccessUrl($merchantUrlData['success']);
        $merchantUrl->setFailureUrl($merchantUrlData['failure']);
        $merchantUrl->setCancelUrl($merchantUrlData['cancel']);
        $merchantUrl->setNotificationUrl($merchantUrlData['notification']);

        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);
        $order->setConsumer($consumer);
        $order->setMerchantUrl($merchantUrl);

        $orderTotals = $this->getOrderTotals($orderId);
        $order->setShippingAmount($this->formatMoney($this->getShippingAmount($orderTotals), $orderData['currency_code'], $orderData['currency_value']));
        $order->setTaxAmount($this->formatMoney($this->getOrderTaxAmount($orderTotals), $orderData['currency_code'], $orderData['currency_value']));
        $order->setDiscount($this->getOrderDiscount($orderTotals));
        $order->setRiskAssessment(
            new \TMS\Tamara\Model\Order\RiskAssessment(
                $this->getRiskAssessmentData($orderData)
            )
        );
        

        return $order;
    }

    public function getRiskAssessmentData($orderData) {
        $riskAssessmentData = [
            'account_creation_date' => null,
            'has_delivered_order' => false,
            'total_order_count' => 0,
            'date_of_first_transaction' => null,
            'is_existing_customer' => false,
            'order_amount_last3months' => 0.0,
            'order_count_last3months' => 0
        ];
            try {
                $timezoneStr = empty($this->config->get('date_timezone')) ? 'UTC' : $this->config->get('date_timezone');
                $timezone = new DateTimeZone($timezoneStr);
                $date3monthsAgo = new DateTime('now', $timezone);
                $date3monthsAgo->modify('-3 month');
                if ($this->customer->isLogged()) {
                    $this->load->model('account/customer');
                    $customerData = $this->model_account_customer->getCustomer($this->customer->getId());
                    if (!empty($customerData['date_added'])) {
                        if (\DateTime::createFromFormat('Y-m-d H:i:s', $customerData['date_added'], $timezone) != false) {
                            $riskAssessmentData['account_creation_date'] = \DateTime::createFromFormat('Y-m-d H:i:s', $customerData['date_added'], $timezone)
                                ->format('d-m-Y');
                        }
                        if (\DateTime::createFromFormat('Y-m-d H:i:s', $orderData['date_added'], $timezone) != false) {
                            $orderCreationDate = \DateTime::createFromFormat('Y-m-d H:i:s', $orderData['date_added'], $timezone)
                                ->format('d-m-Y');
                            if ($riskAssessmentData['account_creation_date'] != null && $riskAssessmentData['account_creation_date'] != $orderCreationDate) {
                                $riskAssessmentData['is_existing_customer'] = true;
                            }
                        }
                    }
                }
                $deliveredStatuses = [];
                $failedStatuses = [];
                $orderStatuses = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_status`;")->rows;
                if (!empty($orderStatuses)) {
                    foreach ($orderStatuses as $orderStatus) {
                        if (in_array($orderStatus['name'], self::ORDER_DELIVERED_STATUSES)) {
                            $deliveredStatuses[] = $orderStatus['order_status_id'];
                        }
                        if (in_array($orderStatus['name'], self::ORDER_FAILED_STATUSES)) {
                            $failedStatuses[] = $orderStatus['order_status_id'];
                        }
                    }
                    $deliveredStatuses = array_unique($deliveredStatuses);
                    $failedStatuses = array_unique($failedStatuses);
                }
                $consumerEmailAddress = preg_replace("/\s+/", "", $orderData['email']);
                $consumerOrdersSql = "SELECT order_id, store_id, customer_id, email, telephone, payment_code, total, order_status_id, currency_id, currency_code, currency_value, date_added, date_modified  FROM `". DB_PREFIX ."order` oo WHERE  (telephone = '" . preg_replace("/\s+/", "", $orderData['telephone']) . "' OR email = '". $consumerEmailAddress ."');";
                $consumerOrders = $this->db->query($consumerOrdersSql)->rows;
                if (!empty($consumerOrders)) {
                    foreach ($consumerOrders as $consumerOrder) {
                        if ($riskAssessmentData['date_of_first_transaction'] === null) {
                            if (\DateTime::createFromFormat('Y-m-d H:i:s',$consumerOrder['date_added'] , $timezone) != false) {
                                $riskAssessmentData['date_of_first_transaction'] = \DateTime::createFromFormat('Y-m-d H:i:s',$consumerOrder['date_added'] , $timezone)
                                    ->format('d-m-Y');
                            }
                        }
                        if (in_array($consumerOrder['order_status_id'], $deliveredStatuses)) {
                            $riskAssessmentData['has_delivered_order'] = true;
                        }
                        if ($consumerOrder['payment_code'] == 'tamarapay') {
                            continue;
                        }
                        if (!empty($consumerOrder['order_status_id']) && !in_array($consumerOrder['order_status_id'], $failedStatuses)) {
                            $riskAssessmentData['total_order_count']++;
                            $consumerOrderCreationDate = \DateTime::createFromFormat('Y-m-d H:i:s', $consumerOrder['date_added'], $timezone);
                            if ($consumerOrderCreationDate != false) {
                                if ($consumerOrderCreationDate > $date3monthsAgo) {
                                    $riskAssessmentData['order_count_last3months']++;
                                    $riskAssessmentData['order_amount_last3months'] += $this->getValueInCurrency($consumerOrder['total'], $consumerOrder['currency_code'], $consumerOrder['currency_value']);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $exception) {
                //pass
            }
            return $riskAssessmentData;
    }

    public function getOrderCountryCode($orderData) {

        $countryCode = $orderData['shipping_iso_code_2'];
        if (empty($countryCode)) {

            if (isset($this->session->data['shipping_method'])) {
                $countryCode = $this->session->data['shipping_method']['code'];
            } else {
                $countryCode = $orderData['payment_iso_code_2'];
            }
        }
        return $countryCode;
    }

    /**
     * @param $orderId
     * @return string
     */
    private function generateOrderReferenceId($orderId) {
        return $orderId . "-" . (microtime(true)  * 10000);
    }

    private function getOrderIdFromReferenceId($orderReferenceId) {
        return explode("-",$orderReferenceId)[0];
    }

    public function getOrder($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->orders[$orderId])) {
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($orderId);
            $order['total_in_currency'] = $this->getValueInCurrency($order['total'], $order['currency_code'], $order['currency_value']);
            $this->cacheData($this->orders, $orderId, self::MAXIMUM_CACHED, $order);
        }

        return $this->orders[$orderId];
    }

    public function getOrderIdFromSession()
    {
        return $this->session->data['order_id'] ?? null;
    }

    private function formatMoney($price, $currencyCode = null, $currencyValue = null)
    {
        if (is_null($currencyCode)) {
            $currencyCode = $this->getCurrencyCodeFromSession();
            if (empty($currencyCode)) {
                throw new Exception("Currency does not exist");
            }
        }
        $price = $this->getValueInCurrency($price, $currencyCode, $currencyValue);
        $price = round($price, 2);
        $price = (float) number_format($price, 2, '.', '');
        return new Money($price, $currencyCode);
    }

    private function getIsoCountryFromSession()
    {
        return strtoupper($this->session->data['shipping_address']['iso_code_2'] ?? $this->session->data['payment_address']['iso_code_2'] ?? '');
    }

    private function getOrderItems($orderId)
    {
        $orderProducts = $this->getOrderProducts($orderId);
        $items = [];
        $currency = $this->getOrderCurrency($orderId);
        $currencyValue = $this->getOrderCurrencyValue($orderId);
        foreach ($orderProducts as $orderProduct) {
            $productData = $this->getProductById($orderProduct['product_id']);
            $sku = empty($productDetails['sku']) ? $orderProduct['product_id'] : $productData['sku'];
            $itemType = empty($productData['model']) ? "simple product" : $productData['model'];
            $items[$orderProduct['order_product_id']] = [
                'order_item_id' => $orderProduct['order_product_id'],
                'product_id' => $orderProduct['product_id'],
                'total_amount' => $orderProduct['total'],
                'total_amount_in_currency' => $this->getValueInCurrency($orderProduct['total'], $currency, $currencyValue),
                'tax_amount' => $orderProduct['tax'],
                'tax_amount_in_currency' => $this->getValueInCurrency($orderProduct['tax'], $currency, $currencyValue),
                'discount_amount' => 0.00,
                'discount_amount_in_currency' => 0.00,
                'unit_price' => $orderProduct['price'],
                'unit_price_in_currency' => $this->getValueInCurrency($orderProduct['price'], $currency, $currencyValue),
                'name' => $orderProduct['name'],
                'sku' => $sku,
                'type' => $itemType,
                'reward' => $orderProduct['reward'],
                'quantity' => $orderProduct['quantity'],
                'image_url' => strval($this->getProductImageUrl($productData['image'])),
                'currency' => $currency,
                'currency_value' => $currencyValue,
                'item_url' => $this->url->link('product/product', 'product_id=' . $orderProduct['product_id'])
            ];
        }

        return $items;
    }

    private function getOrderProducts($orderId)
    {
        $this->load->model('checkout/order');
        return $this->model_checkout_order->getProducts($orderId);
    }

    private function getProductById($productId)
    {
        $this->load->model('catalog/product');

        return $this->model_catalog_product->getProduct($productId);
    }

    private function getProductImageUrl($relativeImagePath)
    {
        if ($this->isRunningFromConsole()) {
            return $this->getBaseUrl() . 'image/' . $relativeImagePath;
        }
        $this->load->model('tool/image');
        $width = $this->config->get('config_image_popup_width');
        $height = $this->config->get('config_image_popup_height');
        if (empty($width)) {
            $width = 40;
        }
        if (empty($height)) {
            $height = 40;
        }

        return $this->model_tool_image->resize($relativeImagePath, $width,
            $height);
    }

    private function getOrderCurrency($orderId)
    {
        return $this->getOrder($orderId)['currency_code'];
    }

    private function getOrderCurrencyValue($orderId) {
        return $this->getOrder($orderId)['currency_value'];
    }

    /**
     * convert array items data to Tamara OrderItemCollection
     *
     * @param array $items
     *
     * @return OrderItemCollection
     * @throws Exception
     */
    private function getOrderItemCollection(array $items)
    {
        $orderItemCollection = new OrderItemCollection();
        foreach ($items as $item) {
            $orderItemCollection->append($this->createItemFromData($item));
        }

        return $orderItemCollection;
    }

    /**
     * Create Tamara OrderItem from item data
     *
     * @param $item
     *
     * @return OrderItem
     * @throws Exception
     */
    private function createItemFromData($item)
    {
        $orderItem = new OrderItem();
        $orderItem->setReferenceId($item['order_item_id']);
        $orderItem->setName($item['name']);
        $orderItem->setSku($item['sku']);
        $orderItem->setType($item['type']);
        $orderItem->setUnitPrice($this->formatMoney($item['unit_price'], $item['currency'], $item['currency_value']));
        $orderItem->setTotalAmount($this->formatMoney($item['total_amount'], $item['currency'], $item['currency_value']));
        $orderItem->setTaxAmount($this->formatMoney($item['tax_amount'] ?? 0, $item['currency'], $item['currency_value']));
        $orderItem->setDiscountAmount($this->formatMoney($item['discount_amount'] ?? 0, $item['currency'], $item['currency_value']));
        $orderItem->setQuantity($item['quantity']);
        $orderItem->setImageUrl($item['image_url'] ?? '');
        $orderItem->setItemUrl($item['item_url'] ?? '');

        return $orderItem;
    }

    public function getMerchantUrls()
    {
        $baseUrl = $this->getBaseUrl();
        $successUrl = $baseUrl . 'index.php?route=extension/tamarapay/payment/tamarapay.success';
        $failureUrl = $baseUrl . 'index.php?route=extension/tamarapay/payment/tamarapay.failure';
        $cancelUrl = $baseUrl . 'index.php?route=extension/tamarapay/payment/tamarapay.cancel';
        $notificationUrl = $baseUrl . 'index.php?route=extension/tamarapay/payment/tamarapay.notification';
        $result = [
            'success' => $successUrl,
            'failure' => $failureUrl,
            'cancel' => $cancelUrl,
            'notification' => $notificationUrl,
        ];

        return $result;
    }

    public function getBaseUrl()
    {
        return HTTP_SERVER;
    }

    /**
     * Get order totals in order currency
     * @param $orderId
     * @param bool $forceReload
     * @return mixed
     */
    private function getOrderTotals($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->orderTotalsArr[$orderId])) {
            $this->load->model('checkout/order');
            $orderTotals = $this->model_checkout_order->getTotals($orderId);
            $currencyCode = $this->getOrderCurrency($orderId);
            $currencyValue = $this->getOrderCurrencyValue($orderId);
            foreach ($orderTotals as &$orderTotal) {
                $orderTotal['value_in_currency'] = $this->getValueInCurrency($orderTotal['value'], $currencyCode, $currencyValue);
                $orderTotal['currency_code'] = $currencyCode;
                $orderTotal['currency_value'] = $currencyValue;
            }
            $this->cacheOrderTotals($orderId, $orderTotals);
        }

        return $this->orderTotalsArr[$orderId];
    }

    private function cacheOrderTotals($orderId, $data)
    {
        return $this->cacheData($this->orderTotalsArr, $orderId, self::MAXIMUM_CACHED, $data);
    }

    /**
     * @param $orderTotals
     * @return Money
     * @throws Exception
     */
    private function getShippingAmount($orderTotals)
    {
        $result = 0.00;
        foreach ($orderTotals as $orderTotal) {
            if ($orderTotal['code'] == 'shipping') {
                $result += $orderTotal['value'];
                break;
            }
        }

        return $result;
    }

    private function getOrderTaxAmount(array $orderTotals)
    {
        $result = 0.00;
        foreach ($orderTotals as $orderTotal) {
            if ($orderTotal['code'] == 'tax') {
                $result += $orderTotal['value'];
            }
        }

        return $result;
    }

    private function getOrderDiscount($orderTotals): Discount
    {
        $name = "";
        $amount = 0.00;
        $currencyCode = null;
        foreach ($orderTotals as $orderTotal) {
            if ($currencyCode === null) {
                $currencyCode = $orderTotal['currency_code'];
            }
            if ($orderTotal['code'] == "coupon") {
                if (empty($name)) {
                    $name .= $orderTotal['title'];
                } else {
                    $name .= " ; " . $orderTotal['title'];
                }
                $amount += ($orderTotal['value'] * -1);
            }
        }

        return new Discount($name, new Money($amount, $currencyCode));
    }

    public function log($data, $class_step = 6, $function_step = 6)
    {
        if ($this->config->get('payment_tamarapay_debug')) {
            if ($this->isRunningFromConsole()) {
                $consoleMessage = "";
                if (is_string($data)) {
                    $consoleMessage = $data;
                } else {
                    if (is_array($data) && isset($data[0]) && is_string($data[0])) {
                        $consoleMessage = $data[0];
                    }
                }
                if (!empty($consoleMessage)) {
                    $this->console->getOutput()->writeln($consoleMessage);
                }
            }
            $backtrace = debug_backtrace();
            $log = new \Opencart\System\Library\Log('tamarapay.log');
            if (!empty($backtrace[$class_step]['class']) && !empty($backtrace[$function_step]['function'])) {
                $log->write('(' . $backtrace[$class_step]['class'] . '::' . $backtrace[$function_step]['function'] . ') - ' . print_r($data,
                        true));
            }
        }
    }

    public function addTamaraOrder($data)
    {

        //   $this->db->query("INSERT INTO `" . DB_PREFIX . "tamara_orders` SET `order_id` = '".$data['order_id']."' ,
        // `tamara_order_id` = '".$this->db->escape($data['tamara_order_id'])."', 
        // `redirect_url` = '".$data['redirect_url']."',
        //  `reference_id` = '".$data['reference_id']."', 
        //  `is_active` = '1', 
        //  `payment_type` = '".$data['payment_type']."', 
        //  `number_of_installments` = '".$data['number_of_installments']."'  ");

        
        $query = sprintf("INSERT INTO `%s` SET `order_id` = %d, `tamara_order_id` = '%s', `redirect_url` = '%s', `is_active` = '%s', `reference_id` = '%s', `payment_type` = '%s', `number_of_installments` = '%s'",
            DB_PREFIX . 'tamara_orders', (int) $data['order_id'], $this->db->escape($data['tamara_order_id']),
            $data['redirect_url'], 1, $data['reference_id'], $data['payment_type'], $data['number_of_installments']);
        $this->db->query($query);
    }

    public function authoriseOrder($tamaraOrderId)
    {
        try {
            if ($this->getDisableTamara()) {
                throw new \Exception("Tamara is disabled");
            }
            $this->log('Start authorise order, order id: ' . $tamaraOrderId);
            $this->load->language('extension/tamarapay/payment/tamarapay');
            $this->load->model('checkout/order');
            $request = new AuthoriseOrderRequest($tamaraOrderId);

            $response = $this->getTamaraClient()->authoriseOrder($request);

            if (!$response->isSuccess()) {
                throw new Exception($response->getMessage());
            } else {
                if (!in_array($response->getOrderStatus(), ["authorised", "fully_captured"])) {
                    throw new Exception("Order status doesn't accept authorization");
                }
            }

            $tamaraOrder = $this->getTamaraOrderByTamaraOrderId($tamaraOrderId);
            $this->updatePaymentTypeAfterCheckout($tamaraOrder);
            $this->updateAuthoriseOrder($response->getOrderId());
            $this->model_checkout_order->addHistory($tamaraOrder['order_id'], $this->config->get('payment_tamarapay_order_status_authorised_id'), 'Order was authorised by Tamara, order id: ' . $response->getOrderId(), false);
            $this->log("Order was authorised, order id: " . $tamaraOrderId);

            return ['success' => true, 'message' => 'Order was authorised', 'order_id' => $tamaraOrderId];

        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        }
        catch (Exception $exception) {
            $this->log('Error when authorise order, error message: ' . $exception->getMessage());

            return ['success' => false, 'error' => $exception->getMessage()];
        }

    }

    public function updateAuthoriseOrder($orderId)
    {
        $query = sprintf("UPDATE `%s` SET `is_authorised` = %d WHERE `tamara_order_id` = '%s'",
            DB_PREFIX . 'tamara_orders', 1, $orderId);
        $this->db->query($query);
    }

    public function getTamaraOrderByReferenceId($referenceId) {
        $query = sprintf("SELECT * FROM `%s` WHERE `reference_id` = '%s' AND `is_active` = '1' LIMIT 1",
            DB_PREFIX . 'tamara_orders',
            $referenceId);
        $tamaraOrder = $this->db->query($query)->row;
        if (empty($tamaraOrder)) {
            throw new InvalidArgumentException("Order requested does not exist");
        }
        return $tamaraOrder;
    }

    /**
     * Get tamara order by tamara order id
     *
     * @param $tamaraOrderId
     * @param bool $forceReload
     * @param bool $active
     * @return mixed
     */
    public function getTamaraOrderByTamaraOrderId($tamaraOrderId, $forceReload = false, $active = true)
    {
        if ($forceReload || empty($this->tamaraOrders[$tamaraOrderId])) {
            $query = sprintf("SELECT * FROM `%s` WHERE `tamara_order_id` = '%s'",
                DB_PREFIX . 'tamara_orders',
                $tamaraOrderId);
            if ($active) {
                $query .= " AND `is_active` = '1'";
            }
            $query .= " LIMIT 1";
            $tamaraOrder = $this->db->query($query)->row;
            if (empty($tamaraOrder)) {
                throw new InvalidArgumentException("Order requested does not exist");
            }
            $this->cacheData($this->tamaraOrders, $tamaraOrderId, self::MAXIMUM_CACHED, $tamaraOrder);
            $orderId = $tamaraOrder['order_id'];
            $this->cacheData($this->tamaraOrdersByOrderId, $orderId, self::MAXIMUM_CACHED, $tamaraOrder);
        }

        return $this->tamaraOrders[$tamaraOrderId];
    }

    public function getOrderTotalFromSession()
    {
        if ($this->orderTotal !== null) {
            return $this->orderTotal;
        }
        $orderTotal = 0.0;
        $this->load->model('extension/tamarapay/payment/helper/tamara_opencart');
        $cartValue = $this->model_extension_tamarapay_payment_helper_tamara_opencart->getTotalAmountInCurrency();
        if (!empty($cartValue)) {
            $orderTotal = $cartValue;
        } else {
            $orderId = $this->getOrderIdFromSession();
            if (!empty($orderId)) {
                $order = $this->getOrder($orderId);
                $orderTotal = $order['total_in_currency'];
            }
        }
        $this->orderTotal = $orderTotal;
        return $this->orderTotal;
    }

    private function isInLimitAmount($price, $method)
    {
        if ($price < $method[self::MIN_LIMIT] || $price > $method[self::MAX_LIMIT]) {
            return false;
        }

        return true;
    }

    /**
     * get all payment method for checkout page, add available flag to recognize method is available
     * @return array
     */
    public function getPaymentMethodsForCheckoutPage()
    {
        $orderValue = $this->getOrderTotalFromSession();
        $supportedCountries = $this->getSupportedCountriesByCurrency($this->getCurrencyCodeFromSession());
        if (empty($supportedCountries)) {
            return [];
        }
        $paymentTypes = $this->getPaymentTypesByOrderInfo(array_values($supportedCountries)[0], $orderValue, $this->getCustomerPhoneNumberFromSession());
        if (empty($paymentTypes)) {
            return [];
        }
        //sort
        $tmpArr = [];
        foreach ($paymentTypes as $name => $paymentType) {
            if ($name == self::PAY_NOW_CODE) {
                $tmpArr[1] = $paymentType;
                continue;
            }
            if ($name == self::PAY_NEXT_MONTH_CODE) {
                $tmpArr[2] = $paymentType;
                continue;
            }
            if ($name == self::PAY_LATER_CODE) {
                $tmpArr[999] = $paymentType;
                continue;
            }
            if ($this->isInstallmentsPayment($name)) {
                $numberOfInstallment = $this->getInstallmentsNumberByPaymentCode($name);
                $tmpArr[$numberOfInstallment + 1] = $paymentType;
            }
        }
        ksort($tmpArr);
        $paymentTypes = [];
        $firstType = true;
        foreach ($tmpArr as $paymentType) {
            if ($firstType) {
                $paymentType['checked'] = true;
                $firstType = false;
            } else {
                $paymentType['checked'] = false;
            }
            $paymentTypes[$paymentType['name']] = $paymentType;
        }
        return $paymentTypes;
    }

    public function getCurrentAvailableMethods($currencyCode) {
        if (!$this->isTamaraEnabled()) {
            return [];
        }
        if (!$this->isTamaraAvailableForThisCustomer()) {
            return [];
        }
        $supportedCountries = $this->getSupportedCountriesByCurrency($currencyCode);
        if (empty($supportedCountries)) {
            return [];
        }
        return $this->getPaymentTypes(array_values($supportedCountries)[0], $currencyCode);
    }

    public function isTamaraEnabled() {
        return boolval($this->config->get("payment_tamarapay_status"));
    }

    public function filterUnderOver($methods, $price) {
        $result = [];
        foreach ($methods as $method) {
            if (!$this->isInLimitAmount($price, $method)) {
                continue;
            }
            $result[$method['name']] = $method;
        }
        return $result;
    }

    /**
     * Add in limit and checked flag to methods, checked method is the method that we want customer uses
     * @param $methods
     * @param $price
     * @return array
     */
    public function markInLimit($methods, $price) {
        $result = [];
        $isExistChecked = false;
        $firstMethodNameInLimit = "";
        $isExistMethodInLimit = false;
        foreach ($methods as $method) {
            $method['checked'] = false;
            $method['is_in_limit'] = $this->isInLimitAmount($price, $method);
            if ($method['is_in_limit']) {
                if (!$isExistMethodInLimit) {
                    $isExistMethodInLimit = true;
                    $firstMethodNameInLimit = $method['name'];
                }

                //default is first installment method
                if ($this->isInstallmentsPayment($method['name'])) {
                    if (!$isExistChecked) {
                        $method['checked'] = true;
                        $isExistChecked = true;
                    }
                }
            }
            $result[$method['name']] = $method;
        }
        if ($isExistMethodInLimit && !$isExistChecked) {
            $result[$firstMethodNameInLimit]['checked'] = true;
        }
        return $result;
    }

    /**
     * @param $price
     * @param $currencyCode
     * @return array|mixed|null
     */
    public function getBestMethodForCustomer($price, $currencyCode) {
        $methods = $this->filterUnderOver($this->markInLimit($this->getCurrentAvailableMethods($currencyCode), $price), $price);
        if (empty($methods)) {
            return [];
        }
        $result = [];
        $firstMethod = null;
        foreach ($methods as $method) {
            if ($firstMethod === null) {
                $firstMethod = $method;
            }
            if ($method['is_in_limit'] && $this->isInstallmentsPayment($method['name'])) {
                $result = $method;
            }
        }
        if (empty($result)) {
            $result = $firstMethod;
        }
        return $result;
    }

    /**
     * @param $tamaraOrderId
     *
     * @return bool
     */
    public function canCapture($tamaraOrderId)
    {
        $tamaraOrderData = $this->getTamaraOrderData($tamaraOrderId);
        if ($tamaraOrderData['captured_from_console']) {
            return false;
        }
        $captures = $this->getCapturesByTamaraOrderId($tamaraOrderId);
        $totalAmountCaptured = 0.00;
        foreach ($captures as $capture) {
            $totalAmountCaptured += $capture['total_amount'];
        }
        if ($totalAmountCaptured >= floatval($tamaraOrderData['total_amount_in_currency'])) {
            return false;
        }

        return true;
    }

    /**
     * @param $tamaraOrderId
     *
     * @return mixed
     */
    public function getCapturesByTamaraOrderId($tamaraOrderId)
    {
        $sql = sprintf("SELECT * FROM %s WHERE tamara_order_id = '%s'", DB_PREFIX . "tamara_captures", $tamaraOrderId);
        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Capture tamara order
     *
     * @param $tamaraOrderId
     *
     * @return string|null
     * @throws Exception
     */
    public function captureOrder($tamaraOrderId)
    {
        try {
            if ($this->getDisableTamara()) {
                throw new \Exception("Tamara is disabled");
            }
            $this->log("Start to capture order " . $tamaraOrderId);
            $this->load->language('extension/tamarapay/payment/tamarapay');
            $this->load->model('checkout/order');
            if (!$this->canCapture($tamaraOrderId)) {
                throw new Exception("Order {$tamaraOrderId} cannot be captured");
            }

            $orderData = $this->getTamaraOrderData($tamaraOrderId);
            $this->log("Capture order data: ");
            $this->log($orderData);
            $this->log("Creating capture request for order  " . $tamaraOrderId);
            $captureRequest = $this->createCaptureRequest($orderData);
            $this->log("Created capture request for order  " . $tamaraOrderId);
            $this->log("Call capture API for order  " . $tamaraOrderId);
            $response = $this->getTamaraClient()->capture($captureRequest);
            if (!$response->isSuccess()) {
                $this->log("Capture not success for the order " . $tamaraOrderId . ", error: " . $response->getMessage());
                throw new Exception($response->getMessage());
            }
            $captureId = $response->getCaptureId();
            $orderData['capture_id'] = $captureId;
            $this->saveCapture($orderData);
            $this->saveCaptureItems($orderData);
            $this->updateTamaraOrders([$orderData],"captured_from_console", 1);
            $this->log("Capture items saved for the order " . $tamaraOrderId . ", capture id: " . $captureId);
            $comment = 'Order was captured by Tamara, capture id: ' . $captureId;
            $this->addOrderComment($orderData['order_id'], $orderData['order_status_id'], $comment, 0);
            $this->log([
                'msg' => 'Tamara order was captured',
                'capture_id' => $captureId,
                'order_id' => $tamaraOrderId,
            ]);

            return $captureId;
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        }
        catch (Exception $exception) {
            $this->log("Error when capture order " . $tamaraOrderId . ": " . $exception->getMessage());
        }
        return null;
    }

    /**
     * Get order data by tamaraOrderId
     *
     * @param $tamaraOrderId
     *
     * @return array
     */
    public function getTamaraOrderData($tamaraOrderId)
    {
        $result = [];
        $tamaraOrderData = $this->getTamaraOrderByTamaraOrderId($tamaraOrderId);
        if (count($tamaraOrderData)) {
            $orderId = $tamaraOrderData['order_id'];
            
            $orderData = $this->getOrder($orderId);
            $result['tamara_id'] = $tamaraOrderData['tamara_id'];
            $result['order_id'] = $orderId;
            $result['tamara_order_id'] = $tamaraOrderData['tamara_order_id'];
            $result['currency'] = $orderData['currency_code'];
            $result['currency_value'] = $orderData['currency_value'];
            $result['total_amount'] = $orderData['total'];
            $result['total_amount_in_currency'] = $orderData['total_in_currency'];
            $result['items'] = $this->getOrderItems($orderId);
            $orderTotals = $this->getOrderTotals($orderId);
            $result['tax_amount'] = $this->getOrderTaxAmount($orderTotals);
            $result['tax_amount_in_currency'] = $this->getValueInCurrency($result['tax_amount'], $result['currency'], $result['currency_value']);
            $result['shipping_amount'] = $this->getShippingAmount($orderTotals);
            $result['shipping_amount_in_currency'] = $this->getValueInCurrency($result['shipping_amount'], $result['currency'], $result['currency_value']);
            $result['discount_amount'] = $this->getDiscountAmount($orderTotals);
            $result['discount_amount_in_currency'] = $this->getValueInCurrency($result['discount_amount'], $result['currency'], $result['currency_value']);
            $shippingData = $this->getShippingData($orderId);
            $companies = [];
            $trackingNumbers = [];
            foreach ($shippingData as $row) {
                $companies[] = $row['shipping_courier_name'];
                $trackingNumbers[] = $row['tracking_number'];
            }

            $company = !empty($companies) ? implode(',', $companies) : self::EMPTY_STRING;
            $trackNumber = !empty($trackingNumbers) ? implode(',', $trackingNumbers) : '';
            $result['shipping_info'] = [
                'company' => $company,
                'tracking_number' => $trackNumber,
            ];
            $result['order_status_id'] = $orderData['order_status_id'];
            $result['captured_from_console'] = $tamaraOrderData['captured_from_console'];
            $result['canceled_from_console'] = $tamaraOrderData['canceled_from_console'];
        }

        return $result;

    }

    private function getDiscountAmount($orderTotals)
    {
        $result = 0.00;
        foreach ($orderTotals as $total) {
            if ($total['code'] == 'coupon' || $total['code'] == 'voucher') {
                $result += $total['value'];
            }
        }

        return $result;
    }

    /**
     * @param $orderId
     *
     * @return mixed
     */
    public function getShippingData($orderId)
    {
        return [];
    }

    /**
     * Create capture request from order data
     *
     * @param array $orderData
     *
     * @return CaptureRequest
     * @throws Exception
     */
    public function createCaptureRequest(array $orderData): CaptureRequest
    {
        $shippingInfo = new ShippingInfo(
            new DateTimeImmutable('now'),
            $orderData['shipping_info']['company'],
            $orderData['shipping_info']['tracking_number'],
            ''
        );
        $capture = new Capture(
            $orderData['tamara_order_id'],
            $this->formatMoney($orderData['total_amount'], $orderData['currency'], $orderData['currency_value']),
            $this->formatMoney($orderData['shipping_amount'], $orderData['currency'], $orderData['currency_value']),
            $this->formatMoney($orderData['tax_amount'], $orderData['currency'], $orderData['currency_value']),
            $this->formatMoney($orderData['discount_amount'], $orderData['currency'], $orderData['currency_value']),
            $this->getOrderItemCollection($orderData['items']),
            $shippingInfo
        );

        return new CaptureRequest($capture);
    }

    private function saveCapture(array $orderData)
    {
        $query = sprintf("INSERT INTO %stamara_captures (capture_id, order_id, tamara_order_id, total_amount, tax_amount, shipping_amount, discount_amount, shipping_info, currency, created_at, updated_at)
 VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s)",
            DB_PREFIX,
            $orderData['capture_id'],
            $orderData['order_id'],
            $orderData['tamara_order_id'],
            $orderData['total_amount_in_currency'],
            $orderData['tax_amount_in_currency'],
            $orderData['shipping_amount_in_currency'],
            $orderData['discount_amount_in_currency'],
            json_encode($orderData['shipping_info']),
            $orderData['currency'],
            "NOW()",
            "NOW()"
        );
        $this->db->query($query);
    }

    private function saveCaptureItems(array $orderData)
    {
        $query = sprintf("INSERT INTO %stamara_capture_items (order_item_id, order_id, capture_id, product_id, name, image_url, sku, `type`, quantity, unit_price, total_amount, tax_amount, discount_amount, created_at, updated_at) VALUES ",
            DB_PREFIX);
        $first = true;
        foreach ($orderData['items'] as $item) {
            if ($first) {
                $query .= sprintf("(
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s)",
                    $item['order_item_id'],
                    $orderData['order_id'],
                    $orderData['capture_id'],
                    $item['product_id'],
                    $this->db->escape($item['name']),
                    $item['image_url'],
                    $this->db->escape($item['sku']),
                    $item['type'],
                    $item['quantity'],
                    $item['unit_price_in_currency'],
                    $item['total_amount_in_currency'],
                    $item['tax_amount_in_currency'],
                    $item['discount_amount_in_currency'],
                    "NOW()",
                    "NOW()"
                );
                $first = false;
            } else {
                $query .= sprintf(", (
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s)",
                    $item['order_item_id'],
                    $orderData['order_id'],
                    $orderData['capture_id'],
                    $item['product_id'],
                    $this->db->escape($item['name']),
                    $item['image_url'],
                    $this->db->escape($item['sku']),
                    $item['type'],
                    $item['quantity'],
                    $item['unit_price_in_currency'],
                    $item['total_amount_in_currency'],
                    $item['tax_amount_in_currency'],
                    $item['discount_amount_in_currency'],
                    "NOW()",
                    "NOW()"
                );
            }
        }

        $this->db->query($query);
    }

    /**
     * Add order comment without model
     *
     * @param     $orderId
     * @param     $orderStatusId
     * @param     $comment
     * @param int $notify
     */
    public function addOrderComment($orderId, $orderStatusId, $comment, $notify = 0)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "order_history (order_history_id, order_id, order_status_id, notify, comment, date_added) VALUES(null, {$orderId}, {$orderStatusId}, {$notify}, '{$comment}', NOW())";
        $this->db->query($sql);

        $sql = "UPDATE `" . DB_PREFIX . "order` SET order_status_id='{$orderStatusId}' WHERE order_id = '{$orderId}'";
        $this->db->query($sql);
    }

    /**
     * @param $tamaraOrderId
     *
     * @return string|null
     * @throws Exception
     */
    public function cancelOrder($tamaraOrderId)
    {
        try {
            if ($this->getDisableTamara()) {
                throw new \Exception("Tamara is disabled");
            }
            if (!$this->canCancel($tamaraOrderId)) {
                throw new Exception("Order {$tamaraOrderId} cannot be canceled");
            }

            $this->log("Start cancel order " . $tamaraOrderId);
            $this->load->language('extension/tamarapay/payment/tamarapay');
            $this->load->model('checkout/order');
            $orderData = $this->getTamaraOrderData($tamaraOrderId);

            /**
             * @var $cancelRequest CancelOrderRequest
             */
            $cancelRequest = $this->createCancelRequest($orderData);

            /**
             * @var $response CancelResponse
             */
            $response = $this->getTamaraClient()->cancelOrder($cancelRequest);
            if (!$response->isSuccess()) {
                throw new Exception($response->getMessage());
            }
            $cancelId = $response->getCancelId();
            $orderData['cancel_id'] = $cancelId;
            $this->saveCancel($orderData, $cancelRequest);
            $comment = 'Tamara order was canceled, tamara order id: '. $tamaraOrderId .', cancel id: ' . $cancelId;
            $this->addOrderComment($orderData['order_id'], $orderData['order_status_id'], $comment, 0);
            $this->log("Order " . $tamaraOrderId . " was canceled. Cancel id: " . $cancelId);

            return $cancelId;
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        }
        catch (Exception $exception) {
            $this->log("Error when cancel order " . $tamaraOrderId . ": " . $exception->getMessage());
        }
        return null;
    }

    public function canCancel($tamaraOrderId){
        $tamaraOrder = $this->getTamaraOrderByTamaraOrderId($tamaraOrderId);
        if ($tamaraOrder['canceled_from_console']) {
            return false;
        }
        return true;
    }

    public function createCancelRequest(array $orderData): CancelOrderRequest
    {
        return new CancelOrderRequest(
            $orderData['tamara_order_id'],
            $this->formatMoney($orderData['total_amount'], $orderData['currency'], $orderData['currency_value']),
            $this->getOrderItemCollection($orderData['items']),
            $this->formatMoney($orderData['shipping_amount'], $orderData['currency'], $orderData['currency_value']),
            $this->formatMoney($orderData['tax_amount'], $orderData['currency'], $orderData['currency_value']),
            $this->formatMoney($orderData['discount_amount'], $orderData['currency'], $orderData['currency_value'])
        );
    }

    /**
     * @param array $orderData
     * @param       $cancelRequest
     */
    private function saveCancel(array $orderData, CancelOrderRequest $cancelRequest)
    {
        $query = sprintf("INSERT INTO %stamara_cancels (cancel_id, order_id, tamara_order_id, request, created_at, updated_at)
 VALUES('%s', '%s', '%s', '%s', %s, %s)",
            DB_PREFIX,
            $orderData['cancel_id'],
            $orderData['order_id'],
            $orderData['tamara_order_id'],
            json_encode($cancelRequest->toArray()),
            "NOW()",
            "NOW()"
        );
        $this->db->query($query);
    }

    public function isRunningFromConsole() {
        return $this->registry->get('console') !== null;
    }

    public function getTamaraClient() {
        $url = $this->getApiUrl();
        $token = $this->config->get('payment_tamarapay_token');

        return Client::create(Configuration::create($url, $token, self::API_REQUEST_TIMEOUT));
    }

    /**
     * @param $orderReferenceId
     * @return \TMS\Tamara\Response\Order\GetOrderByReferenceIdResponse|null
     */
    public function getTamaraOrderFromRemote($orderReferenceId) {
        if ($this->getDisableTamara()) {
            return null;
        }
        try {
            return $this->getTamaraClient()->getOrderByReferenceId(new \TMS\Tamara\Request\Order\GetOrderByReferenceIdRequest($orderReferenceId));
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }
        return null;
    }

    /**
     * @param $tamaraOrderId
     * @return \TMS\Tamara\Response\Order\GetOrderResponse|null
     * @throws \TMS\Tamara\Exception\RequestDispatcherException
     */
    public function getTamaraOrderFromRemoteByTamaraOrderId($tamaraOrderId) {
        if ($this->getDisableTamara()) {
            return null;
        }
        try {
            return $this->getTamaraClient()->getOrder(new \TMS\Tamara\Request\Order\GetOrderRequest($tamaraOrderId));
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }
        return null;
    }

    public function webhook()
    {
        $webhookMessage = $this->getNotificationService()->processWebhook();
        $eventType = $webhookMessage->getEventType();
        if (!in_array($eventType, self::ALLOWED_WEBHOOKS)) {
            $this->log([
                'msg' => 'Webhook event type is not allowed',
                'Event type: ' => $eventType,
                'Webhook tamara order id: ' => $webhookMessage->getOrderId(),
                'Webhook order reference id: ' => $webhookMessage->getOrderReferenceId(),
            ]);
            throw new \Exception($this->language->get("error_webhook_event_type_not_allowed"));
        }
        $comment = sprintf('Tamara - order was %s by webhook, order reference id: %s', $eventType, $webhookMessage->getOrderReferenceId());
        try {
            $tamaraOrder = $this->getTamaraOrderByTamaraOrderId($webhookMessage->getOrderId());
        } catch (\Exception $exception) {
            //order requested doest not exist or has been cancelled or dis active
            return;
        }
        $orderId = $tamaraOrder['order_id'];
        if (!$this->isPayWithTamara($orderId)) {
            return;
        }

        //change status if the order has a status (the order was approved)
        $tamaraOrderData = $this->getTamaraOrderData($webhookMessage->getOrderId());
        if (empty($tamaraOrderData['order_status_id'])) {
            return;
        }
        if ($eventType == "order_declined") {
            $this->addOrderComment($orderId, $this->config->get("payment_tamarapay_order_status_failure_id"), $comment, 0);
        } else {
            $this->addOrderComment($orderId, $this->config->get("payment_tamarapay_order_status_canceled_id"), $comment, 0);
        }
        $this->log(["Webhook processed successful for order " . $orderId]);
    }

    public function createNotificationService($notificationToken) {
        return \TMS\Tamara\Notification\NotificationService::create($notificationToken);
    }

    public function getNotificationService() {
        return $this->createNotificationService($this->config->get("payment_tamarapay_token_notification"));
    }

    /**
     * Get Tamara payment types
     * @param $countryCode
     * @param string $currencyCode
     * @return array
     */
    public function getPaymentTypes($countryCode, $currencyCode = '')
    {
        try {
            $cacheKey = $this->getCacheKey($countryCode, $currencyCode);
            $cachedPaymentTypes = $this->getCachedPaymentTypes($cacheKey);
            if ($cachedPaymentTypes === false) {

                /**
                 * @var $response \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse
                 */
                $response = $this->getPaymentTypesOfClient($this->getTamaraClient(), $countryCode, $currencyCode);
                if ($response === null) {
                    return [];
                }

                if (!$response->isSuccess()) {
                    throw new Exception($response->getMessage());
                }

                $cachedPaymentTypes = $this->parsePaymentTypesFromResponse($response);
                $this->cachePaymentTypes($cachedPaymentTypes, $cacheKey);
            }
            $paymentTypes = $cachedPaymentTypes;

            //add payment title
            foreach ($paymentTypes as &$paymentType) {
                $paymentType['title'] = $this->getTitleOfPaymentMethod($paymentType['name']);
            }
            return $paymentTypes;
        } catch (Exception $exception) {
            $this->log($exception->getMessage());
        }
        return [];
    }

    public function getTitleOfPaymentMethod($methodName) {
        return $this->language->get('text_' . $methodName);
    }

    private function parsePaymentTypesFromResponse($response) {
        $result = [];

        /**
         * @var $response \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse
         */
        if ($response->isSuccess()) {
            foreach ($response->getPaymentTypes() as $paymentType) {

                /** @var PaymentType $paymentType */
                $paymentTypeClone = $paymentType;
                $typeName = "";
                if ($paymentTypeClone->getName() == self::PAY_LATER) {
                    $typeName = self::PAY_LATER_CODE;
                }
                if ($paymentTypeClone->getName() == self::PAY_NEXT_MONTH) {
                    $typeName = self::PAY_NEXT_MONTH_CODE;
                }
                if ($paymentTypeClone->getName() == self::PAY_NOW) {
                    $typeName = self::PAY_NOW_CODE;
                }
                if (!empty($typeName)) {
                    $result[$typeName] = [
                        'name' => $typeName,
                        'min_limit' => $paymentTypeClone->getMinLimit()->getAmount(),
                        'max_limit' => $paymentTypeClone->getMaxLimit()->getAmount(),
                        'currency' => $paymentTypeClone->getMinLimit()->getCurrency(),
                        'description' => $paymentTypeClone->getDescription(),
                        'is_installment' => false,
                    ];
                }
                if ($paymentTypeClone->getName() == self::PAY_BY_INSTALMENTS) {
                    $description = $paymentTypeClone->getDescription();
                    if (count($installments = $paymentTypeClone->getSupportedInstalments())) {
                        foreach ($installments as $installment) {

                            /**
                             * @var \TMS\Tamara\Model\Checkout\Instalment $installment
                             */
                            $installmentMethodCode = $this->getInstallmentPaymentCode($installment->getInstalments());
                            $installmentData = [
                                'name' => $installmentMethodCode,
                                'min_limit' => $installment->getMinLimit()->getAmount(),
                                'max_limit' => $installment->getMaxLimit()->getAmount(),
                                'currency' => $installment->getMinLimit()->getCurrency(),
                                'number_of_instalments' => $installment->getInstalments(),
                                'description' => $description,
                                'is_installment' => true
                            ];
                            $result[$installmentMethodCode] = $installmentData;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @return array|mixed
     */
    private function getPaymentTypesFromDb() {
        $query = "SELECT * FROM `" . DB_PREFIX . "tamara_config` WHERE `key`='payment_types' LIMIT 1";
        $result = $this->db->query($query);
        if ($result->num_rows > 0) {
            $value = $result->row['value'];
            if (empty($value)) {
                return [];
            }
            return json_decode($value, true);
        } else {
            return [];
        }
    }

    private function savePaymentTypesToDb($paymentTypesAsJson) {
        $query = "SELECT * FROM `" . DB_PREFIX . "tamara_config` WHERE `key`='payment_types' LIMIT 1";
        $result = $this->db->query($query);
        if ($result->num_rows > 0) {
            $this->db->query("UPDATE `" . DB_PREFIX . "tamara_config` SET `value` = '{$paymentTypesAsJson}' WHERE `key` = 'payment_types'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "tamara_config`(id, `key`, value, created_at, updated_at) VALUES(NULL, 'payment_types', '{$paymentTypesAsJson}', NOW(), NOW())");
        }
    }

    /**
     * @param $paymentTypes array
     * @param $countryCode
     * @param $currencyCode
     * @param int $lifeTime
     */
    private function cachePaymentTypes($paymentTypes, $cacheKey, $lifeTime = self::PAYMENT_TYPES_CACHE_LIFE_TIME) {
        $this->paymentTypesCache[$cacheKey] = $paymentTypes;
        $data = $this->getPaymentTypesFromDb();
        $data[$cacheKey] = ['cached_time' => time(), 'payment_types' => $paymentTypes, 'life_time' => $lifeTime];
        $this->savePaymentTypesToDb(json_encode($data));
    }

    /**
     * @param $countryCode
     * @param string $currencyCode
     * @return array|bool
     */
    private function getCachedPaymentTypes($cacheKey) {
        if (isset($this->paymentTypesCache[$cacheKey])) {
            return $this->paymentTypesCache[$cacheKey];
        }
        $dbPaymentTypes = $this->getPaymentTypesFromDb();
        if (!isset($dbPaymentTypes[$cacheKey])) {
            return false;
        }
        if (empty($dbPaymentTypes[$cacheKey]['cached_time'])) {
            return false;
        }
        $lifeTime = self::PAYMENT_TYPES_CACHE_LIFE_TIME;
        if (!empty($dbPaymentTypes[$cacheKey]['life_time'])) {
            $lifeTime = $dbPaymentTypes[$cacheKey]['life_time'];
        }
        if ((time() - $dbPaymentTypes[$cacheKey]['cached_time']) > $lifeTime) {
            return false;
        }
        return $dbPaymentTypes[$cacheKey]['payment_types'];
    }

    /**
     * @param Client $client
     * @param $countryCode
     * @param string $currencyCode
     * @return \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse|null
     */
    public function getPaymentTypesOfClient($client, $countryCode, $currencyCode = '') {
        if ($this->getDisableTamara()) {
            return null;
        }
        try {
            return $client->getPaymentTypes($countryCode, $currencyCode);
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }
        return null;
    }


    public function getValueInCurrency($number, $currency, $value = '') {
        $currencies = $this->getCurrencies();
        $decimal_place = $currencies[$currency]['decimal_place'];

        if (!$value) {
            $value = $currencies[$currency]['value'];
        }

        $amount = $value ? (float)$number * $value : (float)$number;

        return round($amount, (int)$decimal_place);
    }

    public function getCurrencies() {
        if (empty($this->currencies)) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency");

            foreach ($query->rows as $result) {
                $this->currencies[$result['code']] = array(
                    'currency_id'   => $result['currency_id'],
                    'title'         => $result['title'],
                    'symbol_left'   => $result['symbol_left'],
                    'symbol_right'  => $result['symbol_right'],
                    'decimal_place' => $result['decimal_place'],
                    'value'         => $result['value']
                );
            }
        }
        return $this->currencies;
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

    public function getApiUrl() {
        if ($this->config->get('payment_tamarapay_api_environment') == self::PRODUCTION_API_ENVIRONMENT) {
            return $this->getProductionApiUrl();
        } else {
            return $this->getSandboxApiUrl();
        }
    }

    public function getCustomerPhoneNumberFromSession() {
        if (isset($this->session->data['customer']['telephone'])) {
            return $this->session->data['customer']['telephone'];
        }
        return "";
    }

    public function isTamaraAvailableForThisCustomer() {
        $availableForTheseCustomers =  $this->config->get('payment_tamarapay_only_show_for_these_customer');
        if (empty($availableForTheseCustomers)) {
            return true;
        }
        $availableForTheseCustomers = explode(",", $availableForTheseCustomers);
        if ($this->customer->isLogged() && in_array($this->customer->getEmail(), $availableForTheseCustomers)) {
            return true;
        }

        return false;
    }

    /**
     * Get payment types that eligible for the order
     * @param $countryCode
     * @param $orderValue
     * @param $phoneNumber
     * @param bool $isVip
     * @return array
     */
    public function getPaymentTypesByOrderInfo($countryCode, $orderValue, $phoneNumber, $isVip = true) {
        $cacheKey = sprintf("%s_%s_%s_%s", $countryCode, $orderValue * 100, $this->removeSpecialCharacters($phoneNumber), $isVip);
        if (isset($this->session->data['tamara_payment_types'][$cacheKey])) {
            $data = $this->session->data['tamara_payment_types'][$cacheKey];
            if ($data['cached_time'] + $data['life_time'] > time()) {
                return $data['payment_types'];
            }
        }
        $paymentTypes = $this->checkPaymentOptionsAvailability($countryCode, $orderValue, $phoneNumber, $isVip)['payment_types'];
        $data = [
            'payment_types' => $paymentTypes,
            'cached_time' => time(),
            'life_time' => 300
        ];
        $this->session->data['tamara_payment_types'][$cacheKey] = $data;
        return $paymentTypes;
    }

    public function removeSpecialCharacters($str) {
        $str = str_replace(' ', '-', $str); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\-]/', '', $str);
    }

    /**
     * @param \TMS\Tamara\Response\Checkout\CheckPaymentOptionsAvailabilityResponse $response
     * @return array
     */
    public function parsePaymentOptionsAvailabilityResponse($response) {
        $result = [
            'has_available_payment_options' => false,
            'single_checkout_enabled' => false,
            'payment_types' => []
        ];
        if ($response->isSuccess()) {
            $this->setSingleCheckoutVersion($response->isSingleCheckoutEnabled());
            if (!$response->hasAvailablePaymentOptions()) {
                return $result;
            }
            $currencyCode = $this->getCurrencyCodeFromSession();
            $languageCode = $this->getLanguageCodeFromSession();
            $paymentTypes = [];
            $allPaymentTypes = $this->getCurrentAvailableMethods($currencyCode);
            $firstMethod = true;
            foreach ($response->getAvailablePaymentLabels() as $paymentType) {
                $typeName = "";
                if ($paymentType['payment_type'] == self::PAY_LATER) {
                    $typeName = self::PAY_LATER_CODE;
                }
                if ($paymentType['payment_type'] == self::PAY_NEXT_MONTH) {
                    $typeName = self::PAY_NEXT_MONTH_CODE;
                }
                if ($paymentType['payment_type'] == self::PAY_NOW) {
                    $typeName = self::PAY_NOW_CODE;
                }
                if ($paymentType['payment_type'] == self::PAY_BY_INSTALMENTS) {
                    $typeName = $this->getInstallmentPaymentCode($paymentType['instalment']);
                }
                $title = $paymentType['description_ar'];
                if ($languageCode == "en") {
                    $title = $paymentType['description_en'];
                }
                if (!empty($typeName)) {
                    $paymentTypes[$typeName] = [
                        'name' => $typeName,
                        'currency' => $currencyCode,
                        'description' => $paymentType['description_en'],
                        'description_ar' => $paymentType['description_ar'],
                        'min_limit' => 1,
                        'max_limit' => 999999999,
                        'is_in_limit' => true,
                        'checked' => false,
                        'title' => $title,
                        'is_installment' => ($paymentType['payment_type'] == self::PAY_BY_INSTALMENTS),
                        'is_none_validated_method' => false
                    ];
                    if ($firstMethod) {
                        $paymentTypes[$typeName]['checked'] = true;
                        $firstMethod = false;
                    }
                    if (isset($paymentType['instalment'])) {
                        if ($paymentType['instalment'] == 0) {
                            $paymentTypes[$typeName]['number_of_instalments'] = 3;
                        } else {
                            $paymentTypes[$typeName]['number_of_instalments'] = $paymentType['instalment'];
                        }
                    }
                    if (isset($allPaymentTypes[$typeName])) {
                        $paymentTypes[$typeName]['min_limit'] = $allPaymentTypes[$typeName]['min_limit'];
                        $paymentTypes[$typeName]['max_limit'] = $allPaymentTypes[$typeName]['max_limit'];
                    }
                }
            }
            $result['payment_types'] = $paymentTypes;
        }
        return $result;
    }

    /**
     * @param $countryCode
     * @param $orderValue
     * @param $phoneNumber
     * @param bool $isVip
     * @return array
     */
    public function checkPaymentOptionsAvailability($countryCode, $orderValue, $phoneNumber, $isVip = true) {
        $result = [
            'has_available_payment_options' => false,
            'single_checkout_enabled' => false,
            'payment_types' => []
        ];
        if ($this->getDisableTamara()) {
            return $result;
        }
        try {
            $paymentOptionsAvailability = new \TMS\Tamara\Model\Checkout\PaymentOptionsAvailability(
                $countryCode,
                new \TMS\Tamara\Model\Money($orderValue, $this->getCurrencyCodeFromSession()),
                $phoneNumber,
                $isVip
            );
            $request = new \TMS\Tamara\Request\Checkout\CheckPaymentOptionsAvailabilityRequest($paymentOptionsAvailability);
            $response = $this->getTamaraClient()->checkPaymentOptionsAvailability($request);
            return $this->parsePaymentOptionsAvailabilityResponse($response);
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        }
        catch (\Exception $exception) {
            $this->log(["Error when checkPaymentOptionsAvailability for customer " . $exception->getMessage()]);
        }
        return $result;
    }

    /**
     * @param int $numberOfInstallments
     * @return string
     */
    public function getInstallmentPaymentCode($numberOfInstallments = 3) {
        $numberOfInstallments = intval($numberOfInstallments);
        if ($numberOfInstallments == 3) {
            return self::PAY_BY_INSTALMENTS_CODE;
        }
        return self::PAY_BY_INSTALMENTS_CODE . "_" . $numberOfInstallments;
    }

    /**
     * @param string $paymentMethodCode
     * @return int
     */
    public function getInstallmentsNumberByPaymentCode($paymentMethodCode = self::PAY_BY_INSTALMENTS_CODE) {
        if ($paymentMethodCode == self::PAY_BY_INSTALMENTS_CODE) {
            return 3;
        }
        return intval(substr($paymentMethodCode, -1));
    }

    /**
     * @param $paymentMethodCode
     * @return bool
     */
    public function isInstallmentsPayment($paymentMethodCode) {
        $installmentPattern = "/^(pay_by_instalments)([_][0-9]+)?$/";
        return boolval(preg_match($installmentPattern, $paymentMethodCode));
    }

    public function updateTamaraOrders($orders, $fieldToUpdate, $value) {
        if (!empty($orders)) {
            $tamaraIds = [];
            foreach ($orders as $order) {
                $tamaraIds[] = $order['tamara_id'];
            }
            $sql = "UPDATE `".DB_PREFIX."tamara_orders` SET `{$fieldToUpdate}` = '{$value}' WHERE `tamara_id` IN (".implode(",", $tamaraIds).")";
            $this->db->query($sql);
        }
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function isPayWithTamara($orderId) {
        $order = $this->getOrder($orderId);
        if (empty($order)) {
            return false;
        }
        if (str_starts_with($order['payment_method']['code'], 'tamarapay')) {
            return true;
        }
        return false;
    }

    /**
     * @param $currencyCode
     * @return array
     */
    public function getSupportedCountriesByCurrency($currencyCode) {
        if (!isset(self::CURRENCIES_COUNTRIES_ALLOWED[$currencyCode])) {
            return [];
        } else {
            return explode(",", self::CURRENCIES_COUNTRIES_ALLOWED[$currencyCode]);
        }
    }

    /**
     * @param $countryCode
     * @param $currencyCode
     * @return string
     */
    private function getCacheKey($countryCode, $currencyCode) {
        return $countryCode . '-' . $currencyCode;
    }

    public function getCustomerCountryCodeFromSession() {
        if (!empty($this->session->data['shipping_address']['iso_code_2'])) {
            $countryCode = $this->session->data['shipping_address']['iso_code_2'];
        } else {
            if (!empty($this->session->data['payment_address']['iso_code_2'])) {
                $countryCode = $this->session->data['payment_address']['iso_code_2'];
            } else {
                $countryCode = $this->getStoreCountryCode();
            }
        }
        return strtoupper($countryCode);
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

    public function updatePaymentTypeAfterCheckout($tamaraOrder) {
        $numberOfInstallments = -1;
        $paymentType = "N/A";
        if ($this->isSingleCheckoutVersion()) {
            try {
                if ($this->getDisableTamara()) {
                    throw new \Exception("Tamara is disabled");
                }
                $response = $this->getTamaraClient()->getOrder(new \TMS\Tamara\Request\Order\GetOrderRequest($tamaraOrder['tamara_order_id']));
                if ($response->isSuccess()) {
                    $paymentType = $this->convertPaymentTypeFromTamaraToOpenCart($response->getPaymentType());
                    $numberOfInstallments = $response->getInstalments();
                    $this->db->query("UPDATE `" . DB_PREFIX . "tamara_orders` SET `payment_type` = '{$paymentType}', `number_of_installments` = '{$numberOfInstallments}' WHERE `tamara_order_id` = '{$tamaraOrder['tamara_order_id']}'");
                }
            } catch (\TMS\Tamara\Exception\RequestException $requestException) {
                $this->setDisableTamara(true);
            }
            catch (\Exception $exception) {
                $this->log(["Error when get Tamara Order from remote " . $exception->getMessage()]);
            }
        } else {
            $paymentType = $tamaraOrder['payment_type'];
            $numberOfInstallments = $tamaraOrder['number_of_installments'];
        }
        $comment = "Tamara - Payment type: " . $paymentType;
        if ($this->isInstallmentsPayment($paymentType)) {
            $comment .= (", number of installments: " . $numberOfInstallments);
        }
        $this->addOrderComment($tamaraOrder['order_id'], $this->config->get("payment_tamarapay_order_status_success_id"), $comment);
    }

    public function convertPaymentTypeFromTamaraToOpenCart($paymentType) {
        if ($paymentType == self::PAY_BY_INSTALMENTS) {
            return self::PAY_BY_INSTALMENTS_CODE;
        }
        if ($paymentType == self::PAY_NEXT_MONTH) {
            return self::PAY_NEXT_MONTH_CODE;
        }
        if ($paymentType == self::PAY_NOW) {
            return self::PAY_NOW_CODE;
        }
        if ($paymentType == self::PAY_LATER) {
            return self::PAY_LATER_CODE;
        }

        throw new \Exception("Wrong payment type!");
    }

    public function convertPaymentTypeFromOpenCartToTamara($paymentType) {
        if ($this->isInstallmentsPayment($paymentType)) {
            return self::PAY_BY_INSTALMENTS;
        }
        if ($paymentType == self::PAY_NEXT_MONTH_CODE) {
            return self::PAY_NEXT_MONTH;
        }
        if ($paymentType == self::PAY_NOW_CODE) {
            return self::PAY_NOW;
        }
        if ($paymentType == self::PAY_LATER_CODE) {
            return self::PAY_LATER;
        }
        if ($paymentType == self::SINGLE_CHECKOUT_CODE) {
            return self::PAY_BY_INSTALMENTS;
        }
        throw new \Exception("Wrong payment type!");
    }

    public function isSandboxMode() {
        return $this->getApiUrl() == self::SANDBOX_API_URL;
    }

    public function isExistPayNow($availableMethods) {
        foreach ($availableMethods as $method) {
            if ($this->isPayNowPayment($method['name'])) {
                return true;
            }
        }
        return false;
    }

    public function isPayNowPayment($paymentMethodCode) {
        return $paymentMethodCode == self::PAY_NOW_CODE;
    }

    /**
     * @param bool $val
     * @param int $lifeTime
     */
    public function setDisableTamara($val, $lifeTime = self::DISABLE_TAMARA_CACHE_LIFE_TIME) {
        $data = ['cached_time' => time(), 'life_time' => $lifeTime, 'value' => $val];
        $this->saveTamaraConfig(self::DISABLE_TAMARA_CACHE_IDENTIFIER, json_encode($data));
    }

    public function getDisableTamara() {
        if ($this->disableTamara !== null) {
            return $this->disableTamara;
        }
        $result = false;
        $data = $this->getTamaraCacheConfigFromDb(self::DISABLE_TAMARA_CACHE_IDENTIFIER);
        if (!empty($data)) {
            if (time() - $data['cached_time'] < $data['life_time']) {
                $result = $data['value'];
            }
        }
        $this->disableTamara = $result;
        return $this->disableTamara;
    }

    public function isSingleCheckoutVersion() {
        if (isset($this->session->data['single_checkout_enabled'])) {
            return $this->session->data['single_checkout_enabled'];
        }
        return boolval($this->getTamaraConfigValue('single_checkout_enabled'));
    }

    public function getPlatformDescription() {
        $platformDescription = self::PLATFORM . " " . VERSION . ", Plugin version: " . self::VERSION;
        if ($this->isSingleCheckoutVersion()) {
            $platformDescription .= " for Single checkout";
        }
        return $platformDescription;
    }

    public function getMerchantPublicKey() {
        $publicKey = $this->config->get("payment_tamarapay_merchant_public_key");
        if (!empty($publicKey)) {
            return $publicKey;
        }
        $publicKey = $this->getTamaraConfigValue('payment_tamarapay_merchant_public_key');
        if ($publicKey !== null) {
            $this->saveConfig('payment_tamarapay_merchant_public_key', $publicKey);
            return $publicKey;
        }
        if ($this->getDisableTamara()) {
            return "";
        }
        try {
            $request = new \TMS\Tamara\Request\Merchant\GetDetailsInfoRequest();
            $response = $this->getTamaraClient()->getMerchantDetailsInfo($request);
            if ($response->isSuccess()) {
                $publicKey = $response->getMerchant()->getPublicKey();
                $this->saveConfig('payment_tamarapay_merchant_public_key', $publicKey);
                $this->saveTamaraConfig('payment_tamarapay_merchant_public_key', $publicKey);
                return $publicKey;
            }
        } catch (\TMS\Tamara\Exception\RequestException $requestException) {
            $this->setDisableTamara(true);
        }
        catch (\Exception $exception) {
            $this->log(["Error when get merchant configs " . $exception->getMessage()]);
        }
        return "";
    }

    /**
     * @param bool $value
     */
    public function setSingleCheckoutVersion($value) {
        $this->session->data['single_checkout_enabled'] = $value;
        if (boolval($this->getTamaraConfigValue('single_checkout_enabled')) != $value) {
            $this->saveTamaraConfig('single_checkout_enabled', intval($value));
        }
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

  
}
