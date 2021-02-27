<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-payment" data-toggle="tooltip" title="<?php echo $button_save ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb): ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if($error_warning): ?>
        <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action ?>" method="post" enctype="multipart/form-data" id="form-payment" class="form-horizontal">

                    <div class="panel-group" id="tamara-payment-api-configurations">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title"><a href="#collapse-api-configurations" data-toggle="collapse" data-parent="#tamara-payment-api-configurations" class="accordion-toggle collapsed" aria-expanded="false">API Configurations <i class="fa fa-caret-down"></i></a></h4>
                            </div>
                            <div class="panel-collapse collapse" id="collapse-api-configurations" aria-expanded="false" style="height: 0px;">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-12">

                                            <div class="form-group required">
                                                <label class="col-sm-2 control-label" for="input-url"><?php echo $entry_url ?></label>
                                                <div class="col-sm-10">
                                                    <input type="text" name="tamarapay_url" value="<?php echo $tamarapay_url ?>" placeholder="<?php echo $entry_url ?>" id="input-url" class="form-control"/>
                                                    <?php if($error_url): ?>
                                                    <div class="text-danger"><?php echo $error_url ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="form-group required">
                                                <label class="col-sm-2 control-label" for="input-token"><?php echo $entry_token ?></label>
                                                <div class="col-sm-10">
                                                    <textarea class="form-control" id="input-token" placeholder="<?php echo $entry_token ?>" rows="7" name="tamarapay_token"><?php echo $tamarapay_token ?></textarea>
                                                    <?php if($error_token): ?>
                                                    <div class="text-danger"><?php echo $error_token ?></div>
                                                    <?php endif ?>
                                                </div>
                                            </div>
                                            <div class="form-group required">
                                                <label class="col-sm-2 control-label" for="input-token-notification"><?php echo $entry_token_notification ?></label>
                                                <div class="col-sm-10">
                                                    <textarea class="form-control" id="input-token-notification" placeholder="<?php echo $entry_token_notification ?>" rows="7" name="tamarapay_token_notification"><?php echo $tamarapay_token_notification ?></textarea>
                                                    <?php if ($error_token_notification): ?>
                                                    <div class="text-danger"><?php echo $error_token_notification; ?></div>
                                                    <?php endif ?>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label" for="input-trigger-actions-enabled"><?php echo $entry_enable_trigger_actions ?></label>
                                                <div class="col-sm-10">
                                                    <select name="tamarapay_trigger_actions_enabled" id="input-trigger-actions-enabled" class="form-control">
                                                        <?php if ($tamarapay_trigger_actions_enabled): ?>
                                                        <option value="1" selected="selected"><?php echo $text_enabled ?></option>
                                                        <option value="0"><?php echo $text_disabled ?></option>
                                                        <?php else: ?>
                                                        <option value="1"><?php echo $text_enabled ?></option>
                                                        <option value="0" selected="selected"><?php echo $text_disabled ?></option>
                                                        <?php endif ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label" for="input-iframe-checkout-enabled"><?php echo $entry_enable_iframe_checkout ?></label>
                                                <div class="col-sm-10">
                                                    <select name="tamarapay_iframe_checkout_enabled" id="input-iframe-checkout-enabled" class="form-control">
                                                        <?php if ($tamarapay_iframe_checkout_enabled): ?>
                                                        <option value="1" selected="selected"><?php echo $text_enabled ?></option>
                                                        <option value="0"><?php echo $text_disabled ?></option>
                                                        <?php else: ?>
                                                        <option value="1"><?php echo $text_enabled ?></option>
                                                        <option value="0" selected="selected"><?php echo $text_disabled ?></option>
                                                        <?php endif ?>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-group" id="tamara-payment-types">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title"><a href="#collapse-payment-types" data-toggle="collapse" data-parent="#tamara-payment-types" class="accordion-toggle collapsed" aria-expanded="false">Payment types <i class="fa fa-caret-down"></i></a></h4>
                            </div>
                            <div class="panel-collapse collapse" id="collapse-payment-types" aria-expanded="false" style="height: 0px;">
                                <div class="panel-body">


                                    <div class="panel-group" id="pay-by-later-group">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><a href="#collapse-pay-by-later" data-toggle="collapse" data-parent="#pay-by-later-group" class="accordion-toggle" aria-expanded="true">Pay later in 30 days<i class="fa fa-caret-down"></i></a></h4>
                                            </div>
                                            <div class="panel-collapse collapse in" id="collapse-pay-by-later" aria-expanded="true">
                                                <div class="panel-body">
                                                    <div class="row">

                                                        <div class="row form-group required">
                                                            <label class="col-sm-4 control-label" for="pay-by-later-enabled"><?php echo $entry_enable ?></label>
                                                            <div class="col-sm-8">

                                                                <select name="tamarapay_types_pay_by_later_enabled" id="pay-by-later-enabled" class="form-control">
                                                                    <?php if ($tamarapay_types_pay_by_later_enabled): ?>
                                                                    <option value="1" selected="selected"><?php echo $text_enabled ?></option>
                                                                    <option value="0"><?php echo $text_disabled ?></option>
                                                                    <?php else: ?>
                                                                    <option value="1"><?php echo $text_enabled ?></option>
                                                                    <option value="0" selected="selected"><?php echo $text_disabled ?></option>
                                                                    <?php endif ?>
                                                                </select>

                                                            </div>
                                                        </div>
                                                        <div class="row form-group">
                                                            <label class="col-sm-4 control-label" for="tamarapay_types_pay_by_later_min_limit"><?php echo $entry_min_limit_amount ?></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" data-toggle="tooltip" title="<?php echo $entry_auto_fetching?>" id="tamarapay_types_pay_by_later_min_limit" name="tamarapay_types_pay_by_later_min_limit" value="<?php echo $tamarapay_types_pay_by_later_min_limit ?>" placeholder="<?php echo $entry_min_limit_amount ?>" class="form-control" readonly/>
                                                            </div>
                                                        </div>
                                                        <div class="row form-group">
                                                            <label class="col-sm-4 control-label" for="tamarapay_types_pay_by_later_max_limit"><?php echo $entry_max_limit_amount ?></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" data-toggle="tooltip" title="<?php echo $entry_auto_fetching?>" id="tamarapay_types_pay_by_later_max_limit" name="tamarapay_types_pay_by_later_max_limit" value="<?php echo $tamarapay_types_pay_by_later_max_limit ?>" placeholder="<?php echo $entry_max_limit_amount ?>" class="form-control" readonly/>
                                                                <input type="hidden" id="tamarapay_types_pay_by_later_currency" name="tamarapay_types_pay_by_later_currency" value="<?php echo $tamarapay_types_pay_by_later_currency ?>" />
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel-group" id="pay-by-instalments-group">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><a href="#collapse-pay-by-instalments" data-toggle="collapse" data-parent="#pay-by-instalments-group" class="accordion-toggle" aria-expanded="true">Pay in 3 instalments <i class="fa fa-caret-down"></i></a></h4>
                                            </div>
                                            <div class="panel-collapse collapse in" id="collapse-pay-by-instalments" aria-expanded="true">
                                                <div class="panel-body">
                                                    <div class="row">

                                                        <div class="row form-group required">
                                                            <label class="col-sm-4 control-label" for="pay-by-instalments-enabled"><?php echo $entry_enable ?></label>
                                                            <div class="col-sm-8">

                                                                <select name="tamarapay_types_pay_by_instalments_enabled" id="pay-by-instalments-enabled" class="form-control">
                                                                    <?php if ($tamarapay_types_pay_by_instalments_enabled): ?>
                                                                    <option value="1" selected="selected"><?php echo $text_enabled ?></option>
                                                                    <option value="0"><?php echo $text_disabled ?></option>
                                                                    <?php else: ?>
                                                                    <option value="1"><?php echo $text_enabled ?></option>
                                                                    <option value="0" selected="selected"><?php echo $text_disabled ?></option>
                                                                    <?php endif ?>
                                                                </select>

                                                            </div>
                                                        </div>
                                                        <div class="row form-group">
                                                            <label class="col-sm-4 control-label" for="tamarapay_types_pay_by_instalments_min_limit"><?php echo $entry_min_limit_amount ?></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" data-toggle="tooltip" title="<?php echo $entry_auto_fetching?>" id="tamarapay_types_pay_by_instalments_min_limit" name="tamarapay_types_pay_by_instalments_min_limit" value="<?php echo $tamarapay_types_pay_by_instalments_min_limit ?>" placeholder="<?php echo $entry_min_limit_amount ?>" class="form-control" readonly />
                                                            </div>
                                                        </div>
                                                        <div class="row form-group">
                                                            <label class="col-sm-4 control-label" for="tamarapay_types_pay_by_instalments_max_limit"><?php echo $entry_max_limit_amount ?></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" data-toggle="tooltip" title="<?php echo $entry_auto_fetching?>" id="tamarapay_types_pay_by_instalments_max_limit" name="tamarapay_types_pay_by_instalments_max_limit" value="<?php echo $tamarapay_types_pay_by_instalments_max_limit ?>" placeholder="<?php echo $entry_max_limit_amount ?>" class="form-control" readonly />
                                                                <input type="hidden" id="tamarapay_types_pay_by_instalments_currency" name="tamarapay_types_pay_by_instalments_currency" value="<?php echo $tamarapay_types_pay_by_instalments_currency ?>" />
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($tamarapay_types_pay_by_instalments_max_limit): ?>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <button type="button" id="update-payment-config">Update config</button>
                                            <span id="update-payment-config-message" style="display: none;"></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-group" id="tamara-payment-order-statuses">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title"><a href="#collapse-order-statuses" data-toggle="collapse" data-parent="#tamara-payment-order-statuses" class="accordion-toggle collapsed" aria-expanded="false">Checkout order statuses <i class="fa fa-caret-down"></i></a></h4>
                            </div>
                            <div class="panel-collapse collapse" id="collapse-order-statuses" aria-expanded="false" style="height: 0px;">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-12">




                                            <div class="form-group">
                                                <label class="col-sm-2 control-label" for="input-order-status-success"><?php echo $entry_order_status_success ?></label>
                                                <div class="col-sm-10">
                                                    <select name="tamarapay_order_status_success_id" id="input-order-status-success" class="form-control">
                                                        <?php foreach ($order_statuses as $order_status): ?>
                                                        <?php if ($order_status['order_status_id'] == $tamarapay_order_status_success_id): ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>" selected="selected"><?php echo $order_status['name'] ?></option>
                                                        <?php else: ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>"><?php echo $order_status['name'] ?></option>
                                                        <?php endif ?>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label" for="input-order-status-failure"><?php echo $entry_order_status_failure ?></label>
                                                <div class="col-sm-10">
                                                    <select name="tamarapay_order_status_failure_id" id="input-order-status-failure" class="form-control">
                                                        <?php foreach ($order_statuses as $order_status): ?>
                                                        <?php if ($order_status['order_status_id'] == $tamarapay_order_status_failure_id): ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>" selected="selected"><?php echo $order_status['name'] ?></option>
                                                        <?php else: ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>"><?php echo $order_status['name'] ?></option>
                                                        <?php endif ?>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label" for="input-order-status-canceled"><?php echo $entry_order_status_canceled ?></label>
                                                <div class="col-sm-10">
                                                    <select name="tamarapay_order_status_canceled_id" id="input-order-status-canceled" class="form-control">
                                                        <?php foreach ($order_statuses as $order_status): ?>
                                                        <?php if ($order_status['order_status_id'] == $tamarapay_order_status_canceled_id): ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>" selected="selected"><?php echo $order_status['name'] ?></option>
                                                        <?php else: ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>"><?php echo $order_status['name'] ?></option>
                                                        <?php endif ?>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label" for="input-order-status-authorised"><?php echo $entry_order_status_authorised ?></label>
                                                <div class="col-sm-10">
                                                    <select name="tamarapay_order_status_authorised_id" id="input-order-status-authorised" class="form-control">
                                                        <?php foreach ($order_statuses as $order_status): ?>
                                                        <?php if ($order_status['order_status_id'] == $tamarapay_order_status_authorised_id): ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>" selected="selected"><?php echo $order_status['name'] ?></option>
                                                        <?php else: ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>"><?php echo $order_status['name'] ?></option>
                                                        <?php endif ?>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-group" id="tamara-payment-capture-order-statuses">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title"><a href="#collapse-capture-order-statuses" data-toggle="collapse" data-parent="#tamara-payment-capture-order-statuses" class="accordion-toggle collapsed" aria-expanded="false">Tamara trigger configuration <i class="fa fa-caret-down"></i></a></h4>
                            </div>
                            <div class="panel-collapse collapse" id="collapse-capture-order-statuses" aria-expanded="false" style="height: 0px;">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="col-sm-6 control-label" for="input-capture-order-status"><?php echo $entry_capture_order_status ?></label>
                                                <div class="col-sm-6">
                                                    <select name="tamarapay_capture_order_status_id" id="input-capture-order-status" class="form-control">
                                                        <?php foreach ($order_statuses as $order_status): ?>
                                                        <?php if ($order_status['order_status_id'] == $tamarapay_capture_order_status_id): ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>" selected="selected"><?php echo $order_status['name'] ?></option>
                                                        <?php else: ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>"><?php echo $order_status['name'] ?></option>
                                                        <?php endif ?>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="col-sm-6 control-label" for="input-cancel-order-status"><?php echo $entry_cancel_order_status ?></label>
                                                <div class="col-sm-6">
                                                    <select name="tamarapay_cancel_order_status_id" id="input-cancel-order-status" class="form-control">
                                                        <?php foreach ($order_statuses as $order_status): ?>
                                                        <?php if ($order_status['order_status_id'] == $tamarapay_cancel_order_status_id): ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>" selected="selected"><?php echo $order_status['name'] ?></option>
                                                        <?php else: ?>
                                                        <option value="<?php echo $order_status['order_status_id'] ?>"><?php echo $order_status['name'] ?></option>
                                                        <?php endif ?>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-group" id="tamara-payment-general-configuration">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">General configuration</h4>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="input-debug-enabled"><?php echo $entry_enable_debug ?></label>
                                    <div class="col-sm-10">
                                        <select name="tamarapay_debug" id="input-debug-enabled" class="form-control">
                                            <?php if ($tamarapay_debug): ?>
                                            <option value="1" selected="selected"><?php echo $text_enabled ?></option>
                                            <option value="0"><?php echo $text_disabled ?></option>
                                            <?php else: ?>
                                            <option value="1"><?php echo $text_enabled ?></option>
                                            <option value="0" selected="selected"><?php echo $text_disabled ?></option>
                                            <?php endif ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone ?></label>
                                    <div class="col-sm-10">
                                        <select name="tamarapay_geo_zone_id" id="input-geo-zone" class="form-control">
                                            <option value="0"><?php echo $text_all_zones ?></option>
                                            <?php foreach ($geo_zones as $geo_zone):  ?>
                                            <?php if ($geo_zone['geo_zone_id'] == $tamarapay_geo_zone_id): ?>
                                            <option value="<?php echo $geo_zone['geo_zone_id'] ?>" selected="selected"><?php echo $geo_zone['name'] ?></option>
                                            <?php else: ?>
                                            <option value="<?php echo $geo_zone['geo_zone_id'] ?>"><?php echo $geo_zone['name'] ?></option>
                                            <?php endif ?>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status ?></label>
                                    <div class="col-sm-10">
                                        <select name="tamarapay_status" id="input-status" class="form-control">
                                            <?php if ($tamarapay_status): ?>
                                            <option value="1" selected="selected"><?php echo $text_enabled ?></option>
                                            <option value="0"><?php echo $text_disabled ?></option>
                                            <?php else: ?>
                                            <option value="1"><?php echo $text_enabled ?></option>
                                            <option value="0" selected="selected"><?php echo $text_disabled ?></option>
                                            <?php endif ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order ?></label>
                                    <div class="col-sm-10">
                                        <input type="text" name="tamarapay_sort_order" value="<?php echo $tamarapay_sort_order ?>" placeholder="<?php echo $entry_sort_order ?>" id="input-sort-order" class="form-control" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript"><!--
    $('#update-payment-config').on('click', function() {
        $.ajax({
            url: 'index.php?route=payment/tamarapay/retrievePaymentConfig&token=<?php echo $token ?>',
            type: 'get',
            contentType: 'application/json',
            dataType: 'json',
            data: {},
            beforeSend: function() {
                $('#update-payment-config').attr("disabled", true);
            },
            complete: function() {
                $('#update-payment-config').attr("disabled", false);
            },
            success: function(rs) {
                if (rs.success == true) {
                    let paymentTypes = rs.payment_types;
                    for (let i = 0; i < paymentTypes.length; i++) {
                        let name = paymentTypes[i].name.toLowerCase();
                        let elementMinLimit = '#tamarapay_types_' + name  + '_min_limit';
                        $(elementMinLimit).val(paymentTypes[i].min_limit.amount);
                        let elementMaxLimit = '#tamarapay_types_' + name  + '_max_limit';
                        $(elementMaxLimit).val(paymentTypes[i].max_limit.amount);
                        let elementCurrency = '#tamarapay_types_' + name  + '_currency';
                        $(elementCurrency).val(paymentTypes[i].max_limit.currency);
                        $('#update-payment-config-message').text("Update config successful").addClass("text-success").show();
                    }
                } else {
                    $('#update-payment-config-message').text("Update config failed, please check log file").addClass("text-danger").show();
                }

                $('#update-payment-config').attr("disabled", false);
            }
        });
    });
//--></script>
<?php echo $footer; ?>