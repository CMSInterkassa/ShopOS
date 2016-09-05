<?php
// Модуль разработан в компании GateOn предназначен для CMS ShopOS 2.5.9
// Сайт разработчикa: www.gateon.net
// E-mail: www@smartbyte.pro
// Версия модуля: 1.2

require('../../../includes/top.php');
require (_CLASS.'order.php');

//Запись в лог даты нового заказа
wrlog(date('l jS \of F Y h:i:s A'));


foreach ($_REQUEST as $key => $value) {
	$str = $key.' => '.$value;
	wrlog($str);
}
	$ik_payment_id = $_REQUEST['ik_pm_no']; 	
	$order = new order((int)$ik_payment_id);
	global $osPrice;
	$orderTotal = number_format($osPrice->CalculateCurrEx($order->info['total'], MODULE_PAYMENT_IK_CURRENCY), 2, '.', '');



 if(count($_REQUEST) && MODULE_PAYMENT_IK_SHOP_ID == $_REQUEST['ik_co_id']){

 	wrlog('params ok');

 	if ($_REQUEST['ik_inv_st'] == 'success'){

 		wrlog('success');

 			if(isset($_REQUEST['ik_pw_via']) && $_REQUEST['ik_pw_via'] == 'test_interkassa_test_xts'){
				$secret_key = MODULE_PAYMENT_IK_TEST_KEY;
			} else {
				$secret_key = MODULE_PAYMENT_IK_SECRET_KEY;
			}

 			$request_sign = $_REQUEST['ik_sign'];

 			$dataSet = $_REQUEST;

			unset($dataSet['ik_sign']);
			ksort($dataSet, SORT_STRING); 
			array_push($dataSet, $secret_key);  
			$signString = implode(':', $dataSet); 
			$sign = base64_encode(md5($signString, true)); 

 			if($request_sign != $sign){

 				die('Подписи не совпадают!');
 			
 			}

 		wrlog('order write');

		//Смена статуса заказа в админке
		$sql_data_array = array(
			'orders_status' => (int)MODULE_PAYMENT_IK_ORDER_STATUS_ID
		);
		os_db_perform(DB_PREFIX.'orders', $sql_data_array, 'update', "orders_id='".(int)$ik_payment_id."'");

		$sql_data_arrax = array(
			'orders_id' => (int)$ik_payment_id,
			'orders_status_id' => (int)MODULE_PAYMENT_IK_ORDER_STATUS_ID,
			'date_added' => 'now()',
			'customer_notified' => '0',
			'comments' => 'InterKassa accepted this order payment'
		);
		os_db_perform(DB_PREFIX.'orders_status_history', $sql_data_arrax);

 	}

} else {

	die('Параметры не совпадают!');

}
//Функция для ведения лога
function wrlog($content){
	$file = 'log.txt';
	$doc = fopen($file, 'a');
	file_put_contents($file, PHP_EOL . $content, FILE_APPEND);	
	fclose($doc);
}


?>