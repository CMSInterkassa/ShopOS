<?php
foreach(array(
  array('MODULE_PAYMENT_INTERKASSA_TEXT_TITLE','Интеркасса'),
  array('MODULE_PAYMENT_INTERKASSA_TEXT_DESCRIPTION','Оплата: Webmoney, RBKMoney, MoneyMail, WebCreds, НСМЭП, Приват24 и другими платежным системами'),
  array('MODULE_PAYMENT_INTERKASSA_T_MODE_TITLE','Тестовый режим'),
  array('MODULE_PAYMENT_INTERKASSA_API_ID_TITLE','API ID'),
  array('MODULE_PAYMENT_INTERKASSA_API_KEY_TITLE','API KEY')
) as $v) define($v[0],$v[isset($v[1])?1:0]);
define('MODULE_PAYMENT_INTERKASSA_STATUS_TITLE', 'Включить модуль Интеркасса');
define('MODULE_PAYMENT_INTERKASSA_STATUS_DESC', 'Разрешить использование модуля при оформлении заказа?');
define('MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID_TITLE', 'Укажите оплаченный статус заказа');
define('MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_DESC', 'Укажите оплаченный статус заказа');
define('MODULE_PAYMENT_INTERKASSA_SORT_ORDER_TITLE', 'Порядок сортировки');
define('MODULE_PAYMENT_INTERKASSA_SORT_ORDER_DESC', 'Порядок сортировки модуля.');
define('MODULE_PAYMENT_INTERKASSA_CO_ID_TITLE', 'ID магазина');
define('MODULE_PAYMENT_INTERKASSA_CO_ID_DESC', 'Идентификатор магазина');
define('MODULE_PAYMENT_INTERKASSA_S_KEY_TITLE', 'Секретный ключ');
define('MODULE_PAYMENT_INTERKASSA_S_KEY_DESC', 'Секретный ключ безопасности');
define('MODULE_PAYMENT_INTERKASSA_T_KEY_TITLE', 'Тестовый ключ');
define('MODULE_PAYMENT_INTERKASSA_T_KEY_DESC', 'Тестовый ключ безопасности');
//define('MODULE_PAYMENT_INTERKASSA_CURRENCY_TITLE', 'Валюта');
//define('MODULE_PAYMENT_INTERKASSA_CURRENCY_DESC', 'Валюта, в которой магазин передает сумму плетежа на платежный шлюз "Интеркасса"');
define('MODULE_PAYMENT_IK_ZONE_TITLE', 'Payment Zone');
define('MODULE_PAYMENT_IK_ZONE_DESC', 'If a zone is selected, only enable this payment method for that zone.');
