<?php

require __DIR__ . '/../vendor/autoload.php';

$valuesFiles = getopt("f:l:");

$csvFile = $valuesFiles['f'];
$logFile = $valuesFiles['l'];

$csvUpdateObj = new Files\Columns\CsvUpdate;
$csvUpdateObj->csvFile = __DIR__ . "/{$csvFile}";
$csvUpdateObj->logFile = __DIR__ . "/{$logFile}";
$csvUpdateObj->getEanCsvFile();

var_dump($csvUpdateObj);
