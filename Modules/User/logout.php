<?php namespace Statistics\Modules\User;

function logout($module, $interface, $date, $start_time, $offset, $count)
{
	$response = \Core\Response::getInstance()->response();
	$session = \Core\Session::getInstance($response);
	$session->delete();
	include ST_ROOT . '/Views/login.tpl.php';
}
