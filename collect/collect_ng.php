<?php

include __DIR__."/../config.php";

$readings = array();

$devices = @file("/sys/bus/w1/devices/w1_bus_master1/w1_master_slaves");
foreach ((array)$devices as $device) {
    $device = trim($device);
    if (empty($device)) continue;

    $sensor = "/sys/bus/w1/devices/".$device."/w1_slave";
    if (!file_exists($sensor)) {
	    continue;
    }
    $f = file($sensor);

    if (strpos($f[0], "YES") !== false) {
        // Valid CRC, got temp.
        $off = strpos($f[1], "t=");
        $temp = substr($f[1], $off + 2);

        $readings[] = [
            "device" => $device,
            "value" => $temp / 1000.0,
            "date" => (new DateTime())->format("c")
        ];
    } else {
        echo $device.": Bad CRC.\n";
    }
}

function get_sqlite($filename) {
    $db = new SQLite3($filename);
    $db->exec("CREATE TABLE IF NOT EXISTS \"readings\" (\"device\" TEXT, \"value\" REAL, \"date\" TEXT)");

    return $db;
}

if (!empty($API_CACHE) && file_exists($API_CACHE)) {
    $db = get_sqlite($API_CACHE);

    $q = $db->query("SELECT * FROM \"readings\"");

    $anything = false;
    while ($a = $q->fetchArray(SQLITE3_ASSOC)) {
        $readings[] = $a;
        $anything = true;
    }

    if ($anything) {
        $db->exec("DELETE FROM \"readings\"");
    }

    $db->close();
}

$data = json_encode($readings);

$curl = curl_init($API_URL);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
	"X-Auth-Token: ".$API_AUTH_TOKEN,
	"Content-Type: application/json",
	"Content-Length ".strlen($data),
]);
$response = @curl_exec($curl);

if ($response !== false) {
    $json = json_decode($response);
    if ($json) {
        $response = $json;
    }
} else {
    $response = curl_error($curl)." (".curl_errno($curl).")";
}

curl_close($curl);

if (is_object($response) && isset($response->status) && $response->status == "OK") {
    echo "OK\n";
    echo "inserted=".$response->inserted."\n";
    echo "skipped=".$response->skipped."\n";
} else {
    fwrite(STDERR, "ERROR: ".json_encode($response)."\n");

    if (!empty($readings)) {
        $db = get_sqlite($API_CACHE);
        $db->exec("INSERT INTO \"readings\" (\"device\", \"value\", \"date\") VALUES ".implode(", ", array_map(function($item) use ($db) {
            return "('".$db->escapeString($item["device"])."', '".$db->escapeString($item["value"])."', '".$db->escapeString($item["date"])."')";
        }, $readings)));
        $db->close();
    }
    exit(1);
}
