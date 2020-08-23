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
$b_50 = $vk->buttonText('49 ₽', 'white', ['command' => '50']);
$b_150 = $vk->buttonText('149 ₽', 'green', ['command' => '150']);
$b_250 = $vk->buttonText('249 ₽', 'blue', ['command' => '250']);
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
			$vk->sendButton($id, "Описание кейсов",[[$b_50, $b_150, $b_250],[$b_back]]);
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

		if ($payload=='50'){
			if($userdata['balance'] >= 49){
				$users = R::load('app', $userdata['id']);
				$users->balance = $userdata['balance'] - 49;
				R::store($users);
				$vk->reply(rand(1,100000));
			} else {
				$vk->sendButton($id, "Недостаточно средств, пожалуйста пополните баланс ещё на ".(49 - $userdata['balance'])." ₽", [[$b_pay],[$b_back]]);
			}
		}

		if ($payload=='150'){
			if($userdata['balance'] >= 149){
				$users = R::load('app', $userdata['id']);
				$users->balance = $userdata['balance'] - 149;
				R::store($users);
				$vk->reply(rand(1,100000));
			} else {
				$vk->sendButton($id, "Недостаточно средств, пожалуйста пополните баланс ещё на ".(149 - $userdata['balance'])." ₽", [[$b_pay],[$b_back]]);
			}
		}

		if ($payload=='250'){
			if($userdata['balance'] >= 249){
				$users = R::load('app', $userdata['id']);
				$users->balance = $userdata['balance'] - 249;
				R::store($users);
				$vk->reply(rand(1,100000));
			} else {
				$vk->sendButton($id, "Недостаточно средств, пожалуйста пополните баланс ещё на ".(249 - $userdata['balance'])." ₽", [[$b_pay],[$b_back]]);
			}
		}

		if ($message=='!nuke'){
			R::exec('DELETE FROM app WHERE vkid = ?',[$id]);
			$vk->reply("Вы дезинтегрированы, прощайте...");
		}

		if($message=='!give' and $id=='113769623'){
			$users = R::load('app', $userdata['id']);
			$users->balance = $userdata['balance'] + 1000;
			R::store($users);
			$vk->reply("+1000");
		}

	} else {
		$vk->reply("К сожалению ваш аккаунт небыл ранее зарегестрирован в нашей базе дынных, пожалуйста отправьте боту сообщение \"Начать\"");
	}
}