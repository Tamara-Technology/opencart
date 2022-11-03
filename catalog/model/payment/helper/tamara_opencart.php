<?php

class ModelPaymentHelperTamaraOpencart extends Model {

    public function getTotalsData() {
        $this->load->language('common/cart');

        // Totals
        $this->load->model('extension/extension');

        $total_data = array();
        $total = 0;
        $taxes = $this->cart->getTaxes();

        // Display prices
        if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            $results = $this->model_extension_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get($result['code'] . '_status')) {
                    $this->load->model('total/' . $result['code']);

                    $this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
                }
            }

            $sort_order = array();

            foreach ($total_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $total_data);
        }

        return $total_data;
    }

    public function getTotalAmountInCurrency() {
        $totalsData = $this->getTotalsData();
        $this->load->model('payment/tamarapay');
        foreach ($totalsData as $total) {
            if ($total['code'] == 'total') {
                return $this->model_payment_tamarapay->getValueInCurrency($total['value'], $this->session->data['currency']);
            }
        }
        return null;
    }
}