<?php

/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS ShopOS 2.5.9
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.3
 * @update 26.10.2016
 */

require('../../../includes/top.php');
require(_CLASS . 'order.php');

//Запись в лог даты нового заказа
wrlog(date('l jS \of F Y h:i:s A'));

$ik_pm_no = $_POST['ik_pm_no'];

$order = new order((int)$ik_pm_no);

$ik_co_id = MODULE_PAYMENT_INTERKASSA_CO_ID;
if ($order->info['currency'] == 'RUR') {
    $ik_cur = 'RUB';
} else {
    $ik_cur = $order->info['currency'];
}


if (count($_POST) && checkIP() && $ik_co_id == $_POST['ik_co_id']) {
    wrlog('params ok');

    if ($_POST['ik_inv_st'] == 'success') {

        if (isset($_POST['ik_pw_via']) && $_POST['ik_pw_via'] == 'test_interkassa_test_xts') {
            $secret_key = MODULE_PAYMENT_INTERKASSA_T_KEY;
        } else {
            $secret_key = MODULE_PAYMENT_INTERKASSA_S_KEY;
        }

        $request = $_POST;
        $request_sign = $request['ik_sign'];
        unset($request['ik_sign']);

        //удаляем все поле которые не принимают участия в формировании цифровой подписи
        foreach ($request as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;
            $request[$key] = $value;
        }

        //формируем цифровую подпись
        ksort($request, SORT_STRING);
        array_push($request, $secret_key);
        $str = implode(':', $request);
        $sign = base64_encode(md5($str, true));

        wrlog($sign . '/' . $request_sign);

        //Если подписи совпадают то осуществляется смена статуса заказа в админке
        if ($request_sign == $sign) {


            //Смена статуса заказа в админке
            $sql_data_array = array(
                'orders_status' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID
            );
            os_db_perform(DB_PREFIX . 'orders', $sql_data_array, 'update', "orders_id='" . (int)$ik_pm_no . "'");

            $sql_data_arrax = array(
                'orders_id' => (int)$ik_pm_no,
                'orders_status_id' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => 'Платеж успешно оплачен с помощью Интеркассы'
            );
            os_db_perform(DB_PREFIX . 'orders_status_history', $sql_data_arrax);
            $_SESSION['cart']->reset(true);

        } else {
            $sql_data_array = array(
                'orders_status' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID
            );
            os_db_perform(DB_PREFIX . 'orders', $sql_data_array, 'update', "orders_id='" . (int)$ik_pm_no . "'");

            $sql_data_arrax = array(
                'orders_id' => 3,
                'orders_status_id' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => 'Цифровая подпись не совпала: ' . $request_sign . '!=' . $sign
            );
            os_db_perform(DB_PREFIX . 'orders_status_history', $sql_data_arrax);

        }
    } else {
        $sql_data_array = array(
            'orders_status' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID
        );
        os_db_perform(DB_PREFIX . 'orders', $sql_data_array, 'update', "orders_id='" . (int)$ik_pm_no . "'");

        $sql_data_arrax = array(
            'orders_id' => 3,
            'orders_status_id' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'Ответ Интеркассы неправильный'
        );
        os_db_perform(DB_PREFIX . 'orders_status_history', $sql_data_arrax);
    }

} else {
    $sql_data_array = array(
        'orders_status' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID
    );
    os_db_perform(DB_PREFIX . 'orders', $sql_data_array, 'update', "orders_id='" . (int)$ik_pm_no . "'");

    $sql_data_arrax = array(
        'orders_id' => 3,
        'orders_status_id' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID,
        'date_added' => 'now()',
        'customer_notified' => '0',
        'comments' => 'Попытка взлома с айпи:' . $_SERVER['REMOTE_ADDR']
    );
    os_db_perform(DB_PREFIX . 'orders_status_history', $sql_data_arrax);
}


//	global $osPrice;
//	$orderTotal = number_format($osPrice->CalculateCurrEx($order->info['total'], MODULE_PAYMENT_IK_CURRENCY), 2, '.', '');


// if(count($_POST) && MODULE_PAYMENT_IK_SHOP_ID == $_POST['ik_co_id']){
//
// 	wrlog('params ok');
//
// 	if ($_POST['ik_inv_st'] == 'success'){
//
// 		wrlog('success');
//
// 			if(isset($_POST['ik_pw_via']) && $_POST['ik_pw_via'] == 'test_interkassa_test_xts'){
//				$secret_key = MODULE_PAYMENT_INTERKASSA_T_KEY;
//			} else {
//				$secret_key = MODULE_PAYMENT_INTERKASSA_S_KEY;
//			}
//
// 			$request_sign = $_POST['ik_sign'];
//
// 			$dataSet = $_POST;
//
//			unset($dataSet['ik_sign']);
//			ksort($dataSet, SORT_STRING);
//			array_push($dataSet, $secret_key);
//			$signString = implode(':', $dataSet);
//			$sign = base64_encode(md5($signString, true));
//
// 			if($request_sign != $sign){
//
// 				die('Подписи не совпадают!');
//
// 			}
//
// 		wrlog('order write');
//
//		//Смена статуса заказа в админке
//		$sql_data_array = array(
//			'orders_status' => (int)MODULE_PAYMENT_IK_ORDER_STATUS_ID
//		);
//		os_db_perform(DB_PREFIX.'orders', $sql_data_array, 'update', "orders_id='".(int)$ik_payment_id."'");
//
//		$sql_data_arrax = array(
//			'orders_id' => (int)$ik_payment_id,
//			'orders_status_id' => (int)MODULE_PAYMENT_IK_ORDER_STATUS_ID,
//			'date_added' => 'now()',
//			'customer_notified' => '0',
//			'comments' => 'InterKassa accepted this order payment'
//		);
//		os_db_perform(DB_PREFIX.'orders_status_history', $sql_data_arrax);
//
// 	}
//
//} else {
//
//	die('Параметры не совпадают!');
//
//}
//Функция для ведения лога
function wrlog($content)
{
    $file = 'log.txt';
    $doc = fopen($file, 'a');

    file_put_contents($file, PHP_EOL . '====================' . date("H:i:s") . '=====================', FILE_APPEND);
    if (is_array($content)) {
        foreach ($content as $k => $v) {
            if (is_array($v)) {
                wrlog($v);
            } else {
                file_put_contents($file, PHP_EOL . $k . '=>' . $v, FILE_APPEND);
            }
        }
    } else {
        file_put_contents($file, PHP_EOL . $content, FILE_APPEND);
    }
    fclose($doc);
}

function checkIP()
{
    $ip_stack = array(
        'ip_begin' => '151.80.190.97',
        'ip_end' => '151.80.190.104'
    );

    if (!ip2long($_SERVER['REMOTE_ADDR']) >= ip2long($ip_stack['ip_begin']) && !ip2long($_SERVER['REMOTE_ADDR']) <= ip2long($ip_stack['ip_end'])) {
        wrlog('REQUEST IP' . $_SERVER['REMOTE_ADDR'] . 'doesnt match');
        die('Ты мошенник! Пшел вон отсюда!');
    }
    return true;
}


?>