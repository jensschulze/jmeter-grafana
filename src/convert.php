<?php

declare(strict_types=1);

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;
use League\CLImate\CLImate;

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

$chunkSize = (int) getenv('CONVERT_SEND_CHUNK_SIZE');
$influxdbHost = getenv('CONVERT_INFLUXDB_HOST');
$influxdbPort = (int) getenv('CONVERT_INFLUXDB_PORT');
$influxdbDb = getenv('CONVERT_INFLUXDB_DB');
$influxdbUser = getenv('CONVERT_INFLUXDB_USER');
$influxdbPassword = getenv('CONVERT_INFLUXDB_PASSWORD');

ini_set('memory_limit', '-1');

include 'vendor/autoload.php';

$console = new CLImate();

$console->info()->inline('Reading data from var/data.csv ');

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

$console->comment(sprintf('🧚🏼‍♂️ done (peak PHP memory usage: %d MByte)', memory_get_peak_usage(true) / (1024 * 1024)));

$console->info('Writing data to InfluxDB. ');

// Write "latency" and "elapsed" into the respective time series
$client = new Client($influxdbHost, $influxdbPort, $influxdbUser, $influxdbPassword);
$database = $client->selectDB($influxdbDb);

$count = 0;
$progress = $console->progress(count($csv));

foreach (array_chunk($csv, $chunkSize) as $chunk) {
    $points = [];
    foreach ($chunk as $row) {
        try {
            $points[] = new Point(
                'latency',
                (int) $row[JmeterData::LATENCY],
                ['label' => $row[JmeterData::LABEL], 'statuscode' => (int) $row[JmeterData::RESPONSECODE]],
                [],
                $row[JmeterData::TIMESTAMP]
            );
            $points[] = new Point(
                'elapsed',
                (int) $row[JmeterData::ELAPSED],
                ['label' => $row[JmeterData::LABEL], 'statuscode' => (int) $row[JmeterData::RESPONSECODE]],
                [],
                $row[JmeterData::TIMESTAMP]
            );

            $count++;
        } catch (Throwable $e) {
            $console->error(sprintf('Error (peak PHP memory: %d)', memory_get_peak_usage(true) / (1024 * 1024)));
            $console->error(sprintf(
                    'timestamp: %d; label: "%s"; statuscode: "%s"',
                    $row[JmeterData::TIMESTAMP],
                    $row[JmeterData::LABEL],
                    $row[JmeterData::RESPONSECODE]
                ));
            $console->error((string) $e);
        }
    }

    $database->writePoints($points, Database::PRECISION_MILLISECONDS);
    $progress->current($count);
}

$console->info('💆‍♂️ done!');
$console->info(sprintf('Wrote %d points into each series (peak PHP memory usage: %d MByte).', $count, memory_get_peak_usage(true) / (1024 * 1024)));
