<?php

namespace TMS\Tamara;

use TMS\Tamara\Request\Checkout\CheckPaymentOptionsAvailabilityRequest;
use TMS\Tamara\Request\Merchant\GetDetailsInfoRequest;
use TMS\Tamara\Response\Checkout\CheckPaymentOptionsAvailabilityResponse;
use TMS\Tamara\Response\Merchant\GetDetailsInfoResponse;
use TMS\Tamara\HttpClient\HttpClient;
use TMS\Tamara\Request\Checkout\CreateCheckoutRequest;
use TMS\Tamara\Request\Checkout\GetPaymentTypesRequest;
use TMS\Tamara\Request\Order\AuthoriseOrderRequest;
use TMS\Tamara\Request\Order\CancelOrderRequest;
use TMS\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use TMS\Tamara\Request\Order\GetOrderRequest;
use TMS\Tamara\Request\Order\UpdateReferenceIdRequest;
use TMS\Tamara\Request\Payment\CaptureRequest;
use TMS\Tamara\Request\Payment\RefundRequest;
use TMS\Tamara\Request\RequestDispatcher;
use TMS\Tamara\Request\Webhook\RegisterWebhookRequest;
use TMS\Tamara\Request\Webhook\RemoveWebhookRequest;
use TMS\Tamara\Request\Webhook\RetrieveWebhookRequest;
use TMS\Tamara\Request\Webhook\UpdateWebhookRequest;
use TMS\Tamara\Response\Checkout\CreateCheckoutResponse;
use TMS\Tamara\Response\Checkout\GetPaymentTypesResponse;
use TMS\Tamara\Response\Order\AuthoriseOrderResponse;
use TMS\Tamara\Response\Order\GetOrderByReferenceIdResponse;
use TMS\Tamara\Response\Order\GetOrderResponse;
use TMS\Tamara\Response\Order\UpdateReferenceIdResponse;
use TMS\Tamara\Response\Payment\CancelResponse;
use TMS\Tamara\Response\Payment\CaptureResponse;
use TMS\Tamara\Response\Payment\RefundResponse;
use TMS\Tamara\Response\Webhook\RegisterWebhookResponse;
use TMS\Tamara\Response\Webhook\RemoveWebhookResponse;
use TMS\Tamara\Response\Webhook\RetrieveWebhookResponse;
use TMS\Tamara\Response\Webhook\UpdateWebhookResponse;
class Client
{
    /**
     * @var string
     */
    public const VERSION = '1.3.3';
    /**
     * @var HttpClient
     */
    protected $httpClient;
    /**
     * @var RequestDispatcher
     */
    private $requestDispatcher;
    /**
     * @param Configuration $configuration
     *
     * @return Client
     */
    public static function create(\TMS\Tamara\Configuration $configuration) : \TMS\Tamara\Client
    {
        return new static($configuration->createHttpClient());
    }
    /**
     * @param HttpClient $httpClient
     */
    public function __construct(\TMS\Tamara\HttpClient\HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->requestDispatcher = new \TMS\Tamara\Request\RequestDispatcher($httpClient);
    }

