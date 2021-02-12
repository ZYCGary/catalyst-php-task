<?php

use Dotenv\Dotenv as Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connect to database via command line
$dbConfigs = [
    'host' => $_ENV['DB_HOST'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'database' => $_ENV['DB_DATABASE'],
    'port' => $_ENV['DB_PORT']
];

$shortOpts = "h";
$shortOpts .= "u";
$shortOpts .= "p";
$longOpts = array(
    "file:",
    "create_table",
    "dry_run"
);

$options = getopt($shortOpts, $longOpts);

function connectDb($configs)
{
    try {
        echo("Connecting to database...\n");

        $connection = mysqli_connect($configs['host'], $configs['username'], $configs['password'], $configs['database'], $configs['port']);

        if (isConnected()) {
            echo("Database connected.\n");
            return $connection;
        }
    } catch (Exception $exception) {
        die('Connection failed.');
    }

}

function disconnectDb($connection)
{
    if (isConnected()) {
        $connection->close();
    }
}

function isConnected()
{
    return mysqli_connect_errno() === 0;
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
//
//$data = getDataFromFile('users.csv');
//
//print_r($data);


