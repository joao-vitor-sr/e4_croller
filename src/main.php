<?php

require __DIR__ . '/../vendor/autoload.php';

$csvUpdateObj = new Files\Columns\CsvUpdate;
$csvUpdateObj->csvFile = __DIR__ . '/../files/FinalizadosBovino.csv';
$csvUpdateObj->logFile = __DIR__ . '/../files/log.txt';
$csvUpdateObj->getEanCsvFile();

var_dump($csvUpdateObj);

