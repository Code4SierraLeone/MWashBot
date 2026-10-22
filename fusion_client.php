<?php
// composer.json pins google/apiclient 2.9.2 and services 0.130: upstream still supplies these FusionTables classes on PHP 8.3, so no copied legacy classes or classmap are needed.


// composer autoload, loads the google api lib
require_once('vendor/autoload.php');
putenv('GOOGLE_APPLICATION_CREDENTIALS=./credentials.json');

$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->setScopes('https://www.googleapis.com/auth/fusiontables');

function combineColumnsAndRows($result) {
    // use column names to create associative arrays in $rows
    $columns = $result->getColumns();
    $rows = $result->getRows();
    array_walk($rows, function(&$row) use ($columns) {
        $row = array_combine($columns, $row);
    });
    return $rows;
}

$service = new Google_Service_Fusiontables($client);