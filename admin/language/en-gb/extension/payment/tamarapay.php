<?php
// Heading
$_['heading_title']			= 'Tamarapay';

// Text
$_['text_extension']		= 'Extensions';
$_['text_success']			= 'Success: You have modified Tamarapay payment module!';
$_['text_edit']             = 'Edit Tamarapay';
$_['text_tamarapay']		= '<a href="http://tamarapay.com/" target="_blank"><img src="view/image/payment/tamarapay.png" alt="Tamarapay" title="Tamarapay" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_production']		= 'Production';
$_['text_sandbox']			= 'Sandbox';
$_['text_payment_info']		= 'Refund information';
$_['text_no_refund']		= 'No refund history';
$_['text_confirm_refund']	= 'Are you sure you want to refund';
$_['text_na']				= 'N/A';
$_['text_success_action']	= 'Success';
$_['text_error_generic']	= 'Error: There was an error with your request. Please check the logs.';

// Column
$_['column_refund']			= 'Refund';
$_['column_date']			= 'Date';
$_['column_refund_history'] = 'Refund History';
$_['column_action']			= 'Action';
$_['column_status']			= 'Status';
$_['column_amount']			= 'Amount';
$_['column_description']	= 'Description';

// Entry
$_['entry_total']			            = 'Total';
$_['entry_order_status_success']	    = 'Order status when checkout is success (after redirect)';
$_['entry_order_status_failure']	    = 'Order status when checkout is failure';
$_['entry_order_status_canceled']	    = 'Order status when checkout is cancelled';
$_['entry_order_status_authorised']	    = 'Order status when order is authorised';
$_['entry_geo_zone']		            = 'Geo Zone';
$_['entry_status']			            = 'Status';
$_['entry_sort_order']		            = 'Sort Order';
$_['entry_url']				            = 'API URL';
$_['entry_token']			            = 'Merchant Token';
$_['entry_debug']			            = 'Debug';
$_['entry_merchant_success_url']	    = 'Merchant Success URL';
$_['entry_merchant_failure_url']	    = 'Merchant Failure URL';
$_['entry_merchant_cancel_url']		    = 'Merchant Cancel URL';
$_['entry_merchant_notification_url']   = 'Merchant Notification URL';
$_['entry_token_notification']          = 'Notification Token';
$_['entry_enable_trigger_actions']      = 'Enable trigger to Tamara';
$_['entry_enable_iframe_checkout']      = 'Enable Iframe checkout';
$_['entry_enable_debug']                = 'Enable debug';
$_['entry_capture_order_status']        = 'Which order status do you want to trigger Tamara capture API? (You should set it to the status after the order is shipped)';
$_['entry_cancel_order_status']         = 'Which order status do you want to trigger Tamara cancel API? (You should set it to the status when order is canceled)';
$_['entry_title']                       = 'Title';
$_['entry_enable']                      = 'Enabled';
$_['entry_min_limit_amount']            = 'Min Limit Amount of Order';
$_['entry_max_limit_amount']            = 'Max Limit Amount of Order';
$_['entry_auto_fetching']               = 'Auto fetching in the first time after you save the credential';

// Help
$_['help_debug']			= 'Enabling debug will write sensitive data to a log file. You should always disable unless instructed otherwise.';
$_['help_total']			= 'The checkout total the order must reach before this payment method becomes active.';

// Button
$_['button_refund']			= 'Refund';

// Error
$_['error_url']				            = 'URL Required!';
$_['error_token']			            = 'Token Required!';
$_['error_notification_token_required'] = 'Notification token Required!';
$_['error_token_invalid']			    = 'Token Invalid!';
$_['error_composer']		            = 'Unable to load Tamarapay SDK. Please download a compiled vendor folder or run composer.';
$_['error_php_version']		            = 'Minimum version of PHP 7.0 is required!';
$_['error_permission']		            = 'Warning: You do not have permission to modify payment Tamarapay!';
$_['error_connection']		            = 'There was a problem establishing a connection to the Tamarapay API. Please check your URL and Token settings.';
$_['error_warning']			            = 'Warning: Please check the form carefully for errors!';
$_['error_merchant_success_url']        = 'Merchant Success URL Required';
$_['error_merchant_failure_url']        = 'Merchant Failure URL Required';
$_['error_merchant_cancel_url']         = 'Merchant Cancel URL Required';
$_['error_merchant_notification_url']   = 'Merchant Notification URL Required';
