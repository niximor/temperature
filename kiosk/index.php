<?php
	include "../config.php";

	$sensor = (isset($_REQUEST["sensor"]))?(int)$_REQUEST["sensor"]:$DEFAULT_SENSOR_ID;
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>Teploměr</title>
		<link rel="stylesheet" type="text/css" href="style.css" />
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
		<script type="text/javascript" src="Flotr2/flotr2.min.js"></script>
		<script type="text/javascript" src="funcs.js"></script>
		<script type="text/javascript">
			$(function(){
				updateData(<?php echo $sensor; ?>);
				window.setInterval(function(){
					updateData(<?php echo $sensor; ?>);
				}, 10000);
			});
		</script>
	</head>

	<body>
		<div class="container">
			<div class="c2">
				<div id="last">
					<span class="title">Aktuální teplota:</span>
					<span class="value"></span>
					<span class="unit">&deg;C</span>
					<span class="dateContainer">
						<span class="date"></span>
						<span id="sensor"></span>
					</span>
				</div>

				<div class="peaks">
					<span class="title">Za posledních 24 hod:</span>
					<span id="dayMax">
						<span class="subtitle">Max:</span>
						<span class="value"></span>
						<span class="unit">&deg;C</span>
						<span class="date"></span>
					</span>

					<span id="dayMin">
						<span class="subtitle">Min:</span>
						<span class="value"></span>
						<span class="unit">&deg;C</span>
						<span class="date"></span>
					</span>

					<span class="title">All time:</span>

					<span id="totalMax">
						<span class="subtitle">Max:</span>
						<span class="value"></span>
						<span class="unit">&deg;C</span>
						<span class="date"></span>
					</span>

					<span id="totalMin">
						<span class="subtitle">Min:</span>
						<span class="value"></span>
						<span class="unit">&deg;C</span>
						<span class="date"></span>
					</span>
				</div>

				<div class="c3"><div id="chart"></div></div>
			</div>
		</div>
	</body>
</html>
