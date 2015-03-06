<?php

chdir(dirname(__FILE__));
include "../config.php";

$devices = file("/sys/bus/w1/devices/w1_bus_master1/w1_master_slaves");
foreach ($devices as $device) {
	$device = trim($device);

	$sensor = "/sys/bus/w1/devices/".$device."/w1_slave";
	$f = file($sensor);

	if (strpos($f[0], "YES") !== false) {
		// Valid CRC, got temp.
		$off = strpos($f[1], "t=");
		$temp = substr($f[1], $off + 2);
		
		$link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB) or die(mysqli_connect_error());
		mysqli_query($link, "INSERT INTO `history` (`date`, `value`, `sensor`) SELECT UTC_TIMESTAMP(), ".(int)$temp.", id FROM `sensors` WHERE device = '".$device."'") or die(mysqli_error($link));
		mysqli_close($link);
		echo $device.": OK\n";
	} else {
		echo $device.": Bad CRC.";
	}
}

?>
