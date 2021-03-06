<?php
//dezend by  QQ:2172298892
define('IN_ECS', true);
require dirname(__FILE__) . '/includes/init.php';
require dirname(__FILE__) . '/plugins/aliyunoss/autoload.php';
include 'includes/cls_json.php';
$json = new JSON();
$res = array('err_msg' => '', 'err_no' => 0, 'result' => '');
$rootPath = ROOT_PATH;
$act = (isset($_REQUEST['act']) ? addslashes_deep($_REQUEST['act']) : 'upload');
$bucket = (isset($_REQUEST['bucket']) ? addslashes_deep($_REQUEST['bucket']) : '');
$keyid = (isset($_REQUEST['keyid']) ? addslashes_deep($_REQUEST['keyid']) : '');
$keysecret = (isset($_REQUEST['keysecret']) ? addslashes_deep($_REQUEST['keysecret']) : '');
$endpoint = (isset($_REQUEST['endpoint']) ? addslashes_deep($_REQUEST['endpoint']) : '');
$is_cname = (isset($_REQUEST['is_cname']) ? intval($_REQUEST['is_cname']) : 1);
$object = (isset($_REQUEST['object']) ? $_REQUEST['object'] : array());
$file = '';

if ($is_cname == 1) {
	$is_cname = true;
}
else {
	$is_cname = false;
}

$ossClient = new \OSS\OssClient($keyid, $keysecret, $endpoint, $is_cname);

if ($act == 'upload') {
	if (is_array($object)) {
		foreach ($object as $row) {
			if ($row) {
				$file = $rootPath . $row;
				$objects = $row;
				$ossClient->putObject($bucket, $objects, '{$row}');
				$ossClient->uploadFile($bucket, $objects, $file);
			}
		}
	}
	else {
		$file = $rootPath . $object;
		$ossClient->putObject($bucket, $object, '{$object}');
		$ossClient->uploadFile($bucket, $object, $file);
	}
}
else if ($act == 'del_file') {
	$ossClient->deleteObjects($bucket, $object);
}

exit($json->encode($object));

?>
