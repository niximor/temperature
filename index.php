<?php

include "config.php";

$mysql = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB) or die("Cannot connect to database: ".mysqli_connect_error());
$mysql->query("SET NAMES utf8");

$sensors = array();
foreach ($mysql->query("SELECT id, name FROM sensors ORDER BY name ASC") as $row) {
	$sensors[] = $row;
}

$sets = array();
foreach ($mysql->query("SELECT id, name FROM sensor_set ORDER BY name ASC") as $row) {
	$sets[] = $row;
}

$mysql->close();

?>
<!DOCTYPE html>
<html>
<head>
	<title>Temperature</title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<style type="text/css">
		code { display: inline-block; padding: 0.3em 0.5em; background: #c0c0c0; }
		dt { font-weight: bold; margin-top: 0.5em; }
		li>code { display: block; }
		li { margin-top: 0.5em; }
	</style>
</head>

<body>

<div style="float: left; width: 50%; min-width: 15em;">
	<h1>Dashboards</h1>
	<ul>
<?php foreach ($sets as $row) { $id = $row["id"]; $name = $row["name"]; ?>
		<li><a href="dashboard/?set=<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></a></li>
<?php } ?>
    </ul>

    <h1>History</h1>
	<ul>
<?php foreach ($sensors as $row) { $id = $row["id"]; $name = $row["name"]; ?>
		<li><a href="chart.php?sensor=<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></a></li>
<?php } ?>
	</ul>

</div>

<div style="float: left; width: 50%; min-width: 15em;">
	<h1>Kiosks</h1>
	<ul>
<?php foreach ($sensors as $row) { $id = $row["id"]; $name = $row["name"]; ?>
		<li><a href="kiosk/?sensor=<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></a></li>
<?php } ?>
	</ul>
</div>

<h1 style="clear: both">API</h1>
<dl>
	<dt>Endpoint:</dt>
	<dd><code><?php if ($_SERVER["HTTPS"]) echo "https://"; else echo "http://"; echo $_SERVER["HTTP_HOST"]."/api.php"; ?></code></dd>

	<dt>Methods:</dt>
	<dd>
		<dl>
			<dt>POST</dt>
			<dd>
				<p>Used to collect new readings.</p>
				<dl>
					<dt>Authorization:</dt>
					<dd>Send header: <code>X-Auth-Token: &lt;token&gt;</code></dd>

					<dt>Content-Type:</dt>
					<dd>application/json</dd>
					<dd>application/x-www-form-urlencoded</dd>

					<dt>Format:</dt>
					<dd>
						<ul>
							<li><code>[<br />
							&nbsp;&nbsp;&nbsp;&nbsp;{<br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"device": "device-1",<br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"value": 12.345<br />
							&nbsp;&nbsp;&nbsp;&nbsp;},<br />
							&nbsp;&nbsp;&nbsp;&nbsp;{<br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"device": "device-2",<br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"value": 13.556<br />
							&nbsp;&nbsp;&nbsp;&nbsp;},<br />
							&nbsp;&nbsp;&nbsp;&nbsp;{<br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"device": "device-1",<br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"date": "2017-19-10T13:42:15",<br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"value": 8.113<br />
							&nbsp;&nbsp;&nbsp;&nbsp;}<br />
							]</code></li>

							<li><code>
								{<br />
								&nbsp;&nbsp;&nbsp;&nbsp;"device": "device-1",<br />
								&nbsp;&nbsp;&nbsp;&nbsp;"value": 12.345<br />
								}
							</code></li>

							<li><code>
								{<br />
								&nbsp;&nbsp;&nbsp;&nbsp;"device": "device-1",<br />
								&nbsp;&nbsp;&nbsp;&nbsp;"value": 8.113,<br />
								&nbsp;&nbsp;&nbsp;&nbsp;"date": "2017-19-10T13:42:15"<br />
								}
							</code></li>

							<li><code>
								device=device-1&amp;value=12.345
							</code></li>

							<li><code>
								device=device-1&amp;value=8.113&amp;date=2017-19-10T13:42:15
							</code></li>
						</ul>
					</dd>
				</dl>
			</dd>
		</dl>
	</dd>
</dl>

</body>
</html>
