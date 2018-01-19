<?php
defined('_VALID_OS') or die('Direct Access to this location is not allowed.');
class interkassa
{
  protected $ik_dir = '/modules/payment/interkassa/';
  public $order;
	public $code = 'interkassa';
	public $title;
	public $description;
  public $icon = 'icon.png';
  public $icon_small = 'icon_small.png';
  public $sort_order;
	public $enabled;
	public $name = 'cart_interkassa_id';
  public $form_action_url = 'https://sci.interkassa.com/';
  public $order_status;
  public $ik_co_id;
  public $s_key;
  public $t_key;
  public $form;
  public $ik_pm_no;
  public function __construct(){
    global $order;
    $this->order = &$order;
    if (is_object($order))
      $this->update_status();
    $this->title = MODULE_PAYMENT_INTERKASSA_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_INTERKASSA_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_PAYMENT_INTERKASSA_SORT_ORDER;
    $this->enabled = ((MODULE_PAYMENT_INTERKASSA_STATUS == 'True') ? true : false);
//        $this->order_status = MODULE_PAYMENT_INTERKASSA_STATUS;
    if ((int)MODULE_PAYMENT_INTERKASSA_STATUS > 0) {
      $this->order_status = MODULE_PAYMENT_INTERKASSA_STATUS;
    }
    $this->ik_co_id = MODULE_PAYMENT_INTERKASSA_CO_ID;
    $this->s_key = MODULE_PAYMENT_INTERKASSA_S_KEY;
    $this->t_key = MODULE_PAYMENT_INTERKASSA_T_KEY;
  }
  function update_status()
  {
    global $order;
    if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_IK_ZONE > 0) )
    {
      $check_flag = false;
      $check_query = os_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_IK_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
      while ($check = os_db_fetch_array($check_query))
      {
        if ($check['zone_id'] < 1)
        {
          $check_flag = true;
          break;
        }
        elseif ($check['zone_id'] == $order->billing['zone_id'])
        {
          $check_flag = true;
          break;
        }
      }
      if ($check_flag == false) $this->enabled = false;
    }
  }
  function javascript_validation()
  {
    return false;
  }
  function selection()
  {
    if (isset($_SESSION[$this->name]))
    {
      $order_id = substr($_SESSION[$this->name], strpos($_SESSION[$this->name], '-')+1);
      $check_query = os_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');
      if (os_db_num_rows($check_query) < 1)
      {
        os_db_query('DELETE FROM '.TABLE_ORDERS.' WHERE orders_id = "'.(int)$order_id.'"');
        os_db_query('DELETE FROM '.TABLE_ORDERS_TOTAL.' WHERE orders_id = "'.(int)$order_id.'"');
        os_db_query('DELETE FROM '.TABLE_ORDERS_STATUS_HISTORY.' WHERE orders_id = "'.(int)$order_id.'"');
        os_db_query('DELETE FROM '.TABLE_ORDERS_PRODUCTS.' WHERE orders_id = "'.(int)$order_id.'"');
        os_db_query('DELETE FROM '.TABLE_ORDERS_PRODUCTS_ATTRIBUTES.' WHERE orders_id = "'.(int)$order_id.'"');
        os_db_query('DELETE FROM '.TABLE_ORDERS_PRODUCTS_DOWNLOAD.' WHERE orders_id = "'.(int)$order_id.'"');
        unset($_SESSION[$this->name]);
      }
    }
    if (os_not_null($this->icon)) $icon = os_image(http_path('payment').$this->code.'/'.$this->icon, $this->title);
    return array(
      'id' => $this->code,
      'icon' => $this->icon,
      'module' => $this->title,
      'title'=>$this->title
    );
  }
  function pre_confirmation_check()
  {
    global $cartID, $cart;
    if (empty($_SESSION['cart']->cartID)) $_SESSION['cartID'] = $_SESSION['cart']->cartID = $_SESSION['cart']->generate_cart_id();
    if (!isset($_SESSION['cartID'])) $_SESSION['cartID'] = $_SESSION['cart']->generate_cart_id();
  }
  function confirmation() {
    global $cartID, $cart_ik_id, $customer_id, $languages_id, $order, $order_total_modules;
    if (isset($_SESSION['cartID'])) {
      $insert_order = false;
      if (isset($_SESSION[$this->name])) {
        $order_id = substr($_SESSION[$this->name], strpos($_SESSION[$this->name], '-'));
        $curr_check = os_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
        $curr = os_db_fetch_array($curr_check);
        if (
          ($curr['currency'] != $order->info['currency'])
          ||
          ($cartID != substr($cart_ik_id, 0, strlen($cartID)))
        ){
          $check_query = os_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');
          if (os_db_num_rows($check_query) < 1) {
            os_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
            os_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
            os_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
            os_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
            os_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
            os_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
          }
          $insert_order = true;
        }
      } else $insert_order = true;
      if ($insert_order == true) {
        $order_totals = array();
        if (is_array($order_total_modules->modules)) {
          reset($order_total_modules->modules);
          while (list(, $value) = each($order_total_modules->modules)) {
            $class = substr($value, 0, strrpos($value, '.'));
            if($GLOBALS[$class]->enabled)
              for($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++)
                if
                (
                  os_not_null($GLOBALS[$class]->output[$i]['title'])
                  &&
                  os_not_null($GLOBALS[$class]->output[$i]['text'])
                ) $order_totals[] = array(
                    'code' => $GLOBALS[$class]->code,
                    'title' => $GLOBALS[$class]->output[$i]['title'],
                    'text' => $GLOBALS[$class]->output[$i]['text'],
                    'value' => $GLOBALS[$class]->output[$i]['value'],
                    'sort_order' => $GLOBALS[$class]->sort_order
                  );
          }
        }
        if ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == 1)
          $discount = $_SESSION['customers_status']['customers_status_ot_discount'];
        else $discount = '0.00';
        if ($_SERVER["HTTP_X_FORWARDED_FOR"])
          $customers_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        else $customers_ip = $_SERVER["REMOTE_ADDR"];
        $sql_data_array = array(
          'customers_id' => $_SESSION['customer_id'],
          'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
          'customers_cid' => $order->customer['csID'],
          'customers_vat_id' => $_SESSION['customer_vat_id'],
          'customers_company' => $order->customer['company'],
          'customers_status' => $_SESSION['customers_status']['customers_status_id'],
          'customers_status_name' => $_SESSION['customers_status']['customers_status_name'],
          'customers_status_image' => $_SESSION['customers_status']['customers_status_image'],
          'customers_status_discount' => $discount,
          'customers_street_address' => $order->customer['street_address'],
          'customers_suburb' => $order->customer['suburb'],
          'customers_city' => $order->customer['city'],
          'customers_postcode' => $order->customer['postcode'],
          'customers_state' => $order->customer['state'],
          'customers_country' => $order->customer['country']['title'],
          'customers_telephone' => $order->customer['telephone'],
          'customers_email_address' => $order->customer['email_address'],
          'customers_address_format_id' => $order->customer['format_id'],
          'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
          'delivery_company' => $order->delivery['company'],
          'delivery_street_address' => $order->delivery['street_address'],
          'delivery_suburb' => $order->delivery['suburb'],
          'delivery_city' => $order->delivery['city'],
          'delivery_postcode' => $order->delivery['postcode'],
          'delivery_state' => $order->delivery['state'],
          'delivery_country' => $order->delivery['country']['title'],
          'delivery_address_format_id' => $order->delivery['format_id'],
          'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
          'billing_company' => $order->billing['company'],
          'billing_street_address' => $order->billing['street_address'],
          'billing_suburb' => $order->billing['suburb'],
          'billing_city' => $order->billing['city'],
          'billing_postcode' => $order->billing['postcode'],
          'billing_state' => $order->billing['state'],
          'billing_country' => $order->billing['country']['title'],
          'billing_address_format_id' => $order->billing['format_id'],
          'payment_method' => $order->info['payment_method'],
          'payment_class' => $order->info['payment_class'],
          'shipping_method' => $order->info['shipping_method'],
          'shipping_class' => $order->info['shipping_class'],
          'language' => $_SESSION['language'],
          'customers_ip' => $customers_ip,
          'orig_reference' => $order->customer['orig_reference'],
          'login_reference' => $order->customer['login_reference'],
          'cc_type' => $order->info['cc_type'],
          'cc_owner' => $order->info['cc_owner'],
          'cc_number' => $order->info['cc_number'],
          'cc_expires' => $order->info['cc_expires'],
          'date_purchased' => 'now()',
          'orders_status' => $order->info['order_status'],
          'currency' => $order->info['currency'],
          'currency_value' => $order->info['currency_value']
        );
        os_db_perform(TABLE_ORDERS, $sql_data_array);
        $insert_id = os_db_insert_id();
        for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
          $sql_data_array = array(
            'orders_id' => $insert_id,
            'title' => $order_totals[$i]['title'],
            'text' => $order_totals[$i]['text'],
            'value' => $order_totals[$i]['value'],
            'class' => $order_totals[$i]['code'],
            'sort_order' => $order_totals[$i]['sort_order']
          );
          os_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
        }
        for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
          $sql_data_array = array(
            'orders_id' => $insert_id,
            'products_id' => os_get_prid($order->products[$i]['id']),
            'products_model' => $order->products[$i]['model'],
            'products_name' => $order->products[$i]['name'],
            'products_price' => $order->products[$i]['price'],
            'final_price' => $order->products[$i]['final_price'],
            'products_tax' => $order->products[$i]['tax'],
            'products_quantity' => $order->products[$i]['qty']
          );
          os_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
          $order_products_id = os_db_insert_id();
          $attributes_exist = '0';
          if (isset($order->products[$i]['attributes'])) {
            $attributes_exist = '1';
            for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
              if (DOWNLOAD_ENABLED == 'true') {
                $attributes_query = "SELECT
                  popt.products_options_name,poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                  on pa.products_attributes_id=pad.products_attributes_id
                where
                  pa.products_id = '" . $order->products[$i]['id'] . "'
                  and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                  and pa.options_id = popt.products_options_id
                  and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                  and pa.options_values_id = poval.products_options_values_id
                  and popt.language_id = '" . $_SESSION['languages_id'] . "'
                  and poval.language_id = '" . $_SESSION['languages_id'] . "'";
                $attributes = os_db_query($attributes_query);
              } else {
                $attributes = os_db_query("SELECT
                  popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                where
                  pa.products_id = '" . $order->products[$i]['id'] . "'
                  and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                  and pa.options_id = popt.products_options_id
                  and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                  and pa.options_values_id = poval.products_options_values_id
                  and popt.language_id = '" . $_SESSION['languages_id'] . "'
                  and poval.language_id = '" . $_SESSION['languages_id'] . "'");
              }
              os_db_query("UPDATE ".TABLE_PRODUCTS_ATTRIBUTES." set
                attributes_stock=attributes_stock - '".$order->products[$i]['qty']."'
              where
                products_id='".$order->products[$i]['id']."'
                and options_values_id='".$order->products[$i]['attributes'][$j]['value_id']."'
                and options_id='".$order->products[$i]['attributes'][$j]['option_id']."'");
              $attributes_values = os_db_fetch_array($attributes);
              $sql_data_array = array(
                'orders_id' => $insert_id,
                'orders_products_id' => $order_products_id,
                'products_options' => $attributes_values['products_options_name'],
                'products_options_values' => $attributes_values['products_options_values_name'],
                'options_values_price' => $attributes_values['options_values_price'],
                'price_prefix' => $attributes_values['price_prefix']
              );
              os_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
              if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && os_not_null($attributes_values['products_attributes_filename'])) {
                $sql_data_array = array(
                  'orders_id' => $insert_id,
                  'orders_products_id' => $order_products_id,
                  'orders_products_filename' => $attributes_values['products_attributes_filename'],
                  'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                  'download_count' => $attributes_values['products_attributes_maxcount']
                );
                os_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
              }
            }
          }
        }
        $cart_ik_id = $cartID . '-' . $insert_id;
        $_SESSION[$this->name] = $cart_ik_id;
      }
    }
    return array('title' => MODULE_PAYMENT_INTERKASSA_TEXT_DESCRIPTION);
  }
  public function process_button(){
    global $currencies, $currency, $osPrice,$insert_id;
    $last_order_id = os_db_query("SELECT MAX(orders_id) AS max FROM " . TABLE_ORDERS);
    $ik_pm_no = os_db_fetch_array($last_order_id);
    $ik_pm_no = $ik_pm_no['max']++;
    $ik_am = number_format($this->order->info['total'], 2, '.', '');
    $ik_cur = $this->order->info['currency']=='RUR'?'RUB':$this->order->info['currency'];
    $ik_desc = '#'.$ik_pm_no;
    $ik_ia_u = 'http://' . htmlspecialchars($_SERVER['SERVER_NAME'], ENT_COMPAT, 'UTF-8').'/modules/payment/interkassa/validation.php';
    $ik_suc_u= 'http://' . htmlspecialchars($_SERVER['SERVER_NAME'], ENT_COMPAT, 'UTF-8').'/checkout_process.php';
    $ik_fal_u = 'http://' . htmlspecialchars($_SERVER['SERVER_NAME'], ENT_COMPAT, 'UTF-8').'/checkout_payment.php';
    $ik_pnd_u = 'http://' . htmlspecialchars($_SERVER['SERVER_NAME'], ENT_COMPAT, 'UTF-8').'/checkout_success.php';
    $arg = array(
      'ik_am' => number_format($ik_am, 2, ".", ""),
      'ik_pm_no' => $ik_pm_no,
      'ik_desc' => $ik_desc,
      'ik_cur' => $ik_cur,
      'ik_co_id' => $this->ik_co_id,
      'ik_ia_u' => $ik_ia_u,
      'ik_suc_u' => $ik_suc_u,
      'ik_fal_u' => $ik_fal_u,
      'ik_pnd_u' => $ik_pnd_u,
    );
    $formdata = $arg;
    $formdata['ik_sign'] = $this->SignCraft($arg);
    foreach ($formdata as $field => $value){
      $this->form .= os_draw_hidden_field($field,$value);
    }
    $sql_data_array = array(
      'orders_status' => 2,
    );
    os_db_perform(DB_PREFIX.'orders', $sql_data_array, 'update', "orders_id='".(int)$ik_pm_no."'");
//        $sql_data_arrax = array(
//            'orders_id' => (int)$ik_pm_no,
//            'orders_status_id' => (int)MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID,
//            'date_added' => 'now()',
//            'customer_notified' => '0',
//            'comments' => 'Покупатель выбрал Интеркассу'
//        );
//        os_db_perform(DB_PREFIX.'orders_status_history', $sql_data_arrax);
    $_SESSION['ik_pm_no'] = $ik_pm_no;

    $html = '<script>jQuery(document).ready(function($){$.noConflict()})</script><script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script><link rel="stylesheet" type="text/css" href="' . $this->ik_dir . 'assets/ik.css"><script type="text/javascript" src="' . $this->ik_dir . 'assets/ik.js"></script>';
    $html .= '<div class="ik_block">' .
      '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target=".ik_modal">Выбрать платежную систему</button>'.
      '<div class="modal fade ik_modal" tabindex="-1" role="dialog">' .
        '<div class="modal-dialog modal-lg" role="document">' .
          '<div class="modal-content" id="plans">' .
            '<div class="modal-body">' .
                  '<h1>1. Выберите удобный способ оплаты<br>2. Укажите валюту<br>3. Нажмите \'Оплатить\'</h1>' .
                  '<div class="row">';
        foreach($this->getIkPaymentSystems() as $ps=>$info) if($ps!='test'||($ps=='test'&&MODULE_PAYMENT_INTERKASSA_T_MODE=='True')) {
          $currencyQ = null;
          foreach($info['currency'] as $currency=>$currencyAlias)
            $currencyQ .= '<a class="btn btn-primary btn-sm notActive" data-toggle="fun"	data-title="'.$currencyAlias.'">'.$currency.'</a>';
          $html .=
'<div class="col-sm-3 text-center payment_system">'.
  '<div class="panel panel-warning panel-pricing">'.
    '<div class="panel-heading">'.
      '<img src="'.$this->ik_dir.'assets/paysystems/'.$ps.'.png" alt="'.$info['title'].'">'.
    '</div>'.
    '<div class="form-group">'.
      '<div class="input-group">'.
        '<div id="radioBtn" class="btn-group radioBtn">'.
          $currencyQ .
        '</div>'.
      '</div>'.
    '</div>'.
    '<div class="panel-footer">'.
      '<a class="btn btn-block btn-success ik-payment-confirmation" data-title="'.$ps.'" href="#">Оплатить с <br><strong>'.$info['title'].'</strong></a>'.
    '</div>'.
  '</div>'.
'</div>';
}
        $html .=  '</div>' .
                '</div>' .
              '</div>' .
            '</div>' .
          '</div>'
    ;
    $html .= '</div>';
    return $this->form.$html;
  }
  public function before_process()
  {
    return false;
  }
  public function after_process()
  {
    $order_id = $_SESSION['ik_pm_no']+1;
    os_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
    os_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
    os_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
    os_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
    os_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
    os_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
  }
  public function output_error()
  {
    return false;
  }
  public function check()
  {
    if (!isset($this->_check))
    {
      $check_query = os_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_INTERKASSA_STATUS'");
      $this->_check = os_db_num_rows($check_query);
    }
    return $this->_check;
  }
  public function install()
  {
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) values ('MODULE_PAYMENT_INTERKASSA_STATUS', 'True', '6', '1', 'os_cfg_select_option(array(\'True\', \'False\'), ', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_INTERKASSA_CO_ID', '', '6', '0', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_INTERKASSA_S_KEY', '', '6', '1', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) values ('MODULE_PAYMENT_INTERKASSA_T_MODE', 'True', '6', '1', 'os_cfg_select_option(array(\'True\', \'False\'), ', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_INTERKASSA_T_KEY', '', '6', '2', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_INTERKASSA_API_ID', '', '6', '3', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_INTERKASSA_API_KEY', '', '6', '4', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_INTERKASSA_SORT_ORDER', '1', '6', '9', now())");
    os_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID', '0', '6', '0', 'os_cfg_pull_down_order_statuses(', 'os_get_order_status_name', now())");
  }
  public function remove()
  {
    os_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }
  public function keys()
  {
    return array(
      'MODULE_PAYMENT_INTERKASSA_STATUS',
      'MODULE_PAYMENT_INTERKASSA_CO_ID',
      'MODULE_PAYMENT_INTERKASSA_S_KEY',
      'MODULE_PAYMENT_INTERKASSA_T_MODE',
      'MODULE_PAYMENT_INTERKASSA_T_KEY',
      'MODULE_PAYMENT_INTERKASSA_API_ID',
      'MODULE_PAYMENT_INTERKASSA_API_KEY',
      'MODULE_PAYMENT_INTERKASSA_SORT_ORDER',
      'MODULE_PAYMENT_INTERKASSA_ORDER_STATUS_ID'
    );
  }
  private function getIkPaymentSystems()
  {
    $json_data = json_decode(file_get_contents('https://api.interkassa.com/v1/paysystem-input-payway?checkoutId='.MODULE_PAYMENT_INTERKASSA_CO_ID,false,stream_context_create(array('http'=>array('method'=>"GET",'header'=>"Authorization: Basic ".base64_encode(MODULE_PAYMENT_INTERKASSA_API_ID.':'.MODULE_PAYMENT_INTERKASSA_API_KEY))))));
    // file_put_contents(__DIR__.'/gg.txt',$json_data->status,FILE_APPEND);
    if($json_data->status != 'error'){

      $payment_systems = array();
      foreach ($json_data->data as $ps => $info) {
        $payment_system = $info->ser;
        if (!array_key_exists($payment_system, $payment_systems)) {
          $payment_systems[$payment_system] = array();
          foreach ($info->name as $name) {
            if ($name->l == 'en') $payment_systems[$payment_system]['title'] = ucfirst($name->v);
            $payment_systems[$payment_system]['name'][$name->l] = $name->v;
          }
        }
        $payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;
      }
      return $payment_systems;
    }else echo '<strong style="color:red;">API connection error!<br>'.$json_data->message.'</strong>';
  }
  private function SignCraft($data)
  {
    if(!empty($data['ik_sign'])) unset($data['ik_sign']);
    $dataSet = array();
    foreach ($data as $key=>$value) {
      if(!preg_match('/ik_/', $key))continue;
      $dataSet[$key]=$value;
    }

    ksort($dataSet,SORT_STRING);
    array_push($dataSet,MODULE_PAYMENT_INTERKASSA_S_KEY);
    $arg = implode(':',$dataSet);
    $sign = base64_encode(md5($arg, true));

    return $sign;
  }
  private function FormGet($data)
  {
    $ch = curl_init('https://sci.interkassa.com/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }
  public static function Step_1($data){return json_encode(array('sign'=>interkassa::SignCraft($data)));}
  public static function Step_2($data){$data['ik_sign']=interkassa::SignCraft($data);return json_encode(interkassa::FormGet($data));}
}
