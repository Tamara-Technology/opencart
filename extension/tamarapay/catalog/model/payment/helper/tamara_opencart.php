<?php

namespace Opencart\Catalog\Model\Extension\Tamarapay\Payment\Helper;
/**
 * Class Tamarapay
 *
 * @package
 */
class TamaraOpencart extends \Opencart\System\Engine\Model {

    public function getTotalsData() {
        $this->load->language('common/cart');

        // Totals
        $this->load->model('setting/extension');

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0.0;



        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            // $results = $this->model_setting_extension->getExtensions('total');
		    $results = $this->model_setting_extension->getExtensionsByType('total');


            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {


                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/'.$result['extension'].'/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
				    ($this->{'model_extension_' . $result['extension'] . '_total_' . $result['code']}->getTotal)($totals, $taxes, $total);
                }
            }
        }

        return $totals;
    }

    public function getTotalAmountInCurrency() {
        $totalsData = $this->getTotalsData();
        $this->load->model('extension/tamarapay/payment/tamarapay');
        foreach ($totalsData as $total) {
            if ($total['code'] == 'total') {
                return $this->model_extension_tamarapay_payment_tamarapay->getValueInCurrency($total['value'], $this->session->data['currency']);
            }
        }
        return null;
    }
}