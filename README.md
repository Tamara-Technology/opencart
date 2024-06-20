## Available Versions
* **Tamara payment for OpenCart 4.0.x**
* [Tamara payment for OpenCart 3.0.x](https://github.com/tamara-solution/opencart/tree/master)
* [Tamara payment for OpenCart 2.3.x](https://github.com/tamara-solution/opencart/tree/v2)
* [Tamara payment for OpenCart 2.0.x](https://github.com/tamara-solution/opencart/tree/v20x)

# Tamara payment 1.0.0 for OpenCart 4.x
* Tamara payment for OpenCart allows your users to pay with Tamara on Opencart

# Technical Requirements
* PHP >= 7.3

## Installation
* Download tamarapay.ocmod.zip
* Go to Admin dashboard > Extensions > Installer
* Click upload button > choose the file you have downloaded
* Choose Tamarapay under Installed Extensions > Install
* Go to Admin dashboard > Extensions > Extensions
* Choose the extension type > Payments
* Choose Tamarapay under Payments > Install
* Click Edit button

## Upgrade extension version
* Go to Admin dashboard > Extensions > Extensions
* Choose the extension type > Payments
* Choose Tamarapay under Payments > Uninstall
* Go to Admin dashboard > Extensions > Installer
* Choose Tamarapay under Installed Extensions > Uninstall > Delete
* Download tamarapay.ocmod.zip
* Go to Admin dashboard > Extensions > Installer
* Click upload button > choose the file you have downloaded
* Choose Tamarapay under Installed Extensions > Install
* Go to Admin dashboard > Extensions > Extensions
* Choose the extension type > Payments
* Choose Tamarapay under Payments > Install
* Click Edit button

## Update orders from command line
We support updating orders manually via command line
* First, give it execute permission
```text
chmod +x {web_root}/system/library/tamara/console.php
```
* After that, exec this command
```text
php {web_root}/system/library/tamara/console.php tamara:orders-scan --start-time="{start_time}" --end-time="{end_time}"
```
with {start_time} and {end_time} are the creation times of the orders to be scanned (yyyy-mm-dd hh:mm:ss)
* For example, to update orders created in the last 30 minutes
```text
php {web_root}/system/library/tamara/console.php tamara:orders-scan --start-time="-30 minutes"
```
<br />
to update orders that created from 2021-01-01 00:00:00 to 2021-01-01 23:59:59

```text
php {web_root}/system/library/tamara/console.php tamara:orders-scan --start-time="2021-01-01 00:00:00" --end-time="2021-01-01 23:59:59"
```

## Get checkout information by order id via API
End point:
```text
index.php?route=extension/tamarapay/payment/api/tamarapay.checkout_information&key={your_api_key}&order_id={opencart_order_id}
```
Method: GET
