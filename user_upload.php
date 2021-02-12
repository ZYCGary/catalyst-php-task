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

// Identify command line directives
$shortOpts = "h";
$shortOpts .= "u";
$shortOpts .= "p";

$longOpts = array(
    "file:",
    "create_table",
    "dry_run",
    "help"
);

$directives = [
    "--file <file>" => "this is the name of the CSV to be parsed",
    "--create_table" => "this will cause the MySQL users table to be built (and no further action will be taken)",
    "--dry_run" => "this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered",
    '-u' => "MySQL username",
    '-p' => "MySQL password",
    '-h' => "MySQL host",
    "--help" => "output the above list of directives with details."
];

// Get command line options
$options = getopt($shortOpts, $longOpts);

//connectDb($dbConfigs);

// Command option -u
if (isset($options['u'])) {
    fwrite(STDOUT, "MYSQL username: ${dbConfigs['username']}\n");
}

// Command option -h
if (isset($options['h'])) {
    fwrite(STDOUT, "MYSQL host: ${dbConfigs['host']}\n");
}

// Command option -p
if (isset($options['p'])) {
    fwrite(STDOUT, "MYSQL password: ${dbConfigs['password']}\n");
}

// Command option --help
if (isset($options['help'])) {
    $output = "";

    foreach ($directives as $key => $value) {
        $output .= "${key}\t${value}\n";
    }
    fwrite(STDOUT, $output);
}

function connectDb($configs)
{
    try {
        fwrite(STDOUT, "Connecting to database...\n");

        $connection = mysqli_connect($configs['host'], $configs['username'], $configs['password'], $configs['database'], $configs['port']);

        if (isConnected()) {
            fwrite(STDOUT, "Database connected!\n");
            return $connection;
        }
    } catch (Exception $exception) {
        fwrite(STDERR, "Connection failed!\n");
    }

}

function disconnectDb($connection)
{
    try {
        if (isConnected()) {
            $connection->close();
            fwrite(STDERR, "Database disconnected!\n");
        }
    } catch (Exception $exception) {
        fwrite(STDERR, "Disconnection failed!\n");
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


