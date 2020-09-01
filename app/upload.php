<?php
require (realpath('../config/db.php'));

// $txt = file("list.txt");

// if (!empty($txt)){
// 	$list=[];

// 	for ($i=0; $i < count($txt); $i++) {
// 		$key = explode('|', $txt[$i]);
// 		array_push($list, $key);
// 	}

// 	for ($i=0; $i < count($list); $i++) {
// 		$keys = R::dispense('keys');
// 		$keys->code = $list[$i][0];
// 		$keys->name = $list[$i][1];
// 		$keys->price = $list[$i][2];
// 		$keys->quality = $list[$i][3];
// 		$keys->is_given = false;
// 		R::store($keys);
// 	}
// }

// R::exec('UPDATE keys SET is_given = ? WHERE is_given = ? AND quality = ?',[0,1,4]);

R::exec('ALTER TABLE "keys" ALTER COLUMN quality TYPE integer');