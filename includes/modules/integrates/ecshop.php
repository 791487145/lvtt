<?php
//zend  QQ:2172298892
if (!defined('IN_ECS')) {
	exit('Hacking attempt');
}

if (isset($set_modules) && ($set_modules == true)) {
	$i = (isset($modules) ? count($modules) : 0);
	$modules[$i]['code'] = 'ecshop';
	$modules[$i]['name'] = 'ECSHOP';
	$modules[$i]['version'] = '2.0';
	$modules[$i]['author'] = 'ECMOBAN R&D TEAM';
	$modules[$i]['website'] = 'http://www.ecmoban.com';
	return NULL;
}

require_once ROOT_PATH . 'includes/modules/integrates/integrate.php';
class ecshop extends integrate
{
	public $is_ecshop = 1;

	public function __construct($cfg)
	{
		$this->ecshop($cfg);
	}

	public function ecshop($cfg)
	{
		parent::integrate(array());
		$this->user_table = 'users';
		$this->field_id = 'user_id';
		$this->ec_salt = 'ec_salt';
		$this->field_name = 'user_name';
		$this->field_pass = 'password';
		$this->field_email = 'email';
		$this->field_phone = 'mobile_phone';
		$this->field_gender = 'sex';
		$this->field_bday = 'birthday';
		$this->field_reg_date = 'reg_time';
		$this->need_sync = false;
		$this->is_ecshop = 1;
	}

	public function check_user($username, $password = '')
	{
		if ($this->charset != 'UTF8') {
			$post_username = ecs_iconv('UTF8', $this->charset, $username);
		}
		else {
			$post_username = $username;
		}

		$sql = 'SELECT count(*) ' . ' FROM ' . $this->table($this->user_table) . ' WHERE user_name=\'' . $post_username . '\'';

		if ($this->db->getOne($sql)) {
			$login_name = 'user_name=\'' . $post_username . '\'';
		}
		else {
			$strlen = strlen($username);

			if (preg_match('/[^\\d-., ]/', $username)) {
				$a = '/([a-z0-9]*[-_\\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)(\\.[a-z]*)/i';

				if (preg_match($a, $username)) {
					$login_name = 'email=\'' . $post_username . '\'';
				}
				else {
					$login_name = 'user_name=\'' . $post_username . '\'';
				}
			}
			else if ($strlen != 11) {
				$a = '/([a-z0-9]*[-_\\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)(\\.[a-z]*)/i';

				if (preg_match($a, $username)) {
					$login_name = 'email=\'' . $post_username . '\'';
				}
				else {
					$login_name = 'user_name=\'' . $post_username . '\'';
				}
			}
			else {
				$login_name = 'mobile_phone' . '=\'' . $post_username . '\'';
			}
		}

		$sql = 'SELECT user_id, password, salt,ec_salt ' . ' FROM ' . $this->table($this->user_table) . ' WHERE ' . $login_name;
		$row = $this->db->getRow($sql);
		$ec_salt = $row['ec_salt'];

		if (empty($row)) {
			return 0;
		}

		if (empty($row['salt'])) {
			if (!empty($password) && ($row['password'] != $this->compile_password(array('password' => $password, 'ec_salt' => $ec_salt)))) {
				return 0;
			}
			else {
				if (empty($ec_salt)) {
					$ec_salt = rand(1, 9999);
					$new_password = md5(md5($password) . $ec_salt);
					$sql = 'UPDATE ' . $this->table($this->user_table) . 'SET password= \'' . $new_password . '\',ec_salt=\'' . $ec_salt . '\'' . ' WHERE user_name=\'' . $post_username . '\'';
					$this->db->query($sql);
				}

				return $row['user_id'];
			}
		}
		else {
			$encrypt_type = substr($row['salt'], 0, 1);
			$encrypt_salt = substr($row['salt'], 1);
			$encrypt_password = '';

			switch ($encrypt_type) {
			case ENCRYPT_ZC:
				$encrypt_password = md5($encrypt_salt . $password);
				break;

			case ENCRYPT_UC:
				$encrypt_password = md5(md5($password) . $encrypt_salt);
				break;

			default:
				$encrypt_password = '';
			}

			if (!empty($password) && ($row['password'] != $encrypt_password)) {
				return 0;
			}

			$sql = 'UPDATE ' . $this->table($this->user_table) . ' SET password = \'' . $this->compile_password(array('password' => $password)) . '\', salt=\'\'' . ' WHERE user_id = \'' . $row['user_id'] . '\'';
			$this->db->query($sql);
			return $row['user_id'];
		}
	}
}

?>
