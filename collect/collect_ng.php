<?php

include __DIR__."/../config.php";

$readings = array();

$devices = @file("/sys/bus/w1/devices/w1_bus_master1/w1_master_slaves");
foreach ((array)$devices as $device) {
    $device = trim($device);
    if (empty($device)) continue;

    $sensor = "/sys/bus/w1/devices/".$device."/w1_slave";
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

$context = stream_context_create(array(
    "http" => array(
        "method" => "POST",
        "header" => array(
            "X-Auth-Token: ".$API_AUTH_TOKEN,
            "Content-Type: application/json"
        ),
        "content" => json_encode($readings),
        "ignore_errors" => true,
    ),
));

$response = @json_decode(file_get_contents($API_URL, false, $context));

if (isset($response->status) && $response->status == "OK") {
    echo "OK\n";
    echo "inserted=".$response->inserted."\n";
    echo "skipped=".$response->skipped."\n";
} else {
    echo "ERROR: ".$response->message."\n";

    if (!empty($readings)) {
        $db = get_sqlite($API_CACHE);
        $db->exec("INSERT INTO \"readings\" (\"device\", \"value\", \"date\") VALUES ".implode(", ", array_map(function($item) use ($db) {
            return "('".$db->escapeString($item["device"])."', '".$db->escapeString($item["value"])."', '".$db->escapeString($item["date"])."')";
        }, $readings)));
        $db->close();
    }
}
