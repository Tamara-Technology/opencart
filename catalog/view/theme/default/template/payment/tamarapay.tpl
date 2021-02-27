<style type="text/css">
    .container-customize {max-width: 900px; width: 100%; margin: 0 auto;}
    .four { width: 32.26%; max-width: 32.26%;}
    .form-customize .plan input {
        display: none;
    }
    .form-customize .plan input.submit-form {
        display: block;
    }
    .form-customize .form-submit {
        margin-left: 10px;
    }
    /* COLUMNS */

    .col {
        display: block;
        float:left;
        margin: 1% 0 1% 1%;
    }

    /* CLEARFIX */

    .cf:before,
    .cf:after {
        content: " ";
        display: table;
    }

    .cf:after {
        clear: both;
    }

    .cf {
        *zoom: 1;
    }

    .form-customize label{
        position: relative;
        color: #666;
        background-color: #f5f5f5;
        font-size: 15px;
        text-align: center;
        height: 150px;
        line-height: 40px;
        display: block;
        cursor: pointer;
        border: 3px solid transparent;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
    }

    .form-customize .plan input:checked + label{
        border: 3px solid #333;
        background-color: #229ac8;
        color: #fff;
    }

    .form-customize .plan input:checked + label:after{
        content: "\2713";
        width: 40px;
        height: 40px;
        line-height: 40px;
        border-radius: 100%;
        border: 2px solid #333;
        background-color: #2fcc71;
        z-index: 999;
        position: absolute;
        top: -10px;
        right: -10px;
    }
    .customize-description {
        position: relative;
        height: 150px;
        line-height: 20px;
    }
    .customize-description p {
        margin: 10px 0 0 0;
    }
    a.tamara-widget {
        padding: 5px !important;
    }
    div.tamara-product-widget {
        text-decoration: underline;
        cursor: pointer;
    }
    section.payment-types {
        min-height: 200px; margin-bottom: 50px;
    }
    .payment-type-title {
        margin-bottom: 80px;
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
    <div class="container-customize">
        <form class="form-horizontal" id="payment">
            <section class="payment-types">
                <div class="payment-type-title">
                    <h3 class="text-center"><?php echo $text_choose_payment ?></h3>
                    <div class="col-sm-12">
                        <div class="col-sm-4"></div>
                        <div class="col-sm-4 text-center">
                            <a href="javascript:void(0)" class="tamara-widget" data-lang="<?php echo $language_code ?>" data-currency="<?php echo $order_data['currency_code'] ?>" data-installment-minimum-amount="<?php echo $installment_min_limit ?>"></a>
                        </div>
                        <div class="col-sm-4"></div>
                    </div>
                </div>
                <div class="payment-type-content">
                    <?php foreach ($methods as $key => $method): ?>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-check">
                                <?php if ($method['is_available']): ?>
                                    <?php if ($method['checked']): ?>
                                        <input type="radio" name="payment_type" class="form-check-input" id="<?php echo $method['name'] ?>" value="<?php echo $method['name'] ?>" checked>
                                    <?php else: ?>
                                        <input type="radio" name="payment_type" class="form-check-input" id="<?php echo $method['name'] ?>" value="<?php echo $method['name'] ?>">
                                    <?php endif; ?>
                                    <label for="<?php echo $method['name'] ?>" class="form-check-label"><b><?php echo $method['title'] ?></b></label>
                                    <br />
                                <?php else: ?>
                                    <input type="radio" name="payment_type" class="form-check-input" id="<?php echo $method['name'] ?>" value="<?php echo $method['name'] ?>" disabled>
                                    <label for="<?php echo $method['name'] ?>" class="form-check-label"><b><?php echo $method['title'] ?></b></label>
                                    <br />
                                <?php endif; ?>
                                <label for="<?php echo $method['name'] ?>" class="form-check-label"><b><?php echo $text_min_amount ?></b> <?php echo $method['min_limit'] ?> <?php echo $method['currency'] ?></label>
                                <br />
                                <label for="<?php echo $method['name'] ?>" class="form-check-label"><b><?php echo $text_max_amount ?></b> <?php echo $method['max_limit'] ?> <?php echo $method['currency'] ?></label>
                                <br />
                                <?php if ($method['is_available']): ?>
                                <label>
                                    <?php if ($method['name'] == 'PAY_BY_INSTALMENTS'): ?>
                                        <div class="tamara-product-widget" data-lang="<?php echo $language_code ?>" data-price="<?php echo $order_data['total'] ?>" data-currency="<?php echo $method['currency'] ?>" data-payment-type="installment" data-installment-minimum-amount="<?php echo $method['min_limit'] ?>" data-inject-template="false"><?php echo $text_more_details ?></div>
                                    <?php else: ?>
                                        <div class="tamara-product-widget" data-lang="<?php echo $language_code ?>" data-inject-template="false"><?php echo $text_more_details ?></div>
                                    <?php endif; ?>
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php if ($total_method_available > 0): ?>
            <div class="form-group form-submit col-sm-12 text-center">
                <input id="button-confirm" type="button" value="<?php echo $button_confirm ?>" class="btn btn-primary submit-form" />
            </div>
            <?php else: ?>
                <p class="text-danger font-weight-bold" style="font-weight: bold;">
                    <?php echo $error_no_method_available ?>
                </p>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($use_iframe_checkout): ?>
        <script src="https://cdn.tamara.co/checkout/checkoutFrame.min.js?v=1.1"></script>
        <script type="text/javascript">
            function success() {
                window.location = '<?php echo $merchant_urls["success"] ?>';
            }

            function failed() {
                window.location = '<?php echo $merchant_urls["failure"] ?>';
            }

            function cancel() {
                window.location = '<?php echo $merchant_urls["cancel"] ?>';
            }

            function init() {
                TamaraFrame.addEventHandlers(TamaraFrame.Events.SUCCESS, success);
                TamaraFrame.addEventHandlers(TamaraFrame.Events.FAILED, failed);
                TamaraFrame.addEventHandlers(TamaraFrame.Events.CANCELED, cancel);
            }

            var tamaraCKOConfig = {
                namespace: 'TamaraFrame',
                init: init,
            }
        </script>
    <?php endif; ?>

    <script charset="utf-8" src="https://cdn.tamara.co/widget/tamara-widget.min.js"></script>
    <script type="text/javascript">
        window.checkTamaraWidgetCount = 0;
        var existTamaraWidget = setInterval(function() {
            if (window.TamaraWidget) {
                window.TamaraWidget.init({ lang: '<?php echo $language_code ?>' });
                window.TamaraWidget.render();
                clearInterval(existTamaraWidget);
            }
            window.checkTamaraWidgetCount += 1;
            if (window.checkTamaraWidgetCount > 15) {
                clearInterval(existTamaraWidget);
            }
        }, 300);
    </script>

    <script charset="utf-8" src="https://cdn.tamara.co/widget/product-widget.min.js"></script>
    <script type="text/javascript">
        window.checkTamaraProductWidgetCount = 0;
        var existTamaraProductWidget = setInterval(function() {
            if (window.TamaraProductWidget) {
                window.TamaraProductWidget.init({ lang: '<?php echo $language_code ?>' });
                window.TamaraProductWidget.render();
                clearInterval(existTamaraProductWidget);
            }
            window.checkTamaraProductWidgetCount += 1;
            if (window.checkTamaraProductWidgetCount > 15) {
                clearInterval(existTamaraProductWidget);
            }
        }, 300);
    </script>

    <script type="text/javascript">
        $('#button-confirm').on('click', function() {
            $.ajax({
                url: 'index.php?route=payment/tamarapay/send',
                type: 'post',
                data: $('#payment input:checked'),
                dataType: 'json',
                beforeSend: function() {
                    $('.payment-warning').hide();

                    $('.payment-warning .message').text();

                    $('#payment').find('*').removeClass('has-error');

                    $('#button-confirm').button('loading').attr('disabled', true);
                },
                complete: function() {
                    $('#button-confirm').button('reset');
                },
                success: function(json) {
                    if (json['redirectUrl']) {
                        <?php if ($use_iframe_checkout): ?>
                            TamaraFrame.checkout(json.redirectUrl);
                        <?php else: ?>
                            window.location = json['redirectUrl'];
                        <?php endif; ?>
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
