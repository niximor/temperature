var temperatureApp = angular.module('temperatureApp', []);

var parseLocation = function(location) {
    var pairs = location.substring(1).split("&");
    var obj = {};
    var pair;
    var i;

    for ( i in pairs ) {
        if ( pairs[i] === "" ) continue;

        pair = pairs[i].split("=");
        obj[ decodeURIComponent( pair[0] ) ] = decodeURIComponent( pair[1] );
    }

    return obj;
};

temperatureApp.controller('TemperatureCtrl', function($scope, $http, $timeout, $location) {
	var chartInterval;

	var update = function(){
		var set = parseLocation(window.location.search).set;
		$http.get("get.php" + ((set != undefined)?"?set=" + set:"")).success(function(data){
			document.title = data.name;
			$scope.dashes = data.sensors;
			$timeout(update, 10000);

			if (chartInterval == undefined) {
				requestChartData();
				chartInterval = window.setInterval(requestChartData, 60000);
			}
		});
	}
	update();

	window.addEventListener("focus", function(){
		update();
	});

	function requestChartData() {
		for (var i in $scope.dashes) {
			var dash = $scope.dashes[i];
			$http.get("history.php?sensor=" + dash.id).success((function(dash){
				return function(data) {
					drawChart(dash.id, data);
				}})(dash)
			);
		}
	}

});

function drawChart(sensor, data) {
	var container = document.getElementById("chart_" + sensor);

	var minTemp = undefined;
	var maxTemp = undefined;
	for (var i in data) {
		for (var j in data[i]) {
			if (minTemp == undefined || minTemp > data[i][j][1]) {
				minTemp = parseFloat(data[i][j][1]);
			}

			if (maxTemp == undefined || maxTemp < data[i][j][1]) {
				maxTemp = parseFloat(data[i][j][1]);
			}
		}
	}

	//console.log("------------------ sensor " + sensor + " ---------------------");
	//console.log("min = " + minTemp + "; max = " + maxTemp);

	if (minTemp > 0) minTemp = 0;
	else minTemp = Math.floor((minTemp - 5) / 5) * 5;

	if (maxTemp < 10) maxTemp = 10;
	else maxTemp = Math.ceil((maxTemp + 5) / 5) * 5;

	//console.log("min = " + minTemp + "; max = " + maxTemp);

	for (var series in data) {
		for (var i in data[series]) {
			var dt = new Date(data[series][i][0]);
			data[series][i][0] = dt.getTime();
		}
	}

	Flotr.draw(container, [{
		lines: {
			fill: true
		},
		data: data[0],
	}], {
		colors: ['#00C90D', '#FF0D00'],
		shadowSize: 0,
		xaxis: {
			mode: 'time',
			timeMode: "local",
			color: '#4d4e56',
		},
		yaxis: {
			min: minTemp,
			max: maxTemp
		},
		grid: {
			verticalLines: false,
			tickColor: '#4d4e56',
			outlineWidth: 0
		}
	});
}
