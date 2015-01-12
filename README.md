# Temperature monitoring for Raspberry PI and 1-wire DS18B20 sensors.

## Requirements

- Raspberry PI setup with 1-wire network of DS18B20 sensors.
  For hardware and system setup visit: http://www.reuk.co.uk/DS18B20-Temperature-Sensor-with-Raspberry-Pi.htm
- MySQL server
- Web server with PHP 5.2+ running

## Installation

1. edit config.php.example to provide valid connection information for MySQL.
2. import database dump from database.sql into your database.
3. add collect/collect.php to be run each minute.
4. Insert existing sensors to the database's sensors table. Specifically the hardware ID is required for proper function of the monitoring.
5. Optionally create sensor sets and add sensors to them. You need to manually modify data in the database for this.
6. Point your browser to kiosk/ or dashboard/ to see readings.
