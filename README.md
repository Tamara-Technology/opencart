## Available Versions
* **Tamara payment for OpenCart 3.0.x**
* [Tamara payment for OpenCart 2.3.x](https://github.com/tamara-solution/opencart/tree/v2)
* [Tamara payment for OpenCart 2.0.x](https://github.com/tamara-solution/opencart/tree/v20x)

# Tamara payment for OpenCart 3.x
* Tamara payment for OpenCart allows your users to pay with Tamara on Opencart

# Technical Requirements
* PHP >= 7.3

## Installation
* Download and extract it to your web root
* Go to Admin dashboard > Extensions > Extensions
* Choose Tamarapay under Payments
* Click install button.

## Upgrade extension version
If you already install tamara-php-sdk via composer, you need to remove it before continue
```text
composer remove tamara-solution/php-sdk
composer remove symfony/console
composer remove symfony/cache
```

* Download and extract the extension to your web root
* Go to Admin dashboard > Extensions > Extensions
* Choose Tamarapay under Payments
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
index.php?route=extension/payment/api/tamarapay/checkout_information&key={your_api_key}&order_id={opencart_order_id}
```
Method: GET
