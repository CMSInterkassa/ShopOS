<?php
if($_SERVER['REQUEST_METHOD']!='POST') die();
require('../../../../includes/top.php');
require('../interkassa.php');
switch ($_GET['nYg']) {
  case 'nYs':
    echo interkassa::Step_1($_POST);
    break;
  case 'nYa':
    echo interkassa::Step_2($_POST);
    break;
  default:
    die();
}
