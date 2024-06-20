<?php

namespace Opencart\Catalog\Model\Extension\Tamarapay\Payment;

class TamarapayConsoleScan extends \Opencart\System\Engine\Model {

    const ORDER_STATUS_APPROVED = 'approved';

    private $totalOrderProcessed = 0;

    /**
     * @param string $startTime
     * @param string $endTime
     * @throws \Exception
     */
    public function scan($startTime = '-10 days', $endTime = 'now')
    {
        $dateTimePattern = '/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]/';
        if (!preg_match($dateTimePattern, $startTime)) {
            $timeDiff = $this->db->query("SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP) as time_diff")->rows[0]['time_diff'];
            $parts = explode(":", $timeDiff);
            if (intval($parts[0]) < 0) {
                $additionTime = " {$parts[0]} hours -{$parts[1]} minutes -{$parts[2]} seconds";
            } else {
                $additionTime = " +{$parts[0]} hours +{$parts[1]} minutes +{$parts[2]} seconds";
            }
            $startTime = $startTime . $additionTime;
        }
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->model_extension_tamarapay_payment_tamarapay->log(["Start scan orders"]);

        try {
            $startTime = date('Y-m-d H:i:s', strtotime($startTime));
            if ($endTime == 'now') {
                $endTime = $this->db->query("SELECT NOW() as current_datetime")->row['current_datetime'];
            } else {
                $endTime = date('Y-m-d H:i:s', strtotime($endTime));
            }
        } catch (\Exception $exception) {
            $this->model_extension_tamarapay_payment_tamarapay->log("Time format is not support");
            throw $exception;
        }

        $sql = "select oto.tamara_id , oto.tamara_order_id, oto.captured_from_console, oto.canceled_from_console, oo.* FROM `".DB_PREFIX."tamara_orders` oto INNER JOIN oc_order oo 
                ON oto.order_id  = oo.order_id 
                WHERE oto.is_active = 1 AND oto.created_at >= '{$startTime}' AND oto.created_at <= '{$endTime}'";

        $tamaraOrders = $this->db->query($sql)->rows;
        if (!empty($tamaraOrders)) {

            //scan authorise
            $authoriseStatusId = $this->config->get('payment_tamarapay_order_status_success_id');
            $authoriseOrders = $this->getOrdersFiltered($tamaraOrders, $authoriseStatusId, 'is_authorised');
            $this->doAction(array_keys($authoriseOrders), 'authorise');
            $this->updateTamaraOrdersAfterScan($authoriseOrders, 'is_authorised');

            //scan capture
            $captureStatusId = $this->config->get('payment_tamarapay_capture_order_status_id');
            $captureOrders = $this->getOrdersFiltered($tamaraOrders, $captureStatusId, 'captured_from_console');
            $this->doAction(array_keys($captureOrders), 'capture');
            $this->updateTamaraOrdersAfterScan($captureOrders, 'captured_from_console');

            //scan cancel
            $cancelStatusId = $this->config->get('payment_tamarapay_cancel_order_status_id');
            $cancelOrders = $this->getOrdersFiltered($tamaraOrders, $cancelStatusId,'canceled_from_console');
            $this->doAction(array_keys($cancelOrders), 'cancel');
            $this->updateTamaraOrdersAfterScan($cancelOrders, 'canceled_from_console');

            $this->model_extension_tamarapay_payment_tamarapay->log(["Total order processed: " . $this->totalOrderProcessed]);
        }

        $this->model_extension_tamarapay_payment_tamarapay->log(["End scan orders"]);
    }

    private function doAction(array $tamaraOrderIds, $action) {
        if (count($tamaraOrderIds)) {

            foreach ($tamaraOrderIds as $tamaraOrderId) {
                $this->execute($action, $tamaraOrderId);
            }
        }
    }

    /**
     * @param $action
     * @param $tamaraOrderId
     */
    private function execute($action, $tamaraOrderId)
    {
        $method = $action . "Order";
        if (method_exists($this, $method)) {
            try {
                $this->$method($tamaraOrderId);
                $this->totalOrderProcessed++;
            } catch (\Exception $exception) {
                $this->model_extension_tamarapay_payment_tamarapay->log(["Exception: " . $exception->getMessage()]);
            }
        }
    }

    protected function getOrdersFiltered(&$originalOrders, $statusIdToFilter, $consoleFieldToFilter) {
        $result = [];
        $withoutStatus = [];
        foreach ($originalOrders as $order) {
            if ($order['order_status_id'] == $statusIdToFilter) {
                if (empty($order[$consoleFieldToFilter])) {
                    $result[$order['tamara_order_id']] = $order;
                } else {
                    continue;
                }
            } else {
                $withoutStatus[] = $order;
            }
        }
        $originalOrders = $withoutStatus;
        return $result;
    }

    public function captureOrder($tamaraOrderId) {
        $this->model_extension_tamarapay_payment_tamarapay->log(["Console: capture order, order id: " . $tamaraOrderId]);
        $this->model_extension_tamarapay_payment_tamarapay->captureOrder($tamaraOrderId);
    }

    public function cancelOrder($tamaraOrderId) {
        $this->model_extension_tamarapay_payment_tamarapay->log(["Console: capture order, order id: " . $tamaraOrderId]);
        $this->model_extension_tamarapay_payment_tamarapay->cancelOrder($tamaraOrderId);
    }

    public function authoriseOrder($tamaraOrderId) {
        $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrderByTamaraOrderId($tamaraOrderId);
        $remoteOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrderFromRemoteByTamaraOrderId($tamaraOrder['tamara_order_id']);
        if ($remoteOrder === null) {
            return;
        }
        $currentRemoteStatus = $remoteOrder->getStatus();
        if ($currentRemoteStatus == self::ORDER_STATUS_APPROVED) {
            $this->model_extension_tamarapay_payment_tamarapay->log(["Console: authorise order, order id: " . $tamaraOrderId]);
            $this->model_extension_tamarapay_payment_tamarapay->authoriseOrder($tamaraOrderId);
        } else {
            $this->model_extension_tamarapay_payment_tamarapay->log(["Console: Order was not approve by Tamara, order id: " . $tamaraOrderId . ", current status: " . $currentRemoteStatus]);
        }
    }

    private function updateTamaraOrdersAfterScan($orders, $fieldToUpdate) {
        $this->model_extension_tamarapay_payment_tamarapay->updateTamaraOrders($orders, $fieldToUpdate, 1);
    }
}