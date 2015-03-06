<?php

include "../config.php";

$set_id = (isset($_REQUEST["set"]))?(int)$_REQUEST["set"]:$DEFAULT_SET_ID;

$link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);
mysqli_query($link, "SET NAMES utf8");

$data = array();

$res = mysqli_query($link, "SELECT s.id, s.name sensor_name, a.name value_name, a.value, UNIX_TIMESTAMP(a.date) date FROM sensors_in_set ss JOIN sensors s ON (ss.sensor_id = s.id) JOIN aggregation a ON (s.id = a.sensor) WHERE ss.set_id = ".(int)$set_id." AND a.name IN ('".implode("','", array_keys($DASH_VALUES))."') ORDER BY s.name ASC, a.name ASC");

$data = array();

while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
	if (!isset($data[$row["id"]])) {
		$data[$row["id"]] = array(
			"id" => $row["id"],
			"name" => $row["sensor_name"],
			"values" => array()
		);
	}

	$values = &$data[$row["id"]]["values"];
	$values[] = array(
		"name" => $DASH_VALUES[$row["value_name"]],
		"value" => sprintf("%1.1f", round($row["value"] / 1000, 1)),
		"date" => date("j.n G:i", $row["date"])
	);
}

mysqli_close($link);

$order = array_flip(array_values($DASH_VALUES));

function compare(&$a, &$b) {
	global $order;
	$res = $order[$a["name"]] - $order[$b["name"]];
	if ($res < 0) return -1;
	elseif ($res > 0) return 1;
	else return 0;
}

foreach ($data as &$row) {
	usort($row["values"], "compare");
}

header("Content-Type: application/json");
echo json_encode(array_values($data));
