<?php

include 'ExcelParser.php';
include 'redbeanphp/connect.php';
use DigitalStar\vk_api\vk_api;

$ExcelParser = new ExcelParser("documents/schedule.xlsx");

//-- PARAMS --//

$TOKEN = getenv("TOKEN"); // group token
$CONFIRM = getenv("CONFIRM"); // confirmation key
$VERSION = "5.81"; // api version
date_default_timezone_set('Asia/Yekaterinburg'); // timezone

//-- vk connection --//

$vk = vk_api::create($TOKEN, $VERSION)->setConfirm($CONFIRM);
$vk->debug();
$data = $vk->initVars($id, $message, $payload, $user_id, $type); //инициализация переменных

//-- TIME VARS --//

$today=date('d.m.Y');
$tomorrow = date("d.m.Y", strtotime("+1 day", strtotime($today)));

//--PAYLOADS --//

$B_BACK = $vk->buttonText('🌐 На главную', 'red', ['command' => 'back']);
$B_NEWS = $vk->buttonText('&#128240; Новости', 'white', ['command' => 'news']);
$A_NOTIF = $vk->buttonText('&#128276; Подписаться на новостную рассылку', 'green', ['command' => 'a_notif']);
$D_NOTIF = $vk->buttonText('&#128277; Отписаться от новостной рассылки', 'red', ['command' => 'd_notif']);
$B_TABLE = $vk->buttonText('&#128203; Расписание', 'blue', ['command' => 'table']);
$B_GROUP = $vk->buttonText('&#128101; Группа', 'green', ['command' => 'group']);
$B_TODAY = $vk->buttonText('⚪ Сегодня', 'green', ['command' => 'today']);
$B_TOMORROW = $vk->buttonText('⚫ Завтра', 'blue', ['command' => 'tomorrow']);
$B_DATE = $vk->buttonText('&#128197; Дата', 'white', ['command' => 'date']);
$B_CANCEL = $vk->buttonText('&#11013; Назад', 'red', ['command' => 'cancel']);

//-- Event type "message_new" --//

