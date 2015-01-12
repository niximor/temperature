var updateData = function(sensor){
	var options = {
		colors: [ "#4F4D48", "#F2B933" ],
		xaxis: {
			mode: "time",
			color: "white",
			noTicks: 12,
		},
		yaxis: {
			color: "white",
		},
		grid: {
			tickColor: "#808080",
			outlineWidth: 1
		},
		legend: {
			position: "sw",
			noColumns: 2,
			backgroundColor: "#2D4866",
			backgroundOpacity: 0.85,
			labelFormatter: function(label, series) {
				return '<span style="color: white">' + label + '</span>';
			},
			labelBoxBorderColor: null
		}

		//lines: { show: true },
		/*xaxis: {
			mode: "time",
			tickLength: 5,
			color: "white",
			tickColor: "white",
			tickColor: "#2D4866"
		},*/
		/*yaxis: {
			color: "white",
			tickColor: "#2D4866"
		},*/
		/*grid: {
			show: true,
			tickColor: "#2D4866",
			outlineWidth: 1,
		},*/
		
		/*legend: {
			position: "sw",
			noColumns: 2,
			backgroundOpacity: 0,
			labelFormatter: function(label, series) {
				return '<span style="color: white">' + label + '</span>'
			}
		}*/
	};

	$.getJSON("get.php?sensor=" + sensor, function(data) {
		for (var i in data) {
			if (typeof(data[i]) == "object") {
				$("#" + i + " .value").html(data[i].value);
				$("#" + i + " .date").html(data[i].date);
			} else {
				console.log(data[i]);
				$("#" + i).html(data[i]);
			}
		}
	});

	$.getJSON("history.php?period=48&sensor=" + sensor, function(data) {
		var chartData = [];
		var oldData = [];

		var minTemp = undefined;
		var maxTemp = undefined;

		for (var i = 0; i < data.current.length; i++) {
			chartData.push([data.current[i].date * 1000, data.current[i].value]);

			if (minTemp == undefined || minTemp > data.current[i].value) {
				minTemp = parseFloat(data.current[i].value);
			}

			if (maxTemp == undefined || maxTemp < data.current[i].value) {
				maxTemp = parseFloat(data.current[i].value);
			}
		}
		for (var i = 0; i < data.lastyear.length; i++) {
			oldData.push([(data.lastyear[i].date + 365*86400) * 1000, data.lastyear[i].value]);
			if (minTemp == undefined || minTemp > data.lastyear[i].value) {
				minTemp = parseFloat(data.lastyear[i].value);
			}

			if (maxTemp == undefined || maxTemp < data.lastyear[i].value) {
				maxTemp = parseFloat(data.lastyear[i].value);
			}

		}

		if (minTemp > 0) minTemp = 0;
		else minTemp = Math.floor((minTemp - 5) / 5) * 5;

		if (maxTemp < 10) maxTemp = 10;
		else maxTemp = Math.ceil((maxTemp + 5) / 5) * 5;

		options.yaxis.min = minTemp;
		options.yaxis.max = maxTemp;

		Flotr.draw(document.getElementById("chart"), [
			{
				label: "Last year",
				data: oldData
			},
			{
				label: "Current",
				data: chartData
			}
		], options);
	});
}

$(function(){
	var reconfigure = function(){
		$.getJSON("getconf.php", function(data){
			if (data.background) {
				$(document.body).css("background-image", "url('" + data.background + "')");
			}
		});
	}

	window.setInterval(reconfigure, 60000);
	reconfigure();
});
