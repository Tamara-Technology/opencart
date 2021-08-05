<?php

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

class ModelExtensionPaymentTamarapay extends Model
{
    /**
     * Define version of extension
     */
    public const VERSION = '1.7.0';

    public const
        MAX_LIMIT = 'max_limit',
        MIN_LIMIT = 'min_limit',
        NAME = 'name',
        TITLE = 'title',
        PAY_LATER = 'PAY_BY_LATER',
        PAY_BY_INSTALMENTS = 'PAY_BY_INSTALMENTS',
        ENABLED = 'enabled',
        AMOUNT = 'amount',
        SA_CURRENCY = 'SAR',
        AE_CURRENCY = 'AED',
        CURRENCY = 'currency',
        PLATFORM = 'OpenCart',
        NO_DISCOUNT = 'nothing',
        EMPTY_STRING = 'N/A',
        MAXIMUM_CACHED = 500,
        ORDER_PENDING_STATUS_ID = 1;

    const PAY_LATER_CODE = 'pay_by_later';
    const PAY_BY_INSTALMENTS_CODE = 'pay_by_instalments';
    const PAY_BY_INSTALMENTS_4_CODE = 'pay_by_instalments_4';
    const PAY_BY_INSTALMENTS_5_CODE = 'pay_by_instalments_5';
    const PAY_BY_INSTALMENTS_6_CODE = 'pay_by_instalments_6';
    const PAY_BY_INSTALMENTS_7_CODE = 'pay_by_instalments_7';
    const PAY_BY_INSTALMENTS_8_CODE = 'pay_by_instalments_8';
    const PAY_BY_INSTALMENTS_9_CODE = 'pay_by_instalments_9';
    const PAY_BY_INSTALMENTS_10_CODE = 'pay_by_instalments_10';
    const PAY_BY_INSTALMENTS_11_CODE = 'pay_by_instalments_11';
    const PAY_BY_INSTALMENTS_12_CODE = 'pay_by_instalments_12';

    const WEBHOOK_URL = 'index.php?route=extension/payment/tamarapay/webhook', ALLOWED_WEBHOOKS = ['order_expired', 'order_declined'];
    const PAYMENT_TYPES_CACHED_TIME = 1800;
    const SANDBOX_API_URL = "https://api-sandbox.tamara.co";
    const SANDBOX_API_ENVIRONMENT = "1";
    const PRODUCTION_API_URL = "https://api.tamara.co";
    const PRODUCTION_API_ENVIRONMENT = "2";

    private const SUPPORTED_CURRENCIES = [
        self::SA_CURRENCY,
        self::AE_CURRENCY,
    ];

    private const SUPPORTED_COUNTRIES = [
        'SA', 'AE'
    ];

    private $orders = [];
    private $tamaraOrders = [];
    private $tamaraOrdersByOrderId = [];
    private $orderTotalsArr = [];
    private $currencies = [];

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/tamarapay');
        if (!$this->isTamaraAvailableForThisCustomer()) {
            return [];
        }
        $method_data = array();
        if ($this->validateTamaraPaymentByAddress($address)) {
            $method_data = array(
                'code' => 'tamarapay',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('payment_tamarapay_sort_order'),
            );
        }