if($type == 'message_new'){
	(isset($payload)) ? $payload = $payload['command'] : $payload = null;
	if($payload=='start' or $message=="Начать"){
		$users = R::dispense('users');
		$users->vkid=$id;
		$users->group="ИСИТ-1701";
		$users->waitdate=false;
		$users->waitgroup=false;
		$users->notification=false;
		if(R::count('users',"vkid = $id")==0){
			R::exec("SELECT pg_catalog.setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users)+1)"); // reset postgres primary key sequence
			R::store($users);
			$vk->sendButton($id, "&#128075; Привет, %a_full% ! Я Расписание-Бот.\n&#128293; Тут ты можешь узнать расписание для своей группы, а также свежие новости со всего универа. Обязательно подпишись на новостную рассылку в разделе новости !\n&#128071; А теперь тыкай на кнопки и смотри что я могу!", [[$B_TABLE, $B_NEWS, $B_GROUP]]);
		} else {
			$vk->sendButton($id, "&#127760;Что интересует ?", [[$B_TABLE, $B_NEWS, $B_GROUP]]);
		}
	}

	$userdata = R::getRow('SELECT * FROM users WHERE vkid = ? LIMIT 1', [$id]); //user data array

	if($payload=='back'){
		$users = R::load('users', $userdata['id']);
		$users->waitgroup = false;
		R::store($users);
		$vk->sendButton($id, "&#127760;Что интересует ?", [[$B_TABLE, $B_NEWS, $B_GROUP]]);
	}

	if($payload=='cancel'){
		$users = R::load('users', $userdata['id']);
		$users->waitdate = false;
		R::store($users);
		$vk->sendButton($id,"Меню",[[$B_TODAY, $B_TOMORROW, $B_DATE],[$B_BACK]]);
	}

	if($payload=='table'){
		$vk->sendButton($id, "🔸".$userdata['group'], [[$B_TODAY, $B_TOMORROW, $B_DATE],[$B_BACK]]);
	}

	if($payload=='news'){
		if(!$userdata['notification']){
			$vk->sendButton($id,"Новости на 1&#8419; канале",[[$A_NOTIF],[$B_BACK]]);
		}else{
			$vk->sendButton($id,"Новости на 1&#8419; канале",[[$D_NOTIF],[$B_BACK]]);
		}
	}

	if($payload=='a_notif'){
		$users = R::load('users', $userdata['id']);
		$users->notification = true;
		R::store($users);
		$vk->sendButton($id,"&#9989; Вы успешно подписались на рассылку!",[[$D_NOTIF],[$B_BACK]]);
	}

	if($payload=='d_notif'){
		$users = R::load('users', $userdata['id']);
		$users->notification = false;
		R::store($users);
		$vk->sendButton($id,"&#10071; Вы больше не будете получать новости. И вкусяншки тоже!",[[$A_NOTIF],[$B_BACK]]);
	}

	if($payload=='group'){
		$users = R::load('users', $userdata['id']);
		$users->waitgroup = true;
		R::store($users);
		$groups=$ExcelParser->GetGroupsList();
		$vk->sendButton($id,"&#9999; Напиши свою группу!\n&#128203; Список доступных групп:\n".implode(PHP_EOL, $groups),[[$B_BACK]]);
	}	

	if($payload=='today' or mb_strtolower($message)=="сегодня"){
		$Timetable = $ExcelParser->GetTimetable($userdata['group'], $today);
		$str="\nГруппа: ".$userdata['group'];
		$vk->reply($Timetable.str_repeat(".", strlen($str)).$str);
	}

	if($payload=='tomorrow' or mb_strtolower($message)=="завтра"){
		$Timetable = $ExcelParser->GetTimetable($userdata['group'], $tomorrow);
		$str="\nГруппа: ".$userdata['group'];
		$vk->reply($Timetable.str_repeat(".", strlen($str)).$str);
	}

	if($payload=='date'){
		$users = R::load('users', $userdata['id']);
		$users->waitdate = true;
		R::store($users);
		$vk->sendButton($id,"Введи дату &#128197;\n".$ExcelParser->GetDates(),[[$B_CANCEL]]);
	}

	if($userdata['waitdate'] and $payload!='cancel'){
		$Timetable = $ExcelParser->GetTimetable($userdata['group'] ,$message);
		$str="\nГруппа: ".$userdata['group'];
		$vk->reply($Timetable.str_repeat(".", strlen($str)).$str);
	}

	if($userdata['waitgroup'] and $payload!='back'){
		$message=mb_strtoupper($message);
		$GroupsList=$ExcelParser->GetGroupsList();
		if(in_array("🔹".$message, $GroupsList)){
			$users = R::load('users', $userdata['id']);
			$users->group = $message;
			$users->waitgroup = false;
			R::store($users);
			$vk->sendButton($id,"Расписание для группы🔸".$message." подготовлено!",[[$B_TABLE, $B_NEWS, $B_GROUP]]);
		} else{
			$vk->reply("❌Группа ".$message." не найдена!");
		}
	}

	if ($message=="!upload" and $id==113769623) {
		$attachments = $data->object->attachments;
		if(!empty($attachments)){
			switch ($attachments[0]->type) {
				case 'doc':
					if ($data->object->attachments[0]->doc->ext=="xlsx"){
						if($data->object->attachments[0]->doc->size < 100000){
							$url = $data->object->attachments[0]->doc->url;
							$vk->reply("Файл моэно загрузить по адресу:\n".$url);
						}
						else $vk->reply("Этот документ very big size");
					}
					else $vk->reply("Этот тип документа какой то trash");
				break;
				
				default:
					$vk->reply("Этот тип вложения какой то shit");
				break;
			}
		}
		else $vk->reply("Нужно прикрепить document");
	}

	//-- NUKE YOURSELF --//

	if($message=="!kickme"){
		R::exec('DELETE FROM users WHERE vkid = ?',[$id]);
		$vk->reply("Вы дезинтегрированы, прощайте...");
	}
}

//-- Event type "group_join" --//

if($type == 'group_join'){
	$event_id = $data->object->user_id;
	$vk->sendMessage($event_id, "🔥 +100 к удаче на экзамене за подписку!");
}

//-- Event type "group_leave" --//

if($type == 'group_leave'){
	$event_id = $data->object->user_id;
	$vk->sendMessage($event_id, "Уже уходите ? &#128560; Будем ждать вас снова!");
}