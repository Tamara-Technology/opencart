<?php

use Tamara\Client;
use Tamara\Configuration;
use Tamara\Model\Money;
use Tamara\Model\Order\Address;
use Tamara\Model\Order\Consumer;
use Tamara\Model\Order\Discount;
use Tamara\Model\Order\MerchantUrl;
use Tamara\Model\Order\Order;
use Tamara\Model\Order\OrderItem;
use Tamara\Model\Order\OrderItemCollection;
use Tamara\Model\Payment\Capture;
use Tamara\Model\ShippingInfo;
use Tamara\Request\Checkout\CreateCheckoutRequest;
use Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara\Request\Order\CancelOrderRequest;
use Tamara\Request\Payment\CaptureRequest;
use Tamara\Response\Payment\CancelResponse;

class ModelExtensionPaymentTamarapay extends Model
{
    public const
        COUNTRY_ISO = 'SA',
        MAX_LIMIT = 'max_limit',
        MIN_LIMIT = 'min_limit',
        NAME = 'name',
        TITLTE = 'title',
        PAY_LATER = 'PAY_BY_LATER',
        PAY_BY_INSTALMENTS = 'PAY_BY_INSTALMENTS',
        ENABLED = 'enabled',
        AMOUNT = 'amount',
        SA_CURRENCY = 'SAR',
        AE_CURRENCY = 'AED',
        CURRENCY = 'currency',
        PLATFORM = 'OpenCart',
        NO_DISCOUNT = 'nothing',
        NUMBER_OF_INSTALMENTS = 3,
        EMPTY_STRING = 'N/A',
        MAXIMUM_CACHED = 500,
        ORDER_PENDING_STATUS_ID = 1,
        TEXT_PAY_BY_LATER = 'text_pay_by_later',
        TEXT_PAY_BY_INSTALMENTS = 'text_pay_by_instalments';

    private const SUPPORTED_CURRENCIES = [
        self::SA_CURRENCY,
        self::AE_CURRENCY,
    ];

