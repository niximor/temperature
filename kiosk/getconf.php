<?php

include "../config.php";

$link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);

$out = array();

$res = mysqli_query($link, "SELECT `key`, value FROM config");
while ($row = mysqli_fetch_array($res)) {
	$out[$row["key"]] = $row["value"];
}

mysqli_close($link);

header("Content-Type: application/json");
echo json_encode($out);

?>
