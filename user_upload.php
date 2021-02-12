<?php

use Dotenv\Dotenv as Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function OpenCon()
{
    $conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE'])
    or die("Connect failed: %s\n" . $conn->error);

    return $conn;
}

function CloseCon($conn)
{
    $conn->close();
}

function getDataFromFile($file)
{
    $data = [];

    $csvFile = fopen($file, "r") or die("Invalid input file.\n");

    // Skip the first line
    fgetcsv($csvFile);

    while (($line = fgetcsv($csvFile)) !== FALSE) {
        $data[] = formatData($line);
    }

    return $data;
}

function formatData($data)
{
    $data[0] = ucfirst(strtolower(trim($data[0])));
    $data[1] = ucfirst(strtolower(trim($data[1])));
    $data[2] = strtolower(trim($data[2]));

    return $data;
}

$data = getDataFromFile('users.csv');

print_r($data);


