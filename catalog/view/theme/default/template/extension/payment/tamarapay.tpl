<style type="text/css">
    img.payment-icon {
        max-height: 25px;
    }
    #tamara-installment-plan, .tamara_promo, .tamara-v2, .tamara-v1 {
        margin-bottom: 10px;
    }
</style>
<?php if ($error_get_payment): ?>
    <div id="error-area" class="alert alert-danger"><i class="fa fa-ban"></i> <?php echo $error_get_payment ?></div>
<?php else: ?>
    <div style="display: none;" class="payment-warning alert alert-danger">
        <i class="fa fa-exclamation-circle"></i>
        <span class="message"></span>
    </div>
    <div style="display: none" id="error-area" class="alert alert-danger"><i class="fa fa-ban"></i></div>
    <?php if ($total_method_available > 0): ?>
        <div class="container-customize">
            <form class="form-horizontal" id="tamara-payment-form">
                <?php if ($total_method_available == 1): ?>
                    <section class="payment-types">
                        <div class="payment-type-content">
                            <div class="col-sm-12">
                                <div class="form-check">
                                    <input type="radio" name="payment_type" class="form-check-input" id="<?php echo $first_method['name']; ?>" value="<?php echo $first_method['name']; ?>" checked="true" style="display: none" />
                                    <div class="tamara_promo" style="text-align: center;">
                                        <tamara-widget amount="<?php echo $order_data['total_in_currency'] ?>" inline-type="6" config='{"badgePosition":"","showExtraContent":"full","hidePayInX":false}'></tamara-widget>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="payment-types">
                        <div class="payment-type-title">
                            <h3 class="text-center"><?php echo $text_choose_payment; ?></h3>
                            <?php if ($exists_pay_now == false): ?>
                            <div class="col-sm-12">
                                <div class="col-sm-4"></div>
                                <?php if ($total_method_available): ?>
                                <div class="col-sm-4 text-center">
                                    <tamara-widget amount="<?php echo $order_data['total_in_currency'] ?>" inline-type="1"></tamara-widget>
                                </div>
                                <?php endif; ?>
                                <div class="col-sm-4"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="payment-type-content">
                            <?php foreach ($methods as $key => $method): ?>
                                <div class="col-sm-12">
                                    <div class="form-check">
                                        <?php if ($method['checked']): ?>
                                            <input type="radio" name="payment_type" class="form-check-input" id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>" checked>
                                        <?php else: ?>
                                            <input type="radio" name="payment_type" class="form-check-input" id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>">
                                        <?php endif; ?>
                                        <label for="<?php echo $method['name']; ?>" class="form-check-label"><img class="payment-icon" src="https://cdn.tamara.co/assets/svg/tamara-logo-badge-<?php echo $language_code; ?>.svg" alt="Tamara"><b>&nbsp;<?php echo $method['title']; ?></b></label>
                                        <br/>
                                        <?php if ($method['name'] == 'pay_by_later'): ?>
                                        <label for="<?php echo $method['name']; ?>">
                                            <a href="javascript:void(0)" class="tamara-product-widget tamara-v1" data-lang="<?php echo $language_code; ?>" data-currency="<?php echo $method['currency']; ?>" data-country-code="<?php echo $country_code; ?>" data-price="<?php echo $order_data['total_in_currency']; ?>" data-payment-type="paylater" data-pay-later-max-amount="<?php echo $method['max_limit']; ?>" data-disable-paylater="false" data-disable-product-limit="true" data-disable-installment="true" data-inject-template="false" data-installment-available-amount="<?php echo $method['min_limit']; ?>"><?php echo $text_more_details; ?> </a>
                                        </label>
                                        <?php else: ?>
                                            <?php if ($method['name'] == 'pay_next_month'): ?>
                                            <div class="tamara-product-widget tamara-v1" data-lang="<?php echo $language_code; ?>" data-currency="<?php echo $method['currency']; ?>" data-country-code="<?php echo $country_code; ?>" data-price="<?php echo $order_data['total_in_currency']; ?>" data-payment-type="pay-next-month" data-disable-paylater="true" data-disable-installment="false" data-installment-available-amount="<?php echo $method['min_limit']; ?>"></div>
                                            <?php else: ?>
                                                <?php if ($method['name'] == 'pay_now'): ?>
                                                    <div class="tamara_promo tamara-v2">
                                                        <tamara-widget type="tamara-card-snippet" lang="<?php echo $language_code; ?>" country="<?php echo $country_code; ?>"></tamara-widget>
                                                    </div>
                                                <?php else: ?>
                                                    <?php if ($total_installments_types > 1): ?>
                                                    <div id="tamara-installment-plan" class="tamara-installment-plan-widget tamara-v1" data-lang="<?php echo $language_code; ?>" data-country-code="<?php echo $country_code; ?>" data-price="<?php echo $order_data['total_in_currency']; ?>" data-currency="<?php echo $method['currency']; ?>" data-installment-minimum-amount="<?php echo $method['min_limit']; ?>" data-installment-maximum-amount="<?php echo $method['max_limit']; ?>" data-number-of-installments="<?php echo $method['number_of_instalments']; ?>" data-installment-available-amount="<?php echo $method['min_limit']; ?>"></div>
                                                    <?php else: ?>
                                                    <div class="tamara_promo tamara-v2">
                                                        <tamara-widget amount="<?php echo $order_data['total_in_currency'] ?>" inline-type="6" config='{"badgePosition":"","showExtraContent":"full","hidePayInX":false}'></tamara-widget>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                <div class="form-group form-submit col-sm-12 text-center">
                    <input id="button-confirm" type="button" value="<?php echo $button_confirm; ?>" class="btn btn-primary submit-form tamara-button-confirm"/>
                </div>
            </form>
        </div>
        <?php if ($use_widget_version == 'v1' || $use_widget_version == 'mixed'): ?>
            <?php if ($exists_pay_now == false): ?>
                <script charset="utf-8" src="<?php echo $tamara_widget_url; ?>?t=<?php echo $current_time; ?>"></script>
                <script type="text/javascript">
                    window.checkTamaraWidgetCount = 0;
                    var existTamaraWidget = setInterval(function () {
                        if (window.TamaraWidget) {
                            window.TamaraWidget.init({
                                lang: '<?php echo $language_code; ?>',
                                currency: '<?php echo $order_data["currency_code"]; ?>',
                                publicKey: '<?php echo $merchant_public_key; ?>'
                            });
                            window.TamaraWidget.render();
                            clearInterval(existTamaraWidget);
                        }
                        window.checkTamaraWidgetCount += 1;
                        if (window.checkTamaraWidgetCount > 15) {
                            clearInterval(existTamaraWidget);
                        }
                    }, 300);
                </script>
            <?php endif; ?>
            <?php if ($exists_pay_later_or_pay_next_month): ?>
                <script charset="utf-8" src="<?php echo $tamara_product_widget_url; ?>?t=<?php echo $current_time; ?>"></script>
                <script type="text/javascript">
                    var checkTamaraProductWidgetCount = 0;
                    var existTamaraProductWidget = setInterval(function () {
                        if (window.TamaraProductWidget) {
                            window.TamaraProductWidget.init({
                                lang: '<?php echo $language_code; ?>',
                                currency: '<?php echo $order_data["currency_code"]; ?>',
                                publicKey: '<?php echo $merchant_public_key; ?>'
                            });
                            window.TamaraProductWidget.render();
                            clearInterval(existTamaraProductWidget);
                        }
                        checkTamaraProductWidgetCount += 1;
                        if (checkTamaraProductWidgetCount > 33) {
                            clearInterval(existTamaraProductWidget);
                        }
                    }, 300);
                </script>
            <?php endif; ?>
            <?php if ($exists_pay_by_installments || $exists_pay_in_x): ?>
                <script charset="utf-8"
                        src="<?php echo $tamara_installments_plan_widget_url; ?>?t=<?php echo $current_time; ?>"></script>
                    <script type="text/javascript">
                        var countExistTamaraInstallmentsPlan = 0;
                        var existTamaraInstallmentsPlan = setInterval(function () {
                            if ($('.tamara-installment-plan-widget').length) {
                                if (window.TamaraInstallmentPlan) {
                                    window.TamaraInstallmentPlan.init({
                                        lang: '<?php echo $language_code; ?>',
                                        currency: '<?php echo $order_data["currency_code"]; ?>',
                                        publicKey: '<?php echo $merchant_public_key; ?>'
                                    });
                                    window.TamaraInstallmentPlan.render();
                                    clearInterval(existTamaraInstallmentsPlan);
                                }
                            }
                            if (++countExistTamaraInstallmentsPlan > 33) {
                                clearInterval(existTamaraInstallmentsPlan);
                            }
                        }, 300);
                    </script>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($use_widget_version == 'v2' || $use_widget_version == 'mixed'): ?>
            <script>
                var tamaraWidgetConfig = {
                    lang: '<?php echo $language_code ?>',
                    country: '<?php echo $country_code ?>',
                    publicKey: '<?php echo $merchant_public_key ?>',
                    css: '.tamara-summary-widget__container {display:inline-block;}'
                }
            </script>
            <script charset="utf-8" defer src="<?php echo $information_widget_v2_url?>?t=<?php echo $current_time?>"></script>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-danger text-center font-weight-bold" style="font-weight: bold;">
            <?php echo $error_no_method_available ?>
        </p>
    <?php endif; ?>
    <script type="text/javascript">
        $('#button-confirm').on('click', function () {
            $.ajax({
                url: 'index.php?route=extension/payment/tamarapay/send',
                type: 'post',
                    data: {
                        'payment_type' : $('#tamara-payment-form input:checked').val(),
                        'is_none_validated_method' : <?php echo $is_none_validated_method ?>
                },
                dataType: 'json',
                beforeSend: function () {
                    $('.payment-warning').hide();

                    $('.payment-warning .message').text();

                    $('#tamara-payment-form').find('*').removeClass('has-error');

                    $('#button-confirm').button('loading').attr('disabled', true);
                },
                complete: function () {
                    $('#button-confirm').button('reset');
                },
                success: function (json) {
                    if (json['redirectUrl']) {
                        window.location = json['redirectUrl'];
                    }
                    if (json['error']) {
                        $('#error-area').css('display', 'block');
                        $('#error-area').text(json['error']);
                    }
                    $('#button-confirm').button('reset');
                }
            });
        });
    </script>
<?php endif; ?>