    /**
     * @param string $countryCode
     * @param string $currency
     *
     * @param float $orderValue
     * @param string $phoneNumber
     * @return GetPaymentTypesResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function getPaymentTypes(string $countryCode, string $currency = '', float $orderValue = 0.00, string $phoneNumber = '') : \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse
    {
        return $this->requestDispatcher->dispatch(new \TMS\Tamara\Request\Checkout\GetPaymentTypesRequest($countryCode, $currency, $orderValue, $phoneNumber));
    }
    /**
     * @param CreateCheckoutRequest $createCheckoutRequest
     *
     * @return CreateCheckoutResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function createCheckout(\TMS\Tamara\Request\Checkout\CreateCheckoutRequest $createCheckoutRequest) : \TMS\Tamara\Response\Checkout\CreateCheckoutResponse
    {
        return $this->requestDispatcher->dispatch($createCheckoutRequest);
    }
    /**
     * @param AuthoriseOrderRequest $authoriseOrderRequest
     *
     * @return AuthoriseOrderResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function authoriseOrder(\TMS\Tamara\Request\Order\AuthoriseOrderRequest $authoriseOrderRequest) : \TMS\Tamara\Response\Order\AuthoriseOrderResponse
    {
        return $this->requestDispatcher->dispatch($authoriseOrderRequest);
    }
    /**
     * @param CancelOrderRequest $cancelOrderRequest
     *
     * @return CancelResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function cancelOrder(\TMS\Tamara\Request\Order\CancelOrderRequest $cancelOrderRequest) : \TMS\Tamara\Response\Payment\CancelResponse
    {
        return $this->requestDispatcher->dispatch($cancelOrderRequest);
    }
    /**
     * @param CaptureRequest $captureRequest
     *
     * @return CaptureResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function capture(\TMS\Tamara\Request\Payment\CaptureRequest $captureRequest) : \TMS\Tamara\Response\Payment\CaptureResponse
    {
        return $this->requestDispatcher->dispatch($captureRequest);
    }
    /**
     * @param RefundRequest $refundRequest
     *
     * @return RefundResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function refund(\TMS\Tamara\Request\Payment\RefundRequest $refundRequest) : \TMS\Tamara\Response\Payment\RefundResponse
    {
        return $this->requestDispatcher->dispatch($refundRequest);
    }
    /**
     * @param RegisterWebhookRequest $request
     * @return RegisterWebhookResponse
     * @throws Exception\RequestDispatcherException
     */
    public function registerWebhook(\TMS\Tamara\Request\Webhook\RegisterWebhookRequest $request) : \TMS\Tamara\Response\Webhook\RegisterWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
    /**
     * @param RetrieveWebhookRequest $request
     *
     * @return RetrieveWebhookResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function retrieveWebhook(\TMS\Tamara\Request\Webhook\RetrieveWebhookRequest $request) : \TMS\Tamara\Response\Webhook\RetrieveWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
    /**
     * @param RemoveWebhookRequest $request
     *
     * @return RemoveWebhookResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function removeWebhook(\TMS\Tamara\Request\Webhook\RemoveWebhookRequest $request) : \TMS\Tamara\Response\Webhook\RemoveWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
    /**
     * @param UpdateWebhookRequest $request
     *
     * @return UpdateWebhookResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function updateWebhook(\TMS\Tamara\Request\Webhook\UpdateWebhookRequest $request) : \TMS\Tamara\Response\Webhook\UpdateWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
    /**
     * @param UpdateReferenceIdRequest $request
     *
     * @return UpdateReferenceIdResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function updateOrderReferenceId(\TMS\Tamara\Request\Order\UpdateReferenceIdRequest $request) : \TMS\Tamara\Response\Order\UpdateReferenceIdResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
    /**
     * @param GetOrderByReferenceIdRequest $request
     *
     * @return GetOrderByReferenceIdResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function getOrderByReferenceId(\TMS\Tamara\Request\Order\GetOrderByReferenceIdRequest $request) : \TMS\Tamara\Response\Order\GetOrderByReferenceIdResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
    /**
     * Get order details by tamara order id
     *
     * @param GetOrderRequest $request
     *
     * @return GetOrderResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function getOrder(\TMS\Tamara\Request\Order\GetOrderRequest $request) : \TMS\Tamara\Response\Order\GetOrderResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * Get merchant details information by merchant id
     * @param GetDetailsInfoRequest $request
     * @return GetDetailsInfoResponse
     * @throws Exception\RequestDispatcherException
     */
    public function getMerchantDetailsInfo(GetDetailsInfoRequest $request): GetDetailsInfoResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * Check if there are any available payment options for customer with the given order value
     * @param CheckPaymentOptionsAvailabilityRequest $request
     * @return CheckPaymentOptionsAvailabilityResponse
     * @throws Exception\RequestDispatcherException
     */
    public function checkPaymentOptionsAvailability(CheckPaymentOptionsAvailabilityRequest $request): CheckPaymentOptionsAvailabilityResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
}