    private $orders = [];
    private $tamaraOrders = [];
    private $tamaraOrdersByOrderId = [];
    private $orderTotalsArr = [];

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/tamarapay');
        $method_data = array();
        if ($this->validateTamaraPaymentByAddress($address)) {
            $method_data = array(
                'code' => 'tamarapay',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('tamarapay_sort_order')
            );
        }
        return $method_data;
    }

    private function validateTamaraPaymentByAddress($address)
    {
        if (!$this->config->get('tamarapay_geo_zone_id')) {
            return true;
        }
        $zoneToGeoZoneRecords = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('tamarapay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
        if ($zoneToGeoZoneRecords->num_rows) {
            return true;
        }
        return false;
    }

    /**
     * Get tamara order by order id
     * @param $orderId
     * @param $forceReload
     * @return mixed
     */
    public function getTamaraOrder($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->tamaraOrdersByOrderId[$orderId])) {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "tamara_orders` WHERE `order_id` = '" . (int)$orderId . "' AND is_active = '1' LIMIT 1");
            $tamaraOrder = $query->row;
            if (empty($tamaraOrder)) {
                throw new InvalidArgumentException('Order requested does not exist');
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

    public function createCheckout($url, $token, $paymentType)
    {
        $this->log("Start create checkout");
        $this->load->language('extension/payment/tamarapay');

        if (!$this->isCurrencySupported()) {
            return ['error' => $this->language->get('error_wrong_currency')];
        }

        try {
            //deactivate recent session order
            $this->deactivateRecentTamaraOrder($this->session->data['order_id']);

            $client = Client::create(Configuration::create($url, $token));
            $request = new CreateCheckoutRequest($this->prepareOrder($paymentType));

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
                'redirect_url' => $redirectUrl
            ];

            $this->addTamaraOrder($saveData);
            $this->addOrderComment($saveData['order_id'], self::ORDER_PENDING_STATUS_ID, 'Tamara order was created, order id: '. $tamaraOrderId);

            $this->log(['msg' => 'Created tamara checkout',
                'order_id' => $saveData['order_id'],
                'tamara_order_id' => $tamaraOrderId,
                'redirect_url' => $redirectUrl
            ]);
            return ['redirectUrl' => $redirectUrl];

        } catch (Exception $exception) {
            $this->log($exception->getMessage());
            $errorMessage = !empty($exception->getMessage()) ? $exception->getMessage() : 'error_create_checkout';
            return ['error' => $this->language->get($errorMessage)];
        }
    }

    private function deactivateRecentTamaraOrder($opencartOrderId) {
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

    private function resetTamaraOrderCache() {
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
        return $this->session->data['currency'] ?? '';
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
        $order->setPaymentType($paymentType);
        $order->setPlatform(self::PLATFORM . " " . VERSION);
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
        $billing->setPhoneNumber($orderData['telephone']);
        $billing->setCountryCode($orderData['payment_iso_code_2']);

        $shipping->setFirstName($orderData['shipping_firstname']);
        $shipping->setLastName($orderData['shipping_lastname']);
        $shipping->setLine1($orderData['shipping_address_1']);
        $shipping->setLine2($orderData['shipping_address_2']);
        $shipping->setRegion($orderData['shipping_zone']);
        $shipping->setCity($orderData['shipping_city']);
        $shipping->setPhoneNumber($orderData['telephone']);
        $shipping->setCountryCode($orderData['shipping_iso_code_2']);

        $consumer->setFirstName($orderData['shipping_firstname']);
        $consumer->setLastName($orderData['shipping_lastname']);
        $consumer->setEmail($orderData['email']);
        $consumer->setPhoneNumber($orderData['telephone']);

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
        $order->setShippingAmount($this->formatMoney($this->getShippingAmount($orderTotals)));
        $order->setTaxAmount($this->formatMoney($this->getOrderTaxAmount($orderTotals)));
        $order->setDiscount($this->getOrderDiscount($orderTotals));

        return $order;
    }

    public function getOrder($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->orders[$orderId])) {
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($orderId);
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
        $price = round($price, 2);
        $price = (float)number_format($price, 2, '.', '');
        if (is_null($currencyCode)) {
            $currencyCode = $this->getCurrencyCodeFromSession();
            if (empty($currencyCode)) {
                throw new Exception("Currency does not exist");
            }
        }
        return new Money($price, $currencyCode);
    }

    private function getIsoCountryFromSession()
    {
        return $this->session->data['payment_address']['iso_code_2'] ?? '';
    }

    private function getOrderItems($orderId)
    {
        $orderProducts = $this->getOrderProducts($orderId);
        $items = [];
        foreach ($orderProducts as $orderProduct) {
            $productData = $this->getProductById($orderProduct['product_id']);
            $sku = empty($productDetails['sku']) ? $productData['product_id'] : $productData['sku'];
            $items[$orderProduct['order_product_id']] = [
                'order_item_id' => $orderProduct['order_product_id'],
                'product_id' => $orderProduct['product_id'],
                'total_amount' => $orderProduct['total'],
                'tax_amount' => $orderProduct['tax'],
                'discount_amount' => 0.00,
                'unit_price' => $orderProduct['price'],
                'name' => $orderProduct['name'],
                'sku' => $sku,
                'type' => $productData['model'],
                'reward' => $productData['reward'],
                'quantity' => $orderProduct['quantity'],
                'image_url' => $this->getProductImageUrl($productData['image']),
                'currency' => $this->getOrderCurrency($orderId)
            ];
        }
        return $items;
    }

    private function getOrderProducts($orderId)
    {
        $this->load->model('account/order');
        return $this->model_account_order->getOrderProducts($orderId);
    }

    private function getProductById($productId)
    {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getProduct($productId);
    }

    private function getProductImageUrl($relativeImagePath)
    {
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
     * @param array $items
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
     * @param $item
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
        $orderItem->setUnitPrice($this->formatMoney(($item['unit_price'])));
        $orderItem->setTotalAmount(new Money($item['total_amount'], $item['currency']));
        $orderItem->setTaxAmount(new Money($item['tax_amount'] ?? 0, $item['currency']));
        $orderItem->setDiscountAmount(new Money($item['discount_amount'] ?? 0, $item['currency']));
        $orderItem->setQuantity($item['quantity']);
        $orderItem->setImageUrl($item['image_url'] ?? '');

        return $orderItem;
    }

    public function getMerchantUrls()
    {
        $baseUrl = $this->getBaseUrl();
        $successUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/success';
        $failureUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/failure';
        $cancelUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/cancel';
        $notificationUrl = $baseUrl . 'index.php?route=extension/payment/tamarapay/notification';
        $result = [
            'success' => $successUrl,
            'failure' => $failureUrl,
            'cancel' => $cancelUrl,
            'notification' => $notificationUrl
        ];
        return $result;
    }

    public function getBaseUrl()
    {
        $isHttps = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === 0;
        if ($isHttps) {
            return HTTPS_SERVER;
        } else {
            return HTTP_SERVER;
        }
    }

    private function getOrderTotals($orderId, $forceReload = false)
    {
        if ($forceReload || empty($this->orderTotalsArr[$orderId])) {
            $this->load->model('account/order');
            $orderTotals = $this->model_account_order->getOrderTotals($orderId);
            $this->cacheOrderTotals($orderId, $orderTotals);
        }
        return $this->orderTotalsArr[$orderId];
    }

    private function cacheOrderTotals($orderId, $data)
    {
        return $this->cacheData($this->orderTotalsArr, $orderId, self::MAXIMUM_CACHED, $data);
    }

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
        if ($this->config->get('tamarapay_debug')) {
            $backtrace = debug_backtrace();
            $log = new Log('tamarapay.log');
            $log->write('(' . $backtrace[$class_step]['class'] . '::' . $backtrace[$function_step]['function'] . ') - ' . print_r($data,
                    true));
        }
    }

    public function addTamaraOrder($data)
    {
        $query = sprintf("INSERT INTO `%s` SET `order_id` = %d, `tamara_order_id` = '%s', `redirect_url` = '%s', `is_active` = '%s'",
            DB_PREFIX . 'tamara_orders', (int)$data['order_id'], $this->db->escape($data['tamara_order_id']),
            $data['redirect_url'], 1);
        $this->db->query($query);
    }

    public function authoriseOrder($tamaraOrderId)
    {
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('checkout/order');

        $url = $this->config->get('tamarapay_url');
        $token = $this->config->get('tamarapay_token');

        try {
            $client = Client::create(Configuration::create($url, $token));
            $request = new AuthoriseOrderRequest($tamaraOrderId);

            $response = $client->authoriseOrder($request);

            if (!$response->isSuccess()) {
                throw new Exception($response->getMessage());
            }

            $this->updateAuthoriseOrder($response->getOrderId());
            $tamaraOrder = $this->getTamaraOrderByTamaraOrderId($response->getOrderId());
            $this->addOrderComment($tamaraOrder['order_id'],
                $this->config->get('tamarapay_order_status_authorised_id'),
                'Order was authorized by Tamara, order id: ' . $response->getOrderId()
            );
            $this->log("Order was authorised, order id: " . $tamaraOrderId);
            return ['success' => true, 'message' => 'Order was authorised', 'order_id' => $tamaraOrderId];

        } catch (Exception $exception) {
            $this->log($exception->getMessage());
            return ['error' => $this->language->get('error_cannot_authorise')];
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
     * @param $tamaraOrderId
     * @param $forceReload
     * @return mixed
     */
    public function getTamaraOrderByTamaraOrderId($tamaraOrderId, $forceReload = false)
    {
        if ($forceReload || empty($this->tamaraOrders[$tamaraOrderId])) {
            $query = sprintf("SELECT * FROM `%s` WHERE `tamara_order_id` = '%s' AND is_active = '1' LIMIT 1", DB_PREFIX . 'tamara_orders',
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
        $methods = $this->getPaymentMethodsConfig();
        $orderTotal = $this->getOrderTotalFromSession();
        foreach ($methods as $method) {
            if (!$this->filterStatusMethod($method)) {
                continue;
            }
            if (!$this->filterUnderOverLimitAmount($orderTotal, $method)) {
                continue;
            }
            $result[] = $method;
        }
        return $result;
    }

    /**
     * Get Tamara payment methods from config
     * @return array
     */
    public function getPaymentMethodsConfig()
    {
        return [
            $this->getPayLaterConfig(),
            $this->getPayByInstallmentsConfig()
        ];
    }

    public function getPayLaterConfig()
    {
        return [
            self::NAME => self::PAY_LATER,
            self::ENABLED => $this->config->get('tamarapay_types_pay_by_later_enabled'),
            self::TITLTE => $this->language->get(self::TEXT_PAY_BY_LATER),
            self::MIN_LIMIT => $this->config->get('tamarapay_types_pay_by_later_min_limit'),
            self::MAX_LIMIT => $this->config->get('tamarapay_types_pay_by_later_max_limit'),
            self::CURRENCY => $this->config->get('tamarapay_types_pay_by_later_currency')
        ];
    }

    public function getPayByInstallmentsConfig()
    {
        return [
            self::NAME => self::PAY_BY_INSTALMENTS,
            self::ENABLED => $this->config->get('tamarapay_types_pay_by_instalments_enabled'),
            self::TITLTE => $this->language->get(self::TEXT_PAY_BY_INSTALMENTS),
            self::MIN_LIMIT => $this->config->get('tamarapay_types_pay_by_instalments_min_limit'),
            self::MAX_LIMIT => $this->config->get('tamarapay_types_pay_by_instalments_max_limit'),
            self::CURRENCY => $this->config->get('tamarapay_types_pay_by_instalments_currency')
        ];
    }

    private function getOrderTotalFromSession()
    {
        $order = $this->getOrder($this->getOrderIdFromSession());
        return $order['total'];
    }

    private function filterStatusMethod($method)
    {
        if (empty($method[self::ENABLED])) {
            return false;
        }
        return true;
    }

    private function filterUnderOverLimitAmount($orderTotal, $method)
    {
        if ($orderTotal < $method[self::MIN_LIMIT] || $orderTotal > $method[self::MAX_LIMIT]) {
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
        return $this->getPaymentsMethodsAvailableForPrice($this->getOrderTotalFromSession());
    }

    public function getPaymentsMethodsAvailableForPrice($price) {
        $result = [];
        $methods = $this->getPaymentMethodsConfig();
        $chosenMethod = false;
        foreach ($methods as $method) {
            if (!$this->filterStatusMethod($method)) {
                continue;
            }
            $isAvailable = true;
            if (!$this->filterUnderOverLimitAmount($price, $method)) {
                $isAvailable = false;
            }
            $method['is_available'] = $isAvailable;
            $method['checked'] = false;
            if ($isAvailable && $method[self::NAME] == self::PAY_BY_INSTALMENTS) {
                $method['checked'] = true;
                $chosenMethod = true;
            }
            $result[] = $method;
        }
        if (!$chosenMethod) {
            if (!empty($result)) {
                $result[0]['checked'] = true;
            }
        }

        return $result;
    }

    /**
     * @param $tamaraOrderId
     * @return bool
     */
    public function canCapture($tamaraOrderId) {
        $tamaraOrderData = $this->getTamaraOrderData($tamaraOrderId);
        $captures = $this->getCapturesByTamaraOrderId($tamaraOrderId);
        $totalAmountCaptured = 0.00;
        foreach ($captures as $capture) {
            $totalAmountCaptured += $capture['total_amount'];
        }
        if ($totalAmountCaptured >= $tamaraOrderData['total_amount']) {
            return false;
        }
        return true;
    }

    /**
     * @param $tamaraOrderId
     * @return mixed
     */
    public function getCapturesByTamaraOrderId($tamaraOrderId) {
        $sql = sprintf("SELECT * FROM %s WHERE tamara_order_id = '%s'", DB_PREFIX . "tamara_captures", $tamaraOrderId);
        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Capture tamara order
     * @param $tamaraOrderId
     * @return int|string|null
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

            $url = $this->config->get('tamarapay_url');
            $token = $this->config->get('tamarapay_token');
            $client = Client::create(Configuration::create($url, $token));
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
            $this->log(['msg' => 'Tamara order was captured',
                'capture_id' => $captureId,
                'order_id' => $tamaraOrderId
                ]);
            return $captureId;
        } catch (Exception $exception) {
            $this->log($exception->getMessage());
        }
    }

    /**
     * Get order data by tamaraOrderId
     * @param $tamaraOrderId
     * @return array
     */
    public function getTamaraOrderData($tamaraOrderId)
    {
        $result = [];
        $tamaraOrder = $this->getTamaraOrderByTamaraOrderId($tamaraOrderId);
        if (count($tamaraOrder)) {
            $orderId = $tamaraOrder['order_id'];
            $orderData = $this->getOrder($orderId);
            $result['order_id'] = $orderId;
            $result['tamara_order_id'] = $tamaraOrder['tamara_order_id'];
            $result['total_amount'] = $orderData['total'];
            $result['items'] = $this->getOrderItems($orderId);
            $orderTotals = $this->getOrderTotals($orderId);
            $result['tax_amount'] = $this->getOrderTaxAmount($orderTotals);
            $result['shipping_amount'] = $this->getShippingAmount($orderTotals);
            $result['discount_amount'] = $this->getDiscountAmount($orderTotals);
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
                'tracking_number' => $trackNumber
            ];
            $result['order_status_id'] = $orderData['order_status_id'];
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
        return $result;
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getShippingData($orderId)
    {
        return [];
    }

    /**
     * Create capture request from order data
     * @param array $orderData
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
            new Money($orderData['total_amount'], $orderData['currency']),
            new Money($orderData['shipping_amount'], $orderData['currency']),
            new Money($orderData['tax_amount'], $orderData['currency']),
            new Money($orderData['discount_amount'], $orderData['currency']),
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
                    $orderData['product_id'],
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

            $this->db->query($query);
        }
    }

    /**
     * Add order comment without model
     * @param $orderId
     * @param $orderStatusId
     * @param $comment
     * @param int $notify
     */
    public function addOrderComment($orderId, $orderStatusId, $comment, $notify = 0)
    {
        $sql = "INSERT INTO ". DB_PREFIX ."order_history (order_history_id, order_id, order_status_id, notify, comment, date_added) VALUES(null, {$orderId}, {$orderStatusId}, {$notify}, '{$comment}', NOW())";
        $this->db->query($sql);

        $sql = "UPDATE `". DB_PREFIX ."order` SET order_status_id='{$orderStatusId}' WHERE order_id = '{$orderId}'";
        $this->db->query($sql);
    }

    /**
     * @param $tamaraOrderId
     * @return string|null
     * @throws Exception
     */
    public function cancelOrder($tamaraOrderId)
    {
        $this->log("Start cancel order " . $tamaraOrderId);
        $this->load->language('extension/payment/tamarapay');
        $this->load->model('checkout/order');
        $url = $this->config->get('tamarapay_url');
        $token = $this->config->get('tamarapay_token');

        try {
            $client = Client::create(Configuration::create($url, $token));
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
            $comment = 'Tamara order was canceled, cancel id: ' . $cancelId;
            $this->addOrderComment($orderData['order_id'], $orderData['order_status_id'], $comment, 0);
            $this->log("Order " . $tamaraOrderId . " was canceled. Cancel id: " . $cancelId);
            return $cancelId;
        } catch (Exception $exception) {
            $this->log($exception->getMessage());
        }
    }

    public function createCancelRequest(array $orderData): CancelOrderRequest
    {
        return new CancelOrderRequest(
            $orderData['tamara_order_id'],
            new Money($orderData['total_amount'], $orderData['currency']),
            $this->getOrderItemCollection($orderData['items']),
            new Money($orderData['shipping_amount'], $orderData['currency']),
            new Money($orderData['tax_amount'], $orderData['currency']),
            new Money($orderData['discount_amount'], $orderData['currency'])
        );
    }

    /**
     * @param array $orderData
     * @param $cancelRequest
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
}
