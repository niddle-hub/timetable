<?php
require (realpath('../config/db.php'));
class Callback {
	private $secret_key;
	
	function __construct($secret_key) {
		$this -> secret_key = $secret_key;
	}
	
	public function get() {
		$response = json_decode(file_get_contents('php://input'), true);
		if (!$this -> check_hash($response)) return false;
		unset($response['hash']);
		return $response;
	}
	private function check_hash($params) {
		$hash = $params['hash'];
		unset($params['hash']);
		$params = $this -> get_hash_array($params);
		ksort($params);
		array_push($params, $this -> secret_key);
		$hash_str = join(',', $params);
		$sha256 = hash('sha256', $hash_str);
		return ($sha256 == $hash) ? true : false;
	}
	private function get_hash_array($params, $indx = '') {
		$arr = [];
		if ($indx) $indx .= '/';
		foreach ($params as $key => $val) {
			if (is_array($val)) {
				$newarr = $this -> get_hash_array($val, $indx . $key);
				$arr = array_merge($newarr, $arr);
			}
			else {
				$arr[$indx . $key] = $val;
			}
		}
		return $arr;
	}
}

function send($id , $message, $token)
{
    $url = 'https://api.vk.com/method/messages.send';
    $params = array(
        'user_id' => $id,
        'message' => $message,
        'access_token' => $token,
        'v' => '5.81',
    );

    $result = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($params)
        )
    )));
}

$TOKEN = getenv("app_token"); // group token
$secret_key =  getenv('app_secret');
$code = getenv('app_code');
$userdata = R::getRow('SELECT * FROM app WHERE vkid = ? LIMIT 1', [$id]); //user data array

$callback = new Callback($secret_key);
$response = $callback -> get();
if ($response === false) exit('Invalid request hash.');

switch ($response['type']) {

	case 'new_donate':
		$donate = $response['donate'];
		if(!empty($userdata)){
			$users = R::load('app', $userdata['id']);
			$users->balance = $userdata['balance'] + $donate['amount'];
			$users->pays = $userdata['pays'] + 1;
			R::store($users);
			send($donate['user'], "Ваш баланс пополнен на: ".$donate['amount']." рублей!", $TOKEN);
		} else {
			send($donate['user'], "Вы пополнили баланс на: ".$donate['amount'].", но небыли зарегестрированны, свяжитесь с администратором!",$TOKEN)
		}
		echo '{"status": "ok"}';
	break;

}
