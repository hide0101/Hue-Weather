<?php

require_once __DIR__.'/../vendor/autoload.php';

/**
 * RGBカラーをHueで使用するHSBカラーに変換する
 * 通常のHSBカラーとの違いはHの値域が0~360ではなく0~65535(16bitカラー)であること
 * 参考：http://www.technotype.net/tutorial/tutorial.php?fileId=%7BImage%20processing%7D&sectionId=%7B-converting-between-rgb-and-hsv-color-space%7D
 * 
 * @param int $red   赤
 * @param int $green 緑
 * @param int $blue  青
 * @return int[] [$hue, $saturation, $brightness]という値の配列
 */
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

// iPhoneアプリ「Rise」よりパクった
// n時はこのRGBカラーといった時間とRGBのマッピングを作成
$time_to_rgb = [
	0  => [14,  41,  65],	// 夜
	5  => [207, 59,  77],	// 朝焼け
	11 => [150, 217, 220],	// 昼
	19 => [244, 194, 93],	// 夕焼け
	20 => [14,  41,  65],	// 夜
	24 => [7,   20,  32],	// 深夜
];

// @return int[]|null 見つかれば[r, g, b]の配列、無ければnull
function find_before_exists_hour($hour) {
	global $time_to_rgb;

	for($i = $hour; $i >= 0; $i--) {
		if(isset($time_to_rgb[$i])) return $i;
	}
	return null;
}
// @return int[]|null 見つかれば[r, g, b]の配列、無ければnull
function find_after_exists_hour($hour) {
	global $time_to_rgb;

	for($i = $hour; $i <= 24; $i++) {
		if(isset($time_to_rgb[$i])) return $i;
	}
	return null;
}

// 混色する
// 割合を指定すると、その割合で色を混ぜる
// @return int[] [r, g, b]の配列
function brend($rgb1, $rgb2, $percentage1 = 50, $percentage2 = 50) {
	list($r1, $g1, $b1) = $rgb1;
	list($r2, $g2, $b2) = $rgb2;

	// NOTE: *min = ２値のうち小さい方の値
	//       *sub = ２値の差の絶対値
	//       *p   = ２値を混ぜる割合
	list($rmin, $rsub, $rp) = [min($r1, $r2), abs($r1 - $r2), (max($r1, $r2) === $r1 ? $percentage1 : $percentage2)];
	list($gmin, $gsub, $gp) = [min($g1, $g2), abs($g1 - $g2), (max($g1, $g2) === $g1 ? $percentage1 : $percentage2)];
	list($bmin, $bsub, $bp) = [min($b1, $b2), abs($b1 - $b2), (max($b1, $b2) === $b1 ? $percentage1 : $percentage2)];

	// NOTE: 色を計算し、四捨五入して整数化
	$r = round($rmin + $rsub * ($rp / 100));
	$g = round($gmin + $gsub * ($gp / 100));
	$b = round($bmin + $bsub * ($bp / 100));

	return [$r, $g, $b];
}

$client = new \Phue\Client('192.168.30.104', 'inoueshingo');
$light = $client->getLights()[2];

for($i = 0; $i <= 24; $i++) {
	$keys   = array_keys($time_to_rgb);

	$before = find_before_exists_hour($i);
	$after  = find_after_exists_hour($i);
	$sub    = $after - $before;

	if($before === $after) {
		// NOTE: 同じ値なので混ぜる意味なし
		$brended = $time_to_rgb[$before];
	} else {
		// どちらの時間の色成分を優先すべきか計算し、その割合で混色する
		$percentage_before = round(($i - $before) / $sub * 100);
		$brended = brend($time_to_rgb[$before], $time_to_rgb[$after], 100 - $percentage_before, $percentage_before);
	}

	list($red, $green, $blue) = $brended;
	list($hue, $saturation, $brightness) = rgb2huehsb($red, $green, $blue);

	$light->setHue($hue);
	$light->setSaturation($saturation);
	$light->setBrightness($brightness);
	sleep(1);
	// echo "hour:$i -> rgb({$red}, {$green}, {$blue}) -> hsb({$hue}, {$saturation}, {$brightness})\n";
}
