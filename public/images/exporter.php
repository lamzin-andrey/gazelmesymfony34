<?php

die;

//Перекодировал так
include $_SERVER["DOCUMENT_ROOT"] . "/config.php";
set_time_limit(0);

mysql_query("SET NAMES utf8");

/*$cmd = "SELECT * FROM cities8 ORDER BY delta";

$res = mysql_query($cmd);

$cities = array();
while($row = mysql_fetch_array($res)) {
	$cities[] = $row;
}


foreach($cities as $city) {
	$cmd = "INSERT INTO 
		cities (city_name, region, country, delta, is_moderate, id)
		VALUES('{CITY}', {REG}, {CO}, {D}, 1, {ID})
	";
	$cmd2 = str_replace(array('{CITY}', '{REG}', '{CO}', '{D}', '{ID}'), array( $city['city_name'], $city['region'], $city['country'], $city["delta"], $city['id'] ), $cmd);
	mysql_query($cmd2);
}*/



/*$cmd = "SELECT * FROM countries8  ORDER BY delta";

$res = mysql_query($cmd);

$cities = array();
while($row = mysql_fetch_array($res)) {
	$cities[] = $row;
}


foreach($cities as $city) {
	$cmd = "INSERT INTO 
		countries (id, country_name, delta, is_moderate)
		VALUES({ID}, '{CITY}', {D}, 1)
	";
	$cmd2 = str_replace(array('{CITY}', '{D}', '{ID}'), array( $city['country_name'], $city['delta'], $city["id"]), $cmd);
	mysql_query($cmd2);
}*/

//mysql_query("SET NAMES CP1251");
$cmd = "SELECT * FROM regions8  ORDER BY delta";

$res = mysql_query($cmd);

$cities = array();
while($row = mysql_fetch_array($res)) {
	$cities[] = $row;
}
foreach($cities as $city) {
	$cmd = "INSERT INTO 
		regions (region_name, is_city, country, delta, is_moderate, id)
		VALUES('{CITY}', {REG}, {CO}, {D}, 1, {ID})
	";
	//$city['region_name'] = iconv("WINDOWS-1251", "UTF-8", $city['region_name']);
	//$city['region_name'] = str_replace('обл.', 'область', $city['region_name']);
	
	$cmd2 = str_replace(array('{CITY}', '{REG}', '{CO}', '{D}', '{ID}'), array( $city['region_name'], $city['is_city'], $city['country'], $city['delta'], $city['id']), $cmd);
	mysql_query($cmd2);
}

print 'aga';