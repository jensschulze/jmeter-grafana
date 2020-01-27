<?php

declare(strict_types=1);

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;

class JmeterData
{
    public const TIMESTAMP = 0;
    public const ELAPSED = 1;
    public const LABEL = 2;
    public const RESPONSECODE = 3;
    public const RESPONSEMESSAGE = 4;
    public const THREADNAME = 5;
    public const DATATYPE = 6;
    public const SUCCESS = 7;
    public const FAILUREMESSAGE = 8;
    public const BYTES = 9;
    public const SENTBYTES = 10;
    public const GRPTHREADS = 11;
    public const ALLTHREADS = 12;
    public const URL = 13;
    public const LATENCY = 14;
    public const IDLETIME = 15;
    public const CONNECT = 16;
}

ini_set('memory_limit', '-1');
include 'vendor/autoload.php';

// Read CSV into $csv array
$handle = fopen('var/data.csv', 'rb');

$csv = [];
while (true) {
    $row = fgetcsv($handle);
    if (false === $row) {
        break;
    }
    $csv[] = $row;
}

fclose($handle);

// Remove column titles from $csv
array_shift($csv);

// Write "latency" and "elapsed" into the respective time series
$client = new Client('127.0.0.1', 8086, 'jmeter', 'jmeter');
$database = $client->selectDB('jmeter');

$points = [];
foreach ($csv as $row) {
    $points[] = new Point('latency', (int) $row[JmeterData::LATENCY] , ['label' => $row[JmeterData::LABEL], 'statuscode' => (int) $row[JmeterData::RESPONSECODE]], [], $row[JmeterData::TIMESTAMP]);
    $points[] = new Point('elapsed', (int) $row[JmeterData::ELAPSED] , ['label' => $row[JmeterData::LABEL], 'statuscode' => (int) $row[JmeterData::RESPONSECODE]], [], $row[JmeterData::TIMESTAMP]);
}

$database->writePoints($points, Database::PRECISION_MILLISECONDS);