        return $method_data;
    }

    private function validateTamaraPaymentByAddress($address)
    {
        //validate country
        $addressCountryCode = strtoupper($address['iso_code_2'] ?? "");
        if (!in_array($addressCountryCode, self::SUPPORTED_COUNTRIES) || $addressCountryCode != $this->getStoreCountryCode()) {
            return false;
        }

        //validate geo zone
        if (!$this->config->get('payment_tamarapay_geo_zone_id')) {
            return true;
        }
        $zoneToGeoZoneRecords = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('payment_tamarapay_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");
        if ($zoneToGeoZoneRecords->num_rows) {
            return true;
        }

        return false;
    }

    public function getStoreCountry() {
        $this->load->model('localisation/country');
        return $this->model_localisation_country->getCountry($this->config->get('config_country_id'));
    }

    public function getStoreCountryCode() {
        return strtoupper($this->getStoreCountry()['iso_code_2']);
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
                throw new InvalidArgumentException("Order requested does not exist");
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
        $this->log("Start create checkout");
        $this->load->language('extension/payment/tamarapay');

        if (!$this->isCurrencySupported()) {
            return ['error' => $this->language->get('error_wrong_currency')];
        }

        try {
            //deactivate recent session order
            $this->deactivateRecentTamaraOrder($this->session->data['order_id']);

            $client = $this->getTamaraClient();
            $this->log(["Session data: "]);
            $this->log($this->session->data);
            $order = $this->prepareOrder($paymentType);
            $this->log(["Prepare order " . $this->session->data['order_id']]);
            $this->log($order->toArray());
            $request = new CreateCheckoutRequest($order);

            $response = $client->createCheckout($request);

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
                'order_id' => $this->session->data['order_id'],
                'tamara_order_id' => $tamaraOrderId,
                'redirect_url' => $redirectUrl,
            ];

            $this->addTamaraOrder($saveData);
            $this->addOrderComment($saveData['order_id'], $this->config->get("payment_tamarapay_order_status_create_id"),
                'Tamara order was created, order id: ' . $tamaraOrderId);

            $this->log([
                'msg' => 'Created tamara checkout',
                'order_id' => $saveData['order_id'],
                'tamara_order_id' => $tamaraOrderId,
                'redirect_url' => $redirectUrl,
            ]);

            return ['redirectUrl' => $redirectUrl];

        } catch (Exception $exception) {
            $this->log($exception->getMessage());

            $errorMessage = !empty($exception->getMessage()) ? $exception->getMessage() : 'error_create_checkout';

            return ['error' => $this->language->get($errorMessage)];
        }
    }

    private function deactivateRecentTamaraOrder($opencartOrderId)
    {
        try {
            $tamaraOrder = $this->getTamaraOrder($opencartOrderId);
        } catch (Exception $exception) {
            return;
        }

        try {
            $this->cancelOrder($tamaraOrder['tamara_order_id']);
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

    private function getCurrencyCodeFromSession()
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

    private function prepareOrder($paymentType)
    {
        $orderData = $this->getOrder($this->getOrderIdFromSession());
        $orderId = $orderData['order_id'];
        $order = new Order();

        $order->setOrderReferenceId($orderData['order_id']);
        $order->setLocale($this->session->data['language'] ?? null);
        $order->setCurrency($this->getCurrencyCodeFromSession());
        $order->setTotalAmount($this->formatMoney($orderData['total']));
        $order->setCountryCode($this->getIsoCountryFromSession());
        if ($this->isInstallmentsPayment($paymentType)) {
            $order->setInstalments($this->getInstallmentsNumberByPaymentCode($paymentType));
        }
        if ($paymentType == self::PAY_LATER_CODE) {
            $paymentType = self::PAY_LATER;
        } else {
            $paymentType = self::PAY_BY_INSTALMENTS;
        }

        $order->setPaymentType($paymentType);
        $order->setPlatform(self::PLATFORM . " " . VERSION . ", Plugin version: " . self::VERSION);
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
        $billing->setRegion($orderData['payment_zone']);
        $billing->setCity($orderData['payment_city']);
        $phoneNumber = preg_replace("/\s+/", "", $orderData['telephone']);
        $billing->setPhoneNumber($phoneNumber);
        $billing->setCountryCode($orderData['payment_iso_code_2']);

        $shipping->setFirstName($orderData['shipping_firstname']);
        $shipping->setLastName($orderData['shipping_lastname']);
        $shipping->setLine1($orderData['shipping_address_1']);
        $shipping->setLine2($orderData['shipping_address_2']);
        $shipping->setRegion($orderData['shipping_zone']);
        $shipping->setCity($orderData['shipping_city']);
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
        $order->setShippingAmount($this->getShippingAmount($orderTotals));
        $order->setTaxAmount($this->getOrderTaxAmount($this->getOrderTotals($orderId)));
        $order->setDiscount($this->getOrderDiscount($orderTotals));

        return $order;
    }

    public function getOrder($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->orders[$orderId])) {
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($orderId);
            $order['total_in_currency'] = $this->getValueInCurrency($order['total'], $order['currency_code']);
            $this->cacheData($this->orders, $orderId, self::MAXIMUM_CACHED, $order);
        }

        return $this->orders[$orderId];
    }

    public function getOrderIdFromSession()
    {
        return $this->session->data['order_id'];
    }

    private function formatMoney($price, $currencyCode = null)
    {
        if (is_null($currencyCode)) {
            $currencyCode = $this->getCurrencyCodeFromSession();
            if (empty($currencyCode)) {
                throw new Exception("Currency does not exist");
            }
        }
        $price = $this->getValueInCurrency($price, $currencyCode);
        $price = round($price, 2);
        $price = (float) number_format($price, 2, '.', '');
        return new Money($price, $currencyCode);
    }

    private function getIsoCountryFromSession()
    {
        return strtoupper($this->session->data['payment_address']['iso_code_2'] ?? $this->session->data['shipping_address']['iso_code_2'] ?? '');
    }

    private function getOrderItems($orderId)
    {
        $orderProducts = $this->getOrderProducts($orderId);
        $items = [];
        foreach ($orderProducts as $orderProduct) {
            $productData = $this->getProductById($orderProduct['product_id']);
            $sku = empty($productDetails['sku']) ? $productData['product_id'] : $productData['sku'];
            $itemType = empty($productData['model']) ? "simple product" : $productData['model'];
            $items[$orderProduct['order_product_id']] = [
                'order_item_id' => $orderProduct['order_product_id'],
                'product_id' => $orderProduct['product_id'],
                'total_amount' => $orderProduct['total'],
                'tax_amount' => $orderProduct['tax'],
                'discount_amount' => 0.00,
                'unit_price' => $orderProduct['price'],
                'name' => $orderProduct['name'],
                'sku' => $sku,
                'type' => $itemType,
                'reward' => $productData['reward'],
                'quantity' => $orderProduct['quantity'],
                'image_url' => $this->getProductImageUrl($productData['image']),
                'currency' => $this->getOrderCurrency($orderId),
            ];
        }

        return $items;
    }

    private function getOrderProducts($orderId)
    {
        return $this->model_checkout_order->getOrderProducts($orderId);
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
        $orderItem->setUnitPrice($this->formatMoney($item['unit_price'], $item['currency']));
        $orderItem->setTotalAmount($this->formatMoney($item['total_amount'], $item['currency']));
        $orderItem->setTaxAmount($this->formatMoney($item['tax_amount'] ?? 0, $item['currency']));
        $orderItem->setDiscountAmount($this->formatMoney($item['discount_amount'] ?? 0, $item['currency']));
        $orderItem->setQuantity($item['quantity']);
        $orderItem->setImageUrl($item['image_url'] ?? '');

        return $orderItem;
    }

    public function getMerchantUrls()
    {
        $baseUrl = $this->getBaseUrl();
        $successUrl = $this->config->get('payment_tamarapay_checkout_success_url');
        if (empty($successUrl)) {
            $successUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/success';
        }
        $failureUrl = $this->config->get('payment_tamarapay_checkout_failure_url');
        if (empty($failureUrl)) {
            $failureUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/failure';
        }
        $cancelUrl = $this->config->get('payment_tamarapay_checkout_cancel_url');
        if (empty($cancelUrl)) {
            $cancelUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/cancel';
        }
        $notificationUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/notification';
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
        return HTTPS_SERVER;
    }

    private function getOrderTotals($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->orderTotalsArr[$orderId])) {
            $this->load->model('checkout/order');
            $orderTotals = $this->model_checkout_order->getOrderTotals($orderId);
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

        return $this->formatMoney($result);
    }

    private function getOrderTaxAmount(array $orderTotals)
    {
        $result = 0.00;
        foreach ($orderTotals as $orderTotal) {
            if ($orderTotal['code'] == 'tax') {
                $result += $orderTotal['value'];
            }
        }

        return $this->formatMoney($result);
    }

    private function getOrderDiscount($orderTotals): Discount
    {
        $name = "";
        $amount = 0.00;
        foreach ($orderTotals as $orderTotal) {
            if ($orderTotal['code'] == "coupon" || $orderTotal['code'] == "voucher") {
                if (empty($name)) {
                    $name .= $orderTotal['title'];
                } else {
                    $name .= " | " . $orderTotal['title'];
                }
                $amount += ($orderTotal['value'] * -1);
            }
        }
        if (empty($name)) {
            $name = self::NO_DISCOUNT;
        }

        return new Discount($name, $this->formatMoney($amount));
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
            $log = new Log('tamarapay.log');
            $log->write('(' . $backtrace[$class_step]['class'] . '::' . $backtrace[$function_step]['function'] . ') - ' . print_r($data,
                    true));
        }
    }

    public function addTamaraOrder($data)
    {
        $query = sprintf("INSERT INTO `%s` SET `order_id` = %d, `tamara_order_id` = '%s', `redirect_url` = '%s', `is_active` = '%s'",
            DB_PREFIX . 'tamara_orders', (int) $data['order_id'], $this->db->escape($data['tamara_order_id']),
            $data['redirect_url'], 1);
        $this->db->query($query);
    }

    public function authoriseOrder($tamaraOrderId)
    {
        $this->log('Start authorise order, order id: ' . $tamaraOrderId);
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('checkout/order');

        try {
            $client = $this->getTamaraClient();
            $request = new AuthoriseOrderRequest($tamaraOrderId);

            $response = $client->authoriseOrder($request);

            if (!$response->isSuccess()) {
                throw new Exception($response->getMessage());
            }

            $this->updateAuthoriseOrder($response->getOrderId());
            $orderTamara = $this->getTamaraOrderByTamaraOrderId($response->getOrderId());
            $this->addOrderComment($orderTamara['order_id'],
                $this->config->get('payment_tamarapay_order_status_authorised_id'),
                'Order was authorised by Tamara, order id: ' . $response->getOrderId()
            );
            $this->log("Order was authorised, order id: " . $tamaraOrderId);

            return ['success' => true, 'message' => 'Order was authorised', 'order_id' => $tamaraOrderId];

        } catch (Exception $exception) {
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

    /**
     * Get tamara order by tamara order id
     *
     * @param $tamaraOrderId
     * @param $forceReload
     *
     * @return mixed
     */
    public function getTamaraOrderByTamaraOrderId($tamaraOrderId, $forceReload = false)
    {
        if ($forceReload || empty($this->tamaraOrders[$tamaraOrderId])) {
            $query = sprintf("SELECT * FROM `%s` WHERE `tamara_order_id` = '%s' AND `is_active` = '1' LIMIT 1",
                DB_PREFIX . 'tamara_orders',
                $tamaraOrderId);
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

    /**
     * Get available methods for current session order
     * @return array
     */
    public function getAvailableMethodForCurrentOrder()
    {
        $result = [];
        $methods = $this->getCurrentAvailableMethods();
        $orderTotal = $this->getOrderTotalFromSession();
        foreach ($methods as $method) {
            if (!$this->isInLimitAmount($orderTotal, $method)) {
                continue;
            }
            $result[] = $method;
        }

        return $result;
    }

    private function getOrderTotalFromSession()
    {
        $order = $this->getOrder($this->getOrderIdFromSession());
        return $order['total_in_currency'];
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
        $methods = $this->markInLimit($this->getCurrentAvailableMethods(), $this->getOrderTotalFromSession());
        if ($this->config->get("payment_tamarapay_enable_under_over_warning")) {
            return $methods;
        } else {
            return $this->filterUnderOver($methods, $this->getOrderTotalFromSession());
        }
    }

    public function getCurrentAvailableMethods() {
        if (!$this->isTamaraEnabled()) {
            return [];
        }
        if (!$this->isTamaraAvailableForThisCustomer()) {
            return [];
        }
        return $this->getPaymentTypes();
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

    public function markInLimit($methods, $price) {
        $result = [];
        $isExistChecked = false;
        $firstMethodNameInLimit = "";
        $isExistMethodInLimit = false;
        foreach ($methods as $method) {
            $method['checked'] = false;
            $method['is_in_limit'] = $this->isInLimitAmount($price, $method);
            if ($method['is_in_limit']) {
                $isExistMethodInLimit = true;
                $firstMethodNameInLimit = $method['name'];
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

    public function getPaymentMethodAvailableForPrice($price) {
        $methods = $this->filterUnderOver($this->markInLimit($this->getCurrentAvailableMethods(), $price), $price);
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
        if ($totalAmountCaptured >= floatval($tamaraOrderData['total_amount'])) {
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
        $this->log("Start to capture order " . $tamaraOrderId);
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('checkout/order');

        try {
            if (!$this->canCapture($tamaraOrderId)) {
                throw new Exception("Order {$tamaraOrderId} cannot be captured");
            }

            $client = $this->getTamaraClient();
            $orderData = $this->getTamaraOrderData($tamaraOrderId);
            $captureRequest = $this->createCaptureRequest($orderData);
            $this->log("Capture order data: ");
            $this->log($orderData);
            $response = $client->capture($captureRequest);
            if (!$response->isSuccess()) {
                throw new Exception($response->getMessage());
            }
            $captureId = $response->getCaptureId();
            $orderData['capture_id'] = $captureId;
            $this->saveCapture($orderData);
            $this->saveCaptureItems($orderData);
            $comment = 'Order was captured by Tamara, capture id: ' . $captureId;
            $this->addOrderComment($orderData['order_id'], $orderData['order_status_id'], $comment, 0);
            $this->log([
                'msg' => 'Tamara order was captured',
                'capture_id' => $captureId,
                'order_id' => $tamaraOrderId,
            ]);

            return $captureId;
        } catch (Exception $exception) {
            $this->log("Error when capture order " . $tamaraOrderId . ": " . $exception->getMessage());
        }
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
            $result['order_id'] = $orderId;
            $result['tamara_order_id'] = $tamaraOrderData['tamara_order_id'];
            $result['total_amount'] = $orderData['total'];
            $result['items'] = $this->getOrderItems($orderId);
            $orderTotals = $this->getOrderTotals($orderId);
            $result['tax_amount'] = $this->getOrderTaxAmount($orderTotals)->getAmount();
            $result['shipping_amount'] = $this->getShippingAmount($orderTotals)->getAmount();
            $result['discount_amount'] = $this->getDiscountAmount($orderTotals)->getAmount();
            $result['currency'] = $orderData['currency_code'];
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
            $result['refunded_from_console'] = $tamaraOrderData['refunded_from_console'];
        }

        return $result;

    }

    private function getDiscountAmount($orderTotals)
    {
        $result = 0;
        foreach ($orderTotals as $total) {
            if ($total['code'] == 'coupon' || $total['code'] == 'voucher') {
                $result += $total['value'];
            }
        }

        return $this->formatMoney($result);
    }

    /**
     * @param $orderId
     *
     * @return mixed
     */
    public function getShippingData($orderId)
    {
        $query = sprintf("select * from %sorder_shipment oos 
                    inner join %sshipping_courier osc 
                    on oos.shipping_courier_id = osc.shipping_courier_id 
                    where oos.order_id  = '%s'", DB_PREFIX, DB_PREFIX, $orderId);

        return $this->db->query($query)->rows;
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
            $this->formatMoney($orderData['total_amount'], $orderData['currency']),
            $this->formatMoney($orderData['shipping_amount'], $orderData['currency']),
            $this->formatMoney($orderData['tax_amount'], $orderData['currency']),
            $this->formatMoney($orderData['discount_amount'], $orderData['currency']),
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
            $orderData['total_amount'],
            $orderData['tax_amount'],
            $orderData['shipping_amount'],
            $orderData['discount_amount'],
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
                    $item['unit_price'],
                    $item['total_amount'],
                    $item['tax_amount'],
                    $item['discount_amount'],
                    "NOW()",
                    "NOW()"
                );
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
                    $item['unit_price'],
                    $item['total_amount'],
                    $item['tax_amount'],
                    $item['discount_amount'],
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
            if (!$this->canCancel($tamaraOrderId)) {
                throw new Exception("Order {$tamaraOrderId} cannot be canceled");
            }

            $this->log("Start cancel order " . $tamaraOrderId);
            $this->load->language('extension/payment/tamarapay');
            $this->load->model('checkout/order');
            $client = $this->getTamaraClient();
            $orderData = $this->getTamaraOrderData($tamaraOrderId);

            /**
             * @var $cancelRequest CancelOrderRequest
             */
            $cancelRequest = $this->createCancelRequest($orderData);

            /**
             * @var $response CancelResponse
             */
            $response = $client->cancelOrder($cancelRequest);
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
        } catch (Exception $exception) {
            $this->log("Error when cancel order " . $tamaraOrderId . ": " . $exception->getMessage());
        }
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
            $this->formatMoney($orderData['total_amount'], $orderData['currency']),
            $this->getOrderItemCollection($orderData['items']),
            $this->formatMoney($orderData['shipping_amount'], $orderData['currency']),
            $this->formatMoney($orderData['tax_amount'], $orderData['currency']),
            $this->formatMoney($orderData['discount_amount'], $orderData['currency'])
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

        return Client::create(Configuration::create($url, $token));
    }

    public function getTamaraOrderFromRemote($orderId) {
        return $this->getTamaraClient()->getOrderByReferenceId(new \TMS\Tamara\Request\Order\GetOrderByReferenceIdRequest($orderId));
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
                'Webhook reference order id: ' => $webhookMessage->getOrderReferenceId(),
            ]);
            throw new \Exception($this->language->get("error_webhook_event_type_not_allowed"));
        }
        $comment = sprintf('Tamara - order was %s by webhook', $eventType);
        $this->addOrderComment($webhookMessage->getOrderReferenceId(), $this->config->get("payment_tamarapay_order_status_canceled_id"), $comment, 0);
        $this->log(["Webhook processed successful for order " . $webhookMessage->getOrderReferenceId()]);
    }

    public function createNotificationService($notificationToken) {
        return \TMS\Tamara\Notification\NotificationService::create($notificationToken);
    }

    public function getNotificationService() {
        return $this->createNotificationService($this->config->get("payment_tamarapay_token_notification"));
    }

    /**
     * Get Tamara payment types
     * @return array
     * @throws Exception
     */
    public function getPaymentTypes()
    {
        try {
            $cachedPaymentTypes = $this->getCachedPaymentTypes();
            if (empty($cachedPaymentTypes)) {

                /**
                 * @var $response \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse
                 */
                $response = $this->getPaymentTypesOfClient($this->getTamaraClient());

                if (!$response->isSuccess()) {
                    throw new Exception($response->getMessage());
                }

                $cachedPaymentTypes['payment_types'] = $this->parsePaymentTypesFromResponse($response);
                $this->cachePaymentTypes($cachedPaymentTypes);
            }
            $paymentTypes = $this->filterEnablePaymentMethods($cachedPaymentTypes['payment_types']);

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

    private function filterEnablePaymentMethods($paymentTypes) {

        foreach ($paymentTypes as $paymentType) {
            $methodCode = $paymentType['name'];
            $configKey = "payment_tamarapay_types_" . $methodCode . "_enabled";
            if (!$this->config->get($configKey)) {
                unset($paymentTypes[$methodCode]);
            }
        }
        return $paymentTypes;
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
                if ($paymentTypeClone->getName() == self::PAY_LATER) {
                    $result[self::PAY_LATER_CODE] = [
                        'name' => self::PAY_LATER_CODE,
                        'min_limit' => $paymentTypeClone->getMinLimit()->getAmount(),
                        'max_limit' => $paymentTypeClone->getMaxLimit()->getAmount(),
                        'currency' => $paymentTypeClone->getMinLimit()->getCurrency(),
                        'description' => $paymentTypeClone->getDescription(),
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
                                'description' => $description
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
     */
    private function cachePaymentTypes($paymentTypes) {
        $paymentTypes['cached_time'] = time();
        $this->savePaymentTypesToDb(json_encode($paymentTypes));
    }

    /**
     * @return array
     */
    private function getCachedPaymentTypes() {
        $dbPaymentTypes = $this->getPaymentTypesFromDb();
        if (isset($dbPaymentTypes['cached_time'])) {
            if ((time() - intval($dbPaymentTypes['cached_time'])) > self::PAYMENT_TYPES_CACHED_TIME) {
                return [];
            } else {
                return $dbPaymentTypes;
            }
        }
        return [];
    }

    /**
     * @param \TMS\Tamara\Client $client
     * @return mixed
     * @throws \TMS\Tamara\Exception\RequestDispatcherException
     */
    public function getPaymentTypesOfClient($client) {
        return $client->getPaymentTypes($this->getStoreCountryCode());
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
}
