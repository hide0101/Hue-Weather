<?php 
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

$today_weather = "曇時々雨";
if(strpos($today_weather, "雨")){
	echo "今日は雨";
}else{
	echo "今日は雨じゃない";
}

?>