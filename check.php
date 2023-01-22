<?php

// authcookie_ から書く
$auth_cookie = 'authcookie_**********';

$uid_list = [
	'usr_1e27afa3-36af-48e0-b2d6-c086c43839a0',  // yukatayu
	'usr_**********',  //
	'usr_**********',  //
];

// -+-+-+-+-+-+-+-+-+-+- //
// Parameters

$sleep_batch = 300;
$sleep_user  =  20;
$webhook = 'https://discord.com/api/webhooks/1**********/_**********';
$max_error = 5;

// -+-+-+-+-+-+-+-+-+-+- //
// API

function notify($content){
	global $webhook;

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $webhook);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
		'content' => $content
	]));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, [
		"Content-Type: application/json",
	]);

	$res = curl_exec($curl);
	curl_close($curl);
	return $res;
}

function get($uid){
	global $auth_cookie;
	$url = "https://vrchat.com/api/1/users/{$uid}?apiKey=JlE5Jldo5Jibnk5O5hTx6XVqsJu4WJ26";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, [
		"Cookie: auth={$auth_cookie}",
		'Content-Type: application/json, text/plain, */*',
		'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 9_0_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13A404 Safari/601.1',
	]);
	$res = curl_exec($curl);
	curl_close($curl);
	return json_decode($res, true);
}

function render($user){
	// 参考: https://qiita.com/PruneMazui/items/8a023347772620025ad6
	if($user['state'] == 'online'){
		if($user['location'] == 'private'){
			// プラベ
			return "\e[38;5;208m● private\e[0m  {$user['displayName']}";
		}else{
			// 見える場所
			return "\e[38;5;76m● online\e[0m   {$user['displayName']}";
		}
	}else if($user['state'] == 'active'){
		// offline
		return "\e[38;5;247m● active\e[0m   {$user['displayName']}";
	}else{
		// offline
		return "\e[38;5;240m● offline\e[0m  {$user['displayName']}";
	}
}

$notified = [];
$status = [];
$error_count = 0;


// -+-+-+-+-+-+-+-+-+-+- //
// Main Loop

for(;;){
	$status = [];
	foreach($uid_list as $uid){
		$user = get($uid);
		if($user === null
		|| !isset($user['displayName'])
		|| !isset($user['state'])
		|| !isset($user['status'])
		|| !isset($user['location'])
		){
			$status[] = "\e[31mError\e[0m {$uid}";
			++$error_count;
		}else{
			$error_count = 0;
			$status[] = render($user);
			$online = ($user['state'] == 'online');
			if($online)
				if(!isset($notified[$uid]) || $notified[$uid] == false)
					notify("{$user['displayName']} is {$user['state']} ({$user['status']})\n> {$user['location']}");
			$notified[$uid] = $online;
		}
		sleep($sleep_user);
	}

	system('clear');
	$date = date('Y/m/d H:i:s');
	print("{$date}\n--------------------\n");
	foreach($status as $s)
		print("{$s}\n");
	print("--------------------\n");
	if($error_count >= $max_error)
		die("error_count ({$error_count}) exceeded $max_error (${max_error})\n");
	sleep($sleep_batch);
}

