<?php

require_once __DIR__."/config.php";

$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB) or die("Cannot connect to database: ".mysqli_connect_error());
$db->query("SET NAMES utf8");

function check_auth($db, $token) {
    $q = $db->query("SELECT `ip_regex` FROM `authorization` WHERE `key` = '".$db->real_escape_string($token)."'");

    $ip = $_SERVER["REMOTE_ADDR"];
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    if ($a = $q->fetch_object()) {
        if (!preg_match("/".str_replace("/", "\\/", $a->ip_regex)."/", $ip)) {
            return false;
        }
    } else {
        return false;
    }

    return true;
}

/*function get_method() use ($db) {

}*/

function post_parse_data() {
    if ($_SERVER["HTTP_CONTENT_TYPE"] == "application/json") {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_array($data) && !is_object($data)) {
            throw new Exception("Bad request. Data is not array nor object.", 400);
        }

        if (is_object($data)) {
            if (empty($data->device) || empty($data->value)) {
                throw new Exception("Bad request. Missing device in object.", 400);
            }

            if (!is_string($data->device)) {
                throw new Exception("Bad request. Device in object must be string.", 400);
            }

            if (!is_numeric($data->value)) {
                throw new Exception("Bad request. Value in object must be number.", 400);
            }

            if (isset($data->date)) {
                try {
                    $data->date = new DateTime($data->date);
                } catch (Exception $e) {
                    throw new Exception("Bad request. Unable to parse date '".$data->date."': ".$e->getMessage().".", 400);
                }
            } else {
                $data->date = new DateTime();
            }

            return array($data);
        }

        if (is_array($data)) {
            $index = 0;
            foreach ($data as $item) {
                if (!is_object($item) || empty($item->device) || !isset($item->value)) {
                    throw new Exception("Bad request. Item in array at index ".$index." must be object and device and value must be set. Instead, got ".json_encode($item).".", 400);
                }

                if (!is_string($item->device)) {
                    throw new Exception("Bad request. Index ".$index.": Device must be string. Instead, got ".json_encode($item->device).".", 400);
                }

                if (!is_numeric($item->value)) {
                    throw new Exception("Bad request. Index ".$index.": Value must be number. Instead, got ".json_encode($item->value).".", 400);
                }

                if (isset($item->date)) {
                    try {
                        $item->date = new DateTime($item->date);
                    } catch (Exception $e) {
                        throw new Exception("Bad request. Index ".$index.": Unable to parse date '".$data->date."': ".$e->getMessage().".", 400);
                    }
                } else {
                    $item->date = new DateTime();
                }
                ++$index;
            }

            return $data;
        }
    } elseif (isset($_REQUEST["device"]) && isset($_REQUEST["value"])) {
        $item = new stdClass;

        if (isset($_REQUEST["device"]) && !empty($_REQUEST["device"])) {
            $item->device = $_POST["device"];
            if (!is_string($item->device)) {
                throw new Exception("Bad request. Device must be string.", 400);
            }
        } else {
            throw new Exception("Missing device parameter.", 400);
        }

        if (isset($_REQUEST["value"]) && !empty($_REQUEST["value"])) {
            $item->value = $_POST["value"];
            if (!is_numeric($item->value)) {
                throw new Exception("Value parameter must be a number.", 400);
            }
        } else {
            throw new Exception("Missing value parameter.", 400);
        }

        if (isset($_REQUEST["date"])) {
            if (!empty($_REQUEST["date"])) {
                try {
                    $item->date = new DateTime($_REQUEST["date"]);
                } catch (Exception $e) {
                    throw new Exception("Parameter date must contain valid date.", 400);
                }
            } else {
                throw new Exception("Parameter date cannot be empty.", 400);
            }
        } else {
            $item->date = new DateTime();
        }

        return array(
            $item
        );
    }
}

function post_method($db) {
    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
        throw new Exception("Missing access token.", 400);
    }

    if (!check_auth($db, $_SERVER["HTTP_X_AUTH_TOKEN"])) {
        throw new Exception("Invalid access token.", 403);
    }

    $data = post_parse_data();

    // Resolve devices.
    $devices = array();
    foreach ($data as $item) {
        if (!isset($devices[$item->device])) {
            $devices[$item->device] = NULL;
        }
    }

    if (!empty($devices)) {
        $q = $db->query("SELECT `id`, `device`
            FROM `sensors`
            WHERE `device` IN (".implode(",",
                array_map(function($item) use ($db) {
                    return "'".$db->real_escape_string($item)."'";
                }, array_keys($devices))
            ).")");
        while ($a = $q->fetch_object()) {
            $devices[$a->device] = $a->id;
        }

        foreach ($devices as $device => $id) {
            if (is_null($id)) {
                $db->query("INSERT INTO `sensors` (`name`, `device`)
                    VALUES ('".$db->real_escape_string($device)."', '".$db->real_escape_string($device)."')");
                $devices[$device] = $db->insert_id;
            }
        }
    }

    $to_insert = [];
    $inserted = 0;
    $skipped = 0;
    foreach ($data as $item) {
        if ($item->value == 85) {
            // Skip magic 85 degrees, which means sensor error.
            ++$skipped;
            continue;
        }

        $item->date->setTimezone(new DateTimeZone("UTC"));
        $to_insert[] = "(
            '".$db->real_escape_string($devices[$item->device])."',
            '".$db->real_escape_string($item->date->format("Y-m-d H:i:s"))."',
            '".$db->real_escape_string($item->value * 1000)."'
        )";
        ++$inserted;
    }

    if (!empty($to_insert)) {
        $db->query("INSERT INTO `history` (`sensor`, `date`, `value`) VALUES ".implode(", ", $to_insert));
    }

    http_response_code(200);
    echo json_encode(array(
        "status" => "OK",
        "inserted" => $inserted,
        "skipped" => $skipped
    ));
}

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        /*case "GET":
            get_method();
            break;*/

        case "POST":
            post_method($db);
            break;

        default:
            throw new Exception("Method ".$_SERVER["REQUEST_METHOD"]." not allowed.", 405);
            break;
    }
} catch (Exception $e) {
    if ($e->getCode()) {
        http_response_code($e->getCode());
    } else {
        http_response_code(500);
    }

    echo json_encode(array(
        "status" => "ERROR",
        "message" => $e->getMessage()
    ));
}
