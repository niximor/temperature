<?php

include "../config.php";

$link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);
mysqli_query($link, "SET NAMES utf8");

$sensor = (isset($_REQUEST["sensor"]))?(int)$_REQUEST["sensor"]:$DEFAULT_SENSOR_ID;

$out = array();

$res = mysqli_query($link, "SELECT name, ROUND(value / 1000, 1) value, UNIX_TIMESTAMP(date) date FROM aggregation WHERE sensor = ".(int)$sensor);

while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
	$out[$row["name"]] = array(
		"value" => $row["value"],
		"date" => date("j.n.Y G:i", $row["date"])
	);
}

$res = mysqli_query($link, "SELECT name FROM sensors WHERE id = ".(int)$sensor);
if ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
	$out["sensor"] = $row["name"];
}

mysqli_close($link);

header("Content-Type: application/json");
echo json_encode($out);

