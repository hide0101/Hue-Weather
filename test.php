<?php

require_once __DIR__ . '/vendor/autoload.php';

function blink(\Phue\Light $light, $hue = null) {
	global $client;

	$count = 0;
	while($count++ < 5) {
		$command = new \Phue\Command\SetLightState($light);
		$command->brightness(53)->hue(213)->saturation(100)->transitionTime(1.2);

		// Send the command
		$client->sendCommand($command);

		sleep(1);

		$command = new \Phue\Command\SetLightState($light);
		$command->brightness(100)->hue(288)->saturation(100)->transitionTime(1.2);

		// Send the command
		$client->sendCommand($command);

		sleep(1);
	}

	// $light->setBrightness(20);
	// $light->setBrightness(100);
	// $light->setBrightness(20);
	// $light->setBrightness(100);
	// $light->setBrightness(20);
}

function rgb2huehsb($red, $green, $blue)
{
	$max = max([$red, $green, $blue]);
	$min = min([$red, $green, $blue]);
 
	if($max === $min) {
		$hue = 0;
	} elseif($max === $red) {
		$hue = (60 * ($green - $blue) / ($max - $min) + 360) % 360;
	} elseif($max === $green) {
		$hue = (60 * ($blue - $red) / ($max - $min) + 120) % 360;
	} elseif($max === $blue) {
		$hue = (60 * ($red - $green) / ($max - $min) + 240) % 360;
	} else {
		throw new LogicException("想定しない値の組み合わせです:{$red},{$green},{$blue}");
	}
 
	// 0~360で一度計算して、0~65535に再構成
	// FIXME: 色の段階が荒い、未検証
	$hue        = round(($hue / 360) * 65535);
	$saturation = round(($max === 0) ? 0 : (255 * (($max - $min) / $max)));
	$brightness = round($max);
 
	return [$hue, $saturation, $brightness];
}

//今日は雨がふるか判定する
//0だったら晴れ
function isRain($today_weather){
	if(strpos($today_weather, "雨")){
		return 1;
	}else{
		return 0;
	}
}

//今日の最高気温によって分類
//1が最も寒く4が最も暑い
function isHot($temp){
	if($temp < 5){
		return 1;
	}elseif($temp < 15){
		return 2;
	}elseif($temp < 25){
		return 3;
	}else{
		return 4;
	}
}

//翌日の寒さの判定
function isCold($tomorrow_min_temp){
	if($tomorrow_min_temp <= 15){
		return 1;
	}else{
		return 0;
	}
}


$client = new \Phue\Client('192.168.40.49', 'inoueshingo');
$light1 = $client->getLights()[1];
$light2 = $client->getLights()[2];
$light3 = $client->getLights()[3];



$json = file_get_contents('http://weather.livedoor.com/forecast/webservice/json/v1?city=140020', true);
$obj = json_decode($json);
// var_dump($obj);
// print_r($obj->{'forecasts'});

$forecasts = $obj->{'forecasts'};
$today = $forecasts[0];
$tomorrow = $forecasts[1];

$today_weather = $today->{'telop'};
$today_max_temp = $today->{'temperature'}->{'max'}->{'celsius'};
$tomorrow_min_temp = $tomorrow->{'temperature'}->{'min'}->{'celsius'};

echo("今日の天気=>$today_weather"."\n");
echo("今日の最高気温=>$today_max_temp". "\n");
echo("明日のの最低気温=>$tomorrow_min_temp". "\n");

//雨なら(rain=1)青のライト、雨じゃなければ(rain=0)オレンジのライト
$rain = isRain($today_weather);
// $rain = 1;
if($rain == 0){
	// list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);
	list($hue, $saturation, $brightness) = rgb2huehsb(10,0,0);
	switchLight($light1, $hue, $saturation, $brightness);
}else{
	// list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);
	list($hue, $saturation, $brightness) = rgb2huehsb(0,0,2);
	switchLight($light1, $hue, $saturation, $brightness);
}

//今日の最高気温
$tm = isHot($today_max_temp);
// $tm =3;//各色の確認用
if($tm == 1){
	// list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);
	list($hue, $saturation, $brightness) = rgb2huehsb(0,0,1);
	switchLight($light2, $hue, $saturation, $brightness);
}elseif($tm == 2){
	// list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);

	list($hue, $saturation, $brightness) = rgb2huehsb(1,0,1);
	switchLight($light2, $hue, $saturation, $brightness);
}elseif($tm == 3){
	// list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);

	list($hue, $saturation, $brightness) = rgb2huehsb(0,1,0);
	switchLight($light2, $hue, $saturation, $brightness);
}elseif($tm == 4){
	// list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);

	list($hue, $saturation, $brightness) = rgb2huehsb(1,0,0);
	switchLight($light2, $hue, $saturation, $brightness);
}
echo("暑さの段階=$tm". "\n");


//お腹のライト
$status = isCold($tomorrow_min_temp);
echo "お腹の状態＝".$status. "\n";
if($status == 0){
	list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);
	switchLight($light3, $hue, $saturation, $brightness);
	// blink($light3);
}else{
	// list($hue, $saturation, $brightness) = rgb2huehsb(0,0,0);
	
	switchLight($light3, $hue, $saturation, $brightness);
	blink($light3);
}


//$lightに$clientのインスタンス
function switchLight($light, $hue, $saturation, $brightness){
	$light->setHue($hue);
	$light->setSaturation($saturation);
	$light->setBrightness($brightness);
}





// list($hue, $saturation, $brightness) = rgb2huehsb(2,0,0);
// $light->setHue($hue);
// $light->setSaturation($saturation);
// $light->setBrightness($brightness);

// blink($light);

