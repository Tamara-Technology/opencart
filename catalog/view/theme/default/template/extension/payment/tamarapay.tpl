<style type="text/css">
    img.payment-icon {
        max-height: 25px;
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
    <?php if ($single_checkout_enabled): ?>
        <?php if ($total_method_available > 0): ?>
            <?php if ($exists_pay_now == false): ?>
                <div class="container-customize">
                    <form class="form-horizontal" id="payment">
                        <div class="form-group col-sm-12 text-center">
                            <tamara-widget type="tamara-summary" inline-type="1"></tamara-widget>
                        </div>
                        <div class="form-group form-submit col-sm-12 text-center">
                            <input id="button-confirm" type="button" value="<?php echo $button_confirm; ?>"
                                   class="btn btn-primary submit-form"/>
                        </div>
                    </form>
                </div>
                <script charset="utf-8" src="<?php echo $information_widget_v2_url; ?>?t=<?php echo $current_time; ?>"></script>
                <script>
                    var tamaraWidgetConfig = {
                        lang: '<?php echo $language_code; ?>',
                        country: '<?php echo $country_code; ?>',
                        publicKey: '<?php echo $merchant_public_key; ?>',
                        css: '.tamara-summary-widget__container {display:block;}'
                    }
                </script>
            <?php else: ?>
                <div class="container-customize">
                    <form class="form-horizontal" id="payment">
                        <section class="payment-types">
                            <div class="payment-type-title">
                                <h3 class="text-center"><?php echo $text_choose_payment; ?></h3>
                            </div>
                            <div class="payment-type-content">
                                <?php if ($single_checkout_available_for_this_customer): ?>
                                <div class="col-sm-12">
                                    <div class="form-check">
                                        <input type="radio" name="payment_type" class="form-check-input" id="pay_single_checkout"
                                               value="single_checkout" checked>
                                        <label for="pay_single_checkout" class="form-check-label"><img class="payment-icon"
                                                                                                       src="https://cdn.tamara.co/assets/svg/tamara-logo-badge-en.svg"
                                                                                                       alt="Tamara"><b> <?php echo $single_checkout_payment_title; ?></b></label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php foreach ($methods as $key => $method): ?>
                                <?php if ($method['name'] == 'pay_now'): ?>
                                <div class="col-sm-12">
                                    <div class="form-check">
                                        <?php if ($method['is_in_limit']): ?>
                                        <?php if ($single_checkout_available_for_this_customer == false): ?>
                                        <input type="radio" name="payment_type" class="form-check-input"
                                               id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>" checked>
                                        <?php else: ?>
                                        <input type="radio" name="payment_type" class="form-check-input"
                                               id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>">
                                        <?php endif; ?>
                                        <label for="<?php echo $method['name']; ?>" class="form-check-label"><img class="payment-icon"
                                                                                                                  src="https://cdn.tamara.co/assets/svg/tamara-logo-badge-<?php echo $language_code; ?>.svg"
                                                                                                                  alt="Tamara"><b> <?php echo $method['title']; ?></b></label>
                                        <br/>
                                        <?php else: ?>
                                        <input type="radio" name="payment_type" class="form-check-input"
                                               id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>" disabled>
                                        <label for="<?php echo $method['name']; ?>" class="form-check-label"><img class="payment-icon"
                                                                                                                  src="https://cdn.tamara.co/assets/svg/tamara-logo-badge-<?php echo $language_code; ?>.svg"
                                                                                                                  alt="Tamara"><b> <?php echo $method['title']; ?></b></label>
                                        <br/>
                                        <label for="<?php echo $method['name']; ?>"
                                               class="form-check-label"><b><?php echo $text_min_amount; ?></b> <?php echo $method['min_limit']; ?> <?php echo $method['currency']; ?>
                                        </label>
                                        <br/>
                                        <label for="<?php echo $method['name']; ?>"
                                               class="form-check-label"><b><?php echo $text_max_amount; ?></b> <?php echo $method['max_limit']; ?> <?php echo $method['currency']; ?>
                                        </label>
                                        <br/>
                                        <p class="text-warning"><i
                                                    class="fa fa-exclamation-triangle"></i> <?php echo $text_under_over_limit; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <div class="form-group form-submit col-sm-12 text-center">
                            <input id="button-confirm" type="button" value="<?php echo $button_confirm; ?>"
                                   class="btn btn-primary submit-form"/>
                        </div>
                    </form>
                </div>
                <script charset="utf-8" src="<?php echo $information_widget_v2_url; ?>?t=<?php echo $current_time; ?>"></script>
                <script>
                    var tamaraWidgetConfig = {
                        lang: '<?php echo $language_code; ?>',
                        country: '<?php echo $country_code; ?>',
                        publicKey: '<?php echo $merchant_public_key; ?>',
                        css: '.tamara-summary-widget__container {display:inline-block;}'
                    }
                </script>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-danger text-center font-weight-bold" style="font-weight: bold;">
                <?php echo $error_no_method_available ?>
            </p>
        <?php endif; ?>
    <?php else: ?>
        <div class="container-customize">
            <form class="form-horizontal" id="payment">
                <section class="payment-types">
                    <div class="payment-type-title">
                        <h3 class="text-center"><?php echo $text_choose_payment; ?></h3>
                        <?php if ($exists_pay_now == false): ?>
                        <div class="col-sm-12">
                            <div class="col-sm-4"></div>
                            <?php if ($total_method_available): ?>
                                <?php if ($is_use_widget_v1): ?>
                                <div class="col-sm-4 text-center">
                                    <a href="javascript:void(0)" class="tamara-widget" data-lang="<?php echo $language_code; ?>"
                                       data-currency="<?php echo $order_data['currency_code']; ?>"
                                       data-country-code="<?php echo $country_code; ?>"
                                       data-payment-type="<?php echo $methods_name_in_widget; ?>"
                                       data-number-of-installments="<?php echo $number_of_installments; ?>"
                                       data-installment-available-amount="<?php echo $min_limit_all_methods; ?>"></a>
                                </div>
                                <?php else: ?>
                                <div class="col-sm-4 text-center">
                                    <tamara-widget type="tamara-summary" amount="<?php echo $order_data['total_in_currency']; ?>" inline-type="1"></tamara-widget>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="col-sm-4"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="payment-type-content">
                        <?php foreach ($methods as $key => $method): ?>
                        <div class="col-sm-12">
                            <div class="form-check">
                                <?php if ($method['is_in_limit']): ?>
                                <?php if ($method['checked']): ?>
                                <input type="radio" name="payment_type" class="form-check-input"
                                       id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>" checked>
                                <?php else: ?>
                                <input type="radio" name="payment_type" class="form-check-input"
                                       id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>">
                                <?php endif; ?>
                                <label for="<?php echo $method['name']; ?>" class="form-check-label"><img class="payment-icon"
                                                                                                          src="https://cdn.tamara.co/assets/svg/tamara-logo-badge-<?php echo $language_code; ?>.svg"
                                                                                                          alt="Tamara"><b> <?php echo $method['title']; ?></b></label>
                                <br/>
                                <?php else: ?>
                                <input type="radio" name="payment_type" class="form-check-input"
                                       id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>" disabled>
                                <label for="<?php echo $method['name']; ?>" class="form-check-label"><img class="payment-icon"
                                                                                                          src="https://cdn.tamara.co/assets/svg/tamara-logo-badge-<?php echo $language_code; ?>.svg"
                                                                                                          alt="Tamara"><b> <?php echo $method['title']; ?></b></label>
                                <br/>
                                <?php endif; ?>
                                <label for="<?php echo $method['name']; ?>"
                                       class="form-check-label"><b><?php echo $text_min_amount; ?></b> <?php echo $method['min_limit']; ?> <?php echo $method['currency']; ?>
                                </label>
                                <br/>
                                <label for="<?php echo $method['name']; ?>"
                                       class="form-check-label"><b><?php echo $text_max_amount; ?></b> <?php echo $method['max_limit']; ?> <?php echo $method['currency']; ?>
                                </label>
                                <br/>
                                <?php if ($method['is_in_limit']): ?>
                                <?php if ($method['name'] == 'pay_by_later'): ?>
                                    <?php if ($is_use_widget_v1): ?>
                                    <label for="<?php echo $method['name']; ?>">
                                        <a href="javascript:void(0)" class="tamara-product-widget"
                                           data-lang="<?php echo $language_code; ?>"
                                           data-currency="<?php echo $method['currency']; ?>"
                                           data-country-code="<?php echo $country_code; ?>"
                                           data-price="<?php echo $order_data['total_in_currency']; ?>" data-payment-type="paylater"
                                           data-pay-later-max-amount="<?php echo $method['max_limit']; ?>"
                                           data-disable-paylater="false" data-disable-product-limit="true"
                                           data-disable-installment="true" data-inject-template="false"
                                           data-installment-available-amount="<?php echo $method['min_limit']; ?>"><?php echo $text_more_details; ?> </a>
                                    </label>
                                    <?php endif; ?>
                                <?php else: ?>
                                <?php if ($method['name'] == 'pay_next_month'): ?>
                                    <?php if ($is_use_widget_v1): ?>
                                    <label for="<?php echo $method['name']; ?>">
                                        <a href="javascript:void(0)" class="tamara-product-widget"
                                           data-lang="<?php echo $language_code; ?>"
                                           data-currency="<?php echo $method['currency']; ?>"
                                           data-country-code="<?php echo $country_code; ?>"
                                           data-price="<?php echo $order_data['total_in_currency']; ?>"
                                           data-payment-type="pay-next-month" data-disable-paylater="true"
                                           data-disable-installment="false" data-inject-template="false"
                                           data-installment-available-amount="<?php echo $method['min_limit']; ?>"><?php echo $text_more_details; ?></a>
                                    </label>
                                    <?php endif; ?>
                                <?php else: ?>
                                <?php if ($method['name'] != 'pay_now'): ?>
                                    <?php if ($is_use_widget_v1): ?>
                                    <div id="tamara-installment-plan" style="margin-bottom: 10px;"
                                         class="tamara-installment-plan-widget" data-lang="<?php echo $language_code; ?>"
                                         data-country-code="<?php echo $country_code; ?>"
                                         data-price="<?php echo $order_data['total_in_currency']; ?>"
                                         data-currency="<?php echo $method['currency']; ?>"
                                         data-installment-minimum-amount="<?php echo $method['min_limit']; ?>"
                                         data-installment-maximum-amount="<?php echo $method['max_limit']; ?>"
                                         data-number-of-installments="<?php echo $method['number_of_instalments']; ?>"
                                         data-installment-available-amount="<?php echo $method['min_limit']; ?>"
                                    ></div>
                                    <?php else: ?>
                                    <div class="tamara_promo" style="margin-bottom: 10px">
                                        <tamara-widget type="tamara-summary" amount="<?php echo $order_data['total_in_currency'] ?>" inline-type="3"></tamara-widget>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php else: ?>
                                <p class="text-warning"><i
                                            class="fa fa-exclamation-triangle"></i> <?php echo $text_under_over_limit; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php if ($total_method_available > 0): ?>
                <div class="form-group form-submit col-sm-12 text-center">
                    <input id="button-confirm" type="button" value="<?php echo $button_confirm; ?>"
                           class="btn btn-primary submit-form"/>
                </div>
                <?php else: ?>
                <p class="text-danger text-center font-weight-bold" style="font-weight: bold;">
                    <?php echo $error_no_method_available; ?>
                </p>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($is_use_widget_v1): ?>
            <?php if ($exists_pay_now == false): ?>
                <script charset="utf-8" src="<?php echo $tamara_widget_url; ?>?t=<?php echo $current_time; ?>"></script>
                <script type="text/javascript">
                    window.checkTamaraWidgetCount = 0;
                    var existTamaraWidget = setInterval(function () {
                        if (window.TamaraWidget) {
                            window.TamaraWidget.init({
                                lang: '<?php echo $language_code; ?>',
                                currency: '<?php echo $order_data["currency_code"]; ?>',
                                publicKey: ''
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
                            publicKey: ''
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
            <?php if ($exists_pay_by_installments): ?>
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
                                    publicKey: ''
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
        <?php else: ?>
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
    <?php endif; ?>
    <script type="text/javascript">
        $('#button-confirm').on('click', function () {
            $.ajax({
                url: 'index.php?route=extension/payment/tamarapay/send',
                type: 'post',
                data: $('#payment input:checked'),
                dataType: 'json',
                beforeSend: function () {
                    $('.payment-warning').hide();

                    $('.payment-warning .message').text();

                    $('#payment').find('*').removeClass('has-error');

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
