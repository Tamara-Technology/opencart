<?php
namespace Opencart\Catalog\Controller\Extension\Tamarapay\Event;
/**
 * Class Tamarapay
 *
 * @package Opencart\Catalog\Controller\Event
 */
class Tamarapay extends \Opencart\System\Engine\Controller
{
    // catalog/model/account/custom_field/getCustomFields/after
    public function checkTelephoneForTamaraPayment(&$route, &$args, &$output) {
        //check if Tamara payment is enabled (get it from config)

        $this->load->model('extension/tamarapay/payment/tamarapay');

        if (!$this->model_extension_tamarapay_payment_tamarapay->isTamaraEnabled()) {
            return;
        }

        foreach ($output as $customField) {
            if ($customField['name'] == "Telephone" && $customField['location'] == 'address' && $customField['type'] == 'text') {
                return;
            }
        }
        if (!empty($args)) {
            $output = array_merge($output, $this->getTamaraCustomFields($args[0]));
        } else {
            $output = array_merge($output, $this->getTamaraCustomFields($this->customer->getGroupId()));
        }
    }

    private function getTamaraCustomFields(int $customer_group_id = 0): array {
        $custom_field_data = [];

        if (!$customer_group_id) {
            $custom_field_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field` cf LEFT JOIN `" . DB_PREFIX . "custom_field_description` cfd ON (cf.`custom_field_id` = cfd.`custom_field_id`) WHERE cfd.`name` = 'Telephone' AND cf.`location` = 'address' AND cf.`type` = 'text' AND cfd.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY cf.`sort_order` ASC");
        } else {
            $custom_field_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_customer_group` cfcg LEFT JOIN `" . DB_PREFIX . "custom_field` cf ON (cfcg.`custom_field_id` = cf.`custom_field_id`) LEFT JOIN `" . DB_PREFIX . "custom_field_description` cfd ON (cf.`custom_field_id` = cfd.`custom_field_id`) WHERE cfd.`name` = 'Telephone' AND cf.`location` = 'address' AND cf.`type` = 'text' AND cfd.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND cfcg.`customer_group_id` = '" . (int)$customer_group_id . "' ORDER BY cf.`sort_order` ASC");
        }

        foreach ($custom_field_query->rows as $custom_field) {
            $custom_field_value_data = [];

            if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio' || $custom_field['type'] == 'checkbox') {
                $custom_field_value_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_value` cfv LEFT JOIN `" . DB_PREFIX . "custom_field_value_description` cfvd ON (cfv.`custom_field_value_id` = cfvd.`custom_field_value_id`) WHERE cfv.`custom_field_id` = '" . (int)$custom_field['custom_field_id'] . "' AND cfvd.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY cfv.`sort_order` ASC");

                foreach ($custom_field_value_query->rows as $custom_field_value) {
                    $custom_field_value_data[] = [
                        'custom_field_value_id' => $custom_field_value['custom_field_value_id'],
                        'name'                  => $custom_field_value['name']
                    ];
                }
            }

            $custom_field_data[] = [
                'custom_field_id'    => $custom_field['custom_field_id'],
                'custom_field_value' => $custom_field_value_data,
                'name'               => $custom_field['name'],
                'type'               => $custom_field['type'],
                'value'              => $custom_field['value'],
                'validation'         => $custom_field['validation'],
                'location'           => $custom_field['location'],
                'required'           => empty($custom_field['required']) || $custom_field['required'] == 0 ? false : true,
                'sort_order'         => $custom_field['sort_order']
            ];
        }

        return $custom_field_data;
    }


    public function addPromoWidgetForProduct(&$route, &$data, &$output)
    {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        if (!$this->model_extension_tamarapay_payment_tamarapay->isTamaraEnabled()) {
            return;
        }
        if ($this->model_extension_tamarapay_payment_tamarapay->getDisableTamara()) {
            return;
        }
        preg_match('/<div .*id="product"/', $output, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0][1])) {
            return;
        }
        if (!$this->config->get("payment_tamarapay_status")) {
            return;
        }
        if (empty($data['product_id'])) {
            return;
        }
        $productId = $data['product_id'];
        $excludeProductIds = explode(",", strval($this->config->get('payment_tamarapay_pdp_wg_exclude_product_ids')));
        if (in_array($productId, $excludeProductIds)) {
            return $output;
        }
        $excludeCategoryIds = explode(",", strval($this->config->get('payment_tamarapay_pdp_wg_exclude_category_ids')));
        $this->load->model('catalog/product');
        $rows = $this->model_catalog_product->getCategories($productId);
        $productCategories = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $productCategories[] = $row['category_id'];
            }
            if (!empty(array_intersect($productCategories, $excludeCategoryIds))) {
                return $output;
            }
        }
        $productInfo = $this->model_catalog_product->getProduct($productId);
        if ($productInfo) {
            $finalPrice = $this->model_extension_tamarapay_payment_tamarapay->getValueInCurrency($this->tax->calculate($productInfo['price'], $productInfo['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            if ((float) $productInfo['special']) {
                $finalPrice = $this->model_extension_tamarapay_payment_tamarapay->getValueInCurrency($this->tax->calculate($productInfo['special'], $productInfo['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            }
            $widgetHtml = $this->getWidgetHtml($finalPrice);
            if (!empty($widgetHtml)) {
                $output = substr_replace($output, $widgetHtml, $matches[0][1], 0);
            }
        }
    }

    public function addPromoWidgetForCartPage(&$route, &$data, &$output)
    {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        if (!$this->model_extension_tamarapay_payment_tamarapay->isTamaraEnabled()) {
            return;
        }
        if ($this->model_extension_tamarapay_payment_tamarapay->getDisableTamara()) {
            return;
        }

        if (!preg_match('/<\/table>/i', $output, $tableMatches, PREG_OFFSET_CAPTURE)) {
            return;
        }
        if (!$this->config->get("payment_tamarapay_status")) {
            return;
        }
        if (!$this->model_extension_tamarapay_payment_tamarapay->validateCartItems()) {
            return;
        }
        $this->load->model('extension/tamarapay/payment/helper/tamara_opencart');
        $cartValue = $this->model_extension_tamarapay_payment_helper_tamara_opencart->getTotalAmountInCurrency();
        if (empty($cartValue)) {
            return;
        }
        $widgetHtml = $this->getWidgetHtml($cartValue);
        if (!empty($widgetHtml)) {
            $widgetHtml .= '<style>#tamara_promo_widget {text-align: left;} .tamara-promo {margin: 6px auto 18px auto !important;} </style>';
            $insertAt = $tableMatches[0][1] + strlen($tableMatches[0][0]);
            $output = substr_replace($output, $widgetHtml, $insertAt, 0);
        }
    }

    public function addCssToPaymentMethod(&$route, &$data, &$output)
    {
        $this->load->model('extension/tamarapay/payment/tamarapay');

        if (!$this->model_extension_tamarapay_payment_tamarapay->isTamaraEnabled()) {
            return;
        }
        if ($this->model_extension_tamarapay_payment_tamarapay->getDisableTamara()) {
            return;
        }

        $logoUrl = 'https://cdn.tamara.co/assets/svg/tamara-logo-badge-en.svg';
        $optionReplacements = [
            [
                "                                html += '<div class=\"form-check\">';\n\n                                var code = i + '-' + j.replaceAll('_', '-');\n\n                                html += '<input type=\"radio\" name=\"payment_method\" value=\"' + json['payment_methods'][i]['option'][j]['code'] + '\" id=\"input-payment-method-' + code + '\"';\n\n                                var method = \$('#input-payment-code').val();\n\n                                if ((json['payment_methods'][i]['option'][j]['code'] == method) || (!method && first)) {\n                                    html += ' checked';\n\n                                    first = false;\n                                }",
                "                                var optionDisabled = !!(json['payment_methods'][i]['unavailable'] || json['payment_methods'][i]['option'][j]['disabled']);\n                                html += '<div class=\"form-check' + (optionDisabled ? ' tamara-pre-checkout-unavailable' : '') + '\">';\n\n                                var code = i + '-' + j.replaceAll('_', '-');\n\n                                html += '<input type=\"radio\" name=\"payment_method\" value=\"' + json['payment_methods'][i]['option'][j]['code'] + '\" id=\"input-payment-method-' + code + '\"';\n\n                                var method = \$('#input-payment-code').val();\n\n                                if (optionDisabled) {\n                                    html += ' disabled=\"disabled\"';\n                                } else if ((json['payment_methods'][i]['option'][j]['code'] == method) || (!method && first)) {\n                                    html += ' checked';\n\n                                    first = false;\n                                }",
            ],
            [
                "                                html += '<div class=\"form-check\">';\n\n                                var code = i + '-' + j.replaceAll('_', '-');\n\n                                html += '<input type=\"radio\" name=\"payment_method\" value=\"' + json['payment_methods'][i]['option'][j]['code'] + '\" id=\"input-payment-method-' + code + '\"';\n\n                                if (json['payment_methods'][i]['option'][j]['code'] == \$('#input-payment-code').val()) {\n                                    html += ' checked';\n                                }",
                "                                var optionDisabled = !!(json['payment_methods'][i]['unavailable'] || json['payment_methods'][i]['option'][j]['disabled']);\n                                html += '<div class=\"form-check' + (optionDisabled ? ' tamara-pre-checkout-unavailable' : '') + '\">';\n\n                                var code = i + '-' + j.replaceAll('_', '-');\n\n                                html += '<input type=\"radio\" name=\"payment_method\" value=\"' + json['payment_methods'][i]['option'][j]['code'] + '\" id=\"input-payment-method-' + code + '\"';\n\n                                if (optionDisabled) {\n                                    html += ' disabled=\"disabled\"';\n                                } else if (json['payment_methods'][i]['option'][j]['code'] == \$('#input-payment-code').val()) {\n                                    html += ' checked';\n                                }",
            ],
        ];

        foreach ($optionReplacements as [$optionSearch, $optionReplace]) {
            if (strpos($output, $optionSearch) !== false) {
                $output = str_replace($optionSearch, $optionReplace, $output);
                break;
            }
        }

        $labelSearch = "html += '  <label for=\"input-payment-method-' + code + '\">' + json['payment_methods'][i]['option'][j]['name'] + '</label>';";
        $labelReplace = "var optionName = json['payment_methods'][i]['option'][j]['name'];"
            . " if (json['payment_methods'][i]['option'][j]['code'] && String(json['payment_methods'][i]['option'][j]['code']).indexOf('tamarapay') === 0) {"
            . " optionName = '<img class=\"payment-icon tamara-payment-option-logo\" src=\"" . $logoUrl . "\" alt=\"Tamara\">' + optionName;"
            . " }"
            . " html += '  <label for=\"input-payment-method-' + code + '\">' + optionName + '</label>';";

        if (strpos($output, $labelSearch) !== false) {
            $output = str_replace($labelSearch, $labelReplace, $output);
        }

        $selectedSearch = "$('#input-payment-method').val($('input[name=\'payment_method\']:checked').parent().find('label').text());";
        $selectedReplace = "$('#input-payment-method').val($('input[name=\'payment_method\']:checked').parent().find('label').text());"
            . " if (typeof window.updateTamaraPaymentMethodLogo === 'function') { window.updateTamaraPaymentMethodLogo(); }";
        if (strpos($output, $selectedSearch) !== false) {
            $output = str_replace($selectedSearch, $selectedReplace, $output);
        }

        $clearedSearch = "$('#input-payment-method').val('');";
        $clearedReplace = "$('#input-payment-method').val(''); if (typeof window.updateTamaraPaymentMethodLogo === 'function') { window.updateTamaraPaymentMethodLogo(); }";
        if (strpos($output, $clearedSearch) !== false) {
            $output = str_replace($clearedSearch, $clearedReplace, $output);
        }

        if (strpos($output, 'id="tamara-pre-checkout-script"') !== false) {
            return;
        }

        $addCss = '

            <style>
            label[for=input-payment-method-tamarapay-tamarapay]
            {
              display: inline;
            }
            .tamara-payment-option-logo { height: 28px; width: auto; vertical-align: middle; margin-right: 6px; }
            #input-payment-method.tamara-selected-payment {
              background-image: url("' . $logoUrl . '");
              background-repeat: no-repeat;
              background-position: 10px center;
              background-size: auto 24px;
              padding-left: 100px;
            }
            [dir=rtl] #input-payment-method.tamara-selected-payment {
              background-position: right 10px center;
              padding-left: 12px;
              padding-right: 100px;
            }
            .tamara-pre-checkout-unavailable { opacity: .55; }
            .tamara-pre-checkout-unavailable label { cursor: not-allowed; pointer-events: none; }
            .tamara-pre-checkout-unavailable .tamara-payment-option-logo { opacity: .75; }
            </style>
            <script type="text/javascript" id="tamara-pre-checkout-script">
            window.updateTamaraPaymentMethodLogo = function() {
                var code = $("#input-payment-code").val() || "";
                var name = $("#input-payment-method").val() || "";
                $("#input-payment-method").toggleClass("tamara-selected-payment", code.indexOf("tamarapay") === 0 && name !== "");
            };
            window.decorateTamaraPaymentOptions = function(paymentMethods) {
                var method = paymentMethods && paymentMethods.tamarapay ? paymentMethods.tamarapay : null;
                if (!method) {
                    return false;
                }
                var option = method.option && method.option.tamarapay ? method.option.tamarapay : {};
                var input = document.querySelector(\'input[name="payment_method"][value="tamarapay.tamarapay"]\');
                if (!input) {
                    return false;
                }
                if (method.unavailable || option.disabled) {
                    input.disabled = true;
                    input.checked = false;
                    var wrap = input.closest(".form-check");
                    if (wrap) {
                        wrap.classList.add("tamara-pre-checkout-unavailable");
                    }
                }
                var label = document.querySelector(\'label[for="\' + input.id + \'"]\') || (input.parentNode ? input.parentNode.querySelector("label") : null);
                if (label && !label.querySelector(".tamara-payment-option-logo")) {
                    var img = document.createElement("img");
                    img.className = "payment-icon tamara-payment-option-logo";
                    img.src = "' . $logoUrl . '";
                    img.alt = "Tamara";
                    label.insertBefore(img, label.firstChild);
                }
                return true;
            };
            $(document).ajaxSuccess(function(event, xhr, settings) {
                if (!settings.url || settings.url.indexOf("payment_method.getMethods") === -1) {
                    return;
                }
                var json = xhr.responseJSON;
                if (!json && xhr.responseText) {
                    try { json = JSON.parse(xhr.responseText); } catch (e) { json = null; }
                }
                if (!json || !json.payment_methods) {
                    return;
                }
                var attempts = 0;
                var timer = setInterval(function() {
                    attempts++;
                    if (window.decorateTamaraPaymentOptions(json.payment_methods) || attempts > 20) {
                        clearInterval(timer);
                    }
                }, 50);
            });
            $(function() { window.updateTamaraPaymentMethodLogo(); });
            </script>
            ';

        if (preg_match('/<\/footer>/i', $output, $matches, PREG_OFFSET_CAPTURE)) {
            $output = substr_replace($output, $addCss, $matches[0][1], 0);
        } else {
            $output .= $addCss;
        }

        return $output;
    }

    

    public function getWidgetHtml($price)
    {
        $result = '';
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $bestMethodForCustomer = $this->model_extension_tamarapay_payment_tamarapay->getBestMethodForCustomer($price, $this->model_extension_tamarapay_payment_tamarapay->getCurrencyCodeFromSession());
        if (empty($bestMethodForCustomer)) {
            return $result;
        }

        //list of the payment types which does not have the widget
        if (in_array($bestMethodForCustomer['name'], ['pay_now'])) {
            return $result;
        }
        $countryCode = $this->model_extension_tamarapay_payment_tamarapay->getSupportedCountriesByCurrency($bestMethodForCustomer['currency'])[0];
        $languageCode = $this->model_extension_tamarapay_payment_tamarapay->getLanguageCodeFromSession();
        $publicKey = $this->model_extension_tamarapay_payment_tamarapay->getMerchantPublicKey();
        $isUseWidgetV1 = empty($publicKey);
        if ($isUseWidgetV1) {
            $result = '<div id="tamara-product-widget" class="tamara-product-widget" data-lang="' . $languageCode . '" data-price="' . $price . '" data-currency="' . $bestMethodForCustomer['currency'] . '" data-country-code="' . $countryCode . '" data-installment-available-amount="' . $bestMethodForCustomer['min_limit'] . '"';
            if ($this->model_extension_tamarapay_payment_tamarapay->isInstallmentsPayment($bestMethodForCustomer['name'])) {
                $result .= (' data-payment-type="installment" data-number-of-installments="' . $bestMethodForCustomer['number_of_instalments'] . '" data-installment-minimum-amount="' . $bestMethodForCustomer['min_limit'] . '" data-installment-maximum-amount="' . $bestMethodForCustomer['max_limit'] . '" data-disable-paylater="true"></div>');
            } else {
                if ($bestMethodForCustomer['name'] == "pay_by_later") {
                    $result .= (' data-payment-type="paylater" data-disable-paylater="false" data-disable-product-limit="true" data-disable-installment="true" data-pay-later-max-amount="' . $bestMethodForCustomer['max_limit'] . '"></div>');
                } else {
                    if ($bestMethodForCustomer['name'] == "pay_next_month") {
                        $result .= (' data-payment-type="pay-next-month" data-disable-paylater="true" data-disable-installment="false"></div>');
                    }
                }
            }

            $productWidgetUrl = "https://cdn.tamara.co/widget/product-widget.min.js";
            if ($this->model_extension_tamarapay_payment_tamarapay->isSandboxMode()) {
                $productWidgetUrl = "https://cdn-sandbox.tamara.co/widget/product-widget.min.js";
            }
            $result .= '
            <script type="text/javascript">
                window.tamara = [];
                window.langCode = "' . $languageCode . '";
                window.tamara.currencyCode = "' . $bestMethodForCustomer["currency"] . '";
                window.checkTamaraProductWidgetCount = 0;
                window.tamara.widgetPublicKey = "' . $publicKey . '";
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
            <script charset="utf-8" defer src="' . $productWidgetUrl . '?t=' . time() . '"></script>
            ';
        } else {
            if ($this->model_extension_tamarapay_payment_tamarapay->isSandboxMode()) {
                $widgetUrl = "https://cdn-sandbox.tamara.co/widget-v2/tamara-widget.js";
            } else {
                $widgetUrl = "https://cdn.tamara.co/widget-v2/tamara-widget.js";
            }
            $result = '<tamara-widget id="tamara_promo_widget" type="tamara-summary" amount="' . $price . '" inline-type="2"></tamara-widget>';
            $result .= ('<script>
                    var tamaraWidgetConfig = {
                        lang: "' . $languageCode . '",
                        country: "' . $countryCode . '",
                        publicKey: "' . $publicKey . '"
                    }
                    </script>
                    <script charset="utf-8" defer src="' . $widgetUrl . '?t=' . time() . '"></script>'
            );
        }
        return ('<div class="tamara-promo" style="margin-bottom: 10px;">' . $result . '</div>');
    }

    public function log($data, $class_step = 6, $function_step = 6)
    {
        $this->load->model('extension/tamarapay/payment/tamarapay');
        $this->model_extension_tamarapay_payment_tamarapay->log($data, $class_step, $function_step);
    }


    public function handleOrderStatusChange($route, $args, $output)
    {
        $this->log(["Tamara - function handleOrderStatusChange"]);
        if ($this->config->get('payment_tamarapay_trigger_actions_enabled')) {
            try {
                $this->load->model('extension/tamarapay/payment/tamarapay');
                $orderId = $args[0];
                if (!$this->model_extension_tamarapay_payment_tamarapay->isPayWithTamara($orderId)) {
                    return;
                }
                $statusId = $args[1];
                $this->log([
                    "event" => "handleOrderStatusChange",
                    "order id" => $orderId,
                    "status id" => $statusId
                ]);
                $tamaraOrder = $this->model_extension_tamarapay_payment_tamarapay->getTamaraOrder($orderId);
                if ($statusId == $this->config->get('payment_tamarapay_capture_order_status_id')) {
                    $this->log(["Tamara - handleOrderStatusChange - will go to capture, order id: " . $orderId . ", tamara order id: " . $tamaraOrder['tamara_order_id']]);
                    $this->model_extension_tamarapay_payment_tamarapay->captureOrder($tamaraOrder['tamara_order_id']);
                    return;
                }
                if ($statusId == $this->config->get('payment_tamarapay_cancel_order_status_id')) {
                    $this->log(["Tamara - handleOrderStatusChange - will go to cancel, order id: " . $orderId . ", tamara order id: " . $tamaraOrder['tamara_order_id']]);
                    $this->model_extension_tamarapay_payment_tamarapay->cancelOrder($tamaraOrder['tamara_order_id']);
                    return;
                }
                if ($statusId == $this->model_extension_tamarapay_payment_tamarapay->getRefundOrderStatusId()) {
                    $this->log(["Tamara - handleOrderStatusChange - will go to refund, order id: " . $orderId . ", tamara order id: " . $tamaraOrder['tamara_order_id']]);
                    $this->model_extension_tamarapay_payment_tamarapay->refundOrder($tamaraOrder['tamara_order_id']);
                    return;
                }
                $this->log(["Tamara - handleOrderStatusChange - not processing, order id: " . $orderId . ", tamara order id: " . $tamaraOrder['tamara_order_id']]);
            } catch (\Exception $exception) {
                $this->log(["handleOrderStatusChange - Exception when capture, cancel or refund order"]);
                $this->log($exception->getMessage());
            }
        } else {
            $this->log("Tamara trigger is disabled");
        }

    }


    

}