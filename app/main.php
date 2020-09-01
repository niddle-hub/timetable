<?php
require_once (realpath('../config/vendor/autoload.php'));
require (realpath('../config/db.php'));
use DigitalStar\vk_api\vk_api;

//-- PARAMS --//
$TOKEN = getenv("app_token"); // group token
$CONFIRM = getenv("app_confirm"); // confirmation key
$VERSION = "5.81"; // api version

//-- vk connection --//
$vk = vk_api::create($TOKEN, $VERSION)->setConfirm($CONFIRM);
$vk->debug();
$data = $vk->initVars($id, $message, $payload, $user_id, $type);

//--PAYLOADS --//
$b_keys = $vk->buttonText('Открыть кейсы', 'green', ['command' => 'keys']); //
$b_me = $vk->buttonText('Мой кабинет', 'white', ['command' => 'me']); //
$b_help = $vk->buttonText('Поддержка', 'red', ['command' => 'help']); //
$b_29 = $vk->buttonText('29 ₽', 'white', ['command' => '29']);
$b_59 = $vk->buttonText('59 ₽', 'green', ['command' => '59']);
$b_99 = $vk->buttonText('99 ₽', 'blue', ['command' => '99']);
$b_wallet = $vk->buttonText('Кошелёк', 'blue', ['command' => 'wallet']); //
$b_stat = $vk->buttonText('Статистика', 'green', ['command' => 'stat']); //
$b_back = $vk->buttonText('Меню', 'red', ['command' => 'back']); //
$b_pay = $vk->buttonText('Пополнить баланс', 'green', ['command' => 'pay']); //

if($type == 'message_new'){
	(isset($payload)) ? $payload = $payload['command'] : $payload = null;
	if($payload == 'start' or mb_strtolower($message) == "начать"){
		$users = R::dispense('app');
		$users->vkid=$id;
		$users->balance=0;
		$users->pays=0; //пополнений
		$users->cases=0; //куплено кейсов
		if(R::count('app',"vkid = $id")==0){
			R::store($users);
			$vk->sendButton($id, "Привет, %a_full%, это приветсвие",[[$b_keys],[$b_me],[$b_help]]);
		} else {
			$vk->sendButton($id, "Привет, %a_full%, мы уже знакомы",[[$b_keys],[$b_me],[$b_help]]);
		}
	}

	$userdata = R::getRow('SELECT * FROM app WHERE vkid = ? LIMIT 1', [$id]); //user data array

	if (!empty($userdata)){

		if ($payload=='back'){
			$vk->sendButton($id, " Меню", [[$b_keys],[$b_me],[$b_help]]);
		}

		if($payload=='keys'){
			$vk->sendButton($id, "Описание кейсов",[[$b_29, $b_59, $b_99],[$b_back]]);
		}

		if($payload=='me'){
			$vk->sendButton($id, "Кабинет",[[$b_wallet],[$b_stat],[$b_back]]);
		}

		if ($payload=='wallet'){
			$vk->sendButton($id, "Ваш баланс: ".$userdata['balance']." ₽", [[$b_pay],[$b_back]]);
		}

		if ($payload=='pay'){
			$vk->reply("Перейдите по ссылке для пополнения баланса -> \nhttps://vk.com/public197838267?w=app6887721_-197838267");
		}

		if($payload=='help'){
			$vk->reply("Связаться с разработчиком -> \nВопросы рекламы -> \nПравила сообщества -> \nОтзывы покупателей ->");
		}

		if ($payload=='stat'){
			$vk->reply("ID: $id\nПополнений баланса: ". $userdata['pays']."\nКуплено кейсов: ". $userdata['cases']);
		}

		if ($payload=='29'){
			if($userdata['balance'] >= 29){
				$users = R::load('app', $userdata['id']);
				$users->balance = $userdata['balance'] - 29;
				$users->cases = $userdata['cases'] + 1;
				R::store($users);
				$vk->reply(rand(1,100000));
			} else {
				$vk->sendButton($id, "Недостаточно средств, пожалуйста пополните баланс ещё на ".(29 - $userdata['balance'])." ₽", [[$b_pay],[$b_back]]);
			}
		}

		if ($payload=='59'){
			if($userdata['balance'] >= 59){
				$users = R::load('app', $userdata['id']);
				$users->balance = $userdata['balance'] - 59;
				$users->cases = $userdata['cases'] + 1;
				R::store($users);
				$vk->reply(rand(1,100000));
			} else {
				$vk->sendButton($id, "Недостаточно средств, пожалуйста пополните баланс ещё на ".(59 - $userdata['balance'])." ₽", [[$b_pay],[$b_back]]);
			}
		}

		if ($payload=='99'){
			if($userdata['balance'] >= 99){
				$users = R::load('app', $userdata['id']);
				$users->balance = $userdata['balance'] - 99;
				$users->cases = $userdata['cases'] + 1;
				R::store($users);
				$vk->reply(rand(1,100000));
			} else {
				$vk->sendButton($id, "Недостаточно средств, пожалуйста пополните баланс ещё на ".(99 - $userdata['balance'])." ₽", [[$b_pay],[$b_back]]);
			}
		}

		if ($message=='!nuke'){
			R::exec('DELETE FROM app WHERE vkid = ?',[$id]);
			$vk->reply("Вы дезинтегрированы, прощайте...");
		}

		if($message=='!give' and $id=='113769623'){
			$users = R::load('app', $userdata['id']);
			$users->balance = $userdata['balance'] + 1000;
			$users->pays = $userdata['pays'] + 1;
			R::store($users);
			$vk->reply("+1000");
		}

		if($message=='!key'){
			if($userdata['balance'] >=1) {
				$key = R::getRow('SELECT * FROM keys WHERE quality = ? AND is_given = ? ORDER BY RANDOM() LIMIT 1',[4,0]);
				if (!empty($key)){
					R::exec('UPDATE keys SET is_given = ? WHERE code = ?',[1,$key['code']]);
					$users = R::load('app', $userdata['id']);
					$users->balance = $userdata['balance'] - 1;
					R::store($users);
					$vk->reply("Ваш ключ: ".$key['name']."\n".$key['code']."\nЦена в steam ".$key['price']);
				} else{
					$vk->reply("Недостаточно ключей в этой категории");
				}
			} else {
				$vk->sendButton($id, "Недостаточно средств, пожалуйста пополните баланс ещё на 1 ₽", [[$b_pay],[$b_back]]);
			}
		}

	} else {
		$vk->reply("К сожалению ваш аккаунт небыл ранее зарегестрирован в нашей базе дынных, пожалуйста отправьте боту сообщение \"Начать\"");
	}
}