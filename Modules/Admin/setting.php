<?php
namespace Statistics\Modules\Admin;

function setting()
{
	$act = isset($_GET['act']) ? $_GET['act'] : 'home';
	$err_msg = $notice_msg = $suc_msg = $ip_list_str = '';
	switch ($act) {
		case 'save':
			if (empty($_POST['detect_port'])) {
				$err_msg = "探测端口不能为空";
				break;
			}
			$detect_port = (int) $_POST['detect_port'];
			
			if ($detect_port < 0 || $detect_port > 65535) {
				$err_msg = "探测端口不合法";
				break;
			}
			$suc_msg = "保存成功";
			\Config\Config::$ProviderPort = $detect_port;
			saveDetectPortToCache();
			break;
		default:
			$detect_port = \Config\Config::$ProviderPort;
	}
	
	include ST_ROOT . '/Views/Includes/header.tpl.php';
	include ST_ROOT . '/Views/Performance/setting.tpl.php';
	include ST_ROOT . '/Views/Includes/footer.tpl.php';
}

function saveDetectPortToCache()
{
	foreach (glob(ST_ROOT . '/Config/Cache/*detect_port.cache.php') as $php_file) {
		unlink($php_file);
	}
	file_put_contents(ST_ROOT . '/Config/Cache/' . time() . '.detect_port.cache.php', "<?php\n\\Config\\Config::\$ProviderPort=" . var_export(\Config\Config::$ProviderPort, true) . ';');
}
