<?php

include "../config.php";

$sensor = (isset($_REQUEST["sensor"]))?(int)$_REQUEST["sensor"]:$DEFAULT_SENSOR_ID;

$period = (isset($_REQUEST["period"]))?(int)$_REQUEST["period"]:$DEFAULT_HISTORY_PERIOD;
if ($period <= 0) $period = 24;
if ($period > 24*365*2) $period = 24;

$link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);

$data = array();

// Select this year's stats
$res = mysqli_query($link, "SELECT UNIX_TIMESTAMP(`date`) AS `date`, ROUND(`value` / 1000, 1) AS `value` FROM `history` WHERE `date` >= DATE_ADD(NOW(), INTERVAL -".(int)$period." HOUR) AND sensor = ".(int)$sensor." ORDER BY `date` ASC");

while ($row = mysqli_fetch_array($res)) {
	$data[] = array(
		"date" => (int)$row["date"] + (date("I", $a["date"]) * 3600 + 3600),
		"value" => $row["value"]
	);
}

$old = array();

// Select last year's statistics
$q = mysqli_query($link, "SELECT UNIX_TIMESTAMP(`date`) AS `date`, ROUND(`value` / 1000, 1) AS `value` FROM `history` WHERE `date` BETWEEN DATE_ADD(DATE_ADD(NOW(), INTERVAL -".(int)$period." HOUR), INTERVAL -1 YEAR) AND DATE_ADD(NOW(), INTERVAL -1 YEAR) AND sensor = ".(int)$sensor." ORDER BY `date` ASC");
while ($a = mysqli_fetch_array($q)) {
	$old[] = array(
		"date" => (int)$a["date"] + (date("I", $a["date"]) * 3600 + 3600),
		"value" => $a["value"]
	);
}

mysqli_close($link);

header("content-type: application/json");
echo json_encode(array(
	"current" => $data,
	"lastyear" => $old
));

