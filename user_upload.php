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

// Command option --create_table
if (isset($options['create_table'])) {
    createTable($dbConfigs);
}

// Command option --file <filename>
if (isset($options['file'])) {
    $data = getDataFromFile($options['file']);

    $connection = connectDb($dbConfigs);

    rebuildDbTable($connection, $data, isset($options['dry_run']));

    disconnectDb($connection);
}


/**
 * Connect to MYSQL database.
 *
 * @param $configs : Database configurations.
 * @return false|mysqli
 */
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

/**
 * Disconnect from MYSQL database.
 *
 * @param $connection : Previously opened database connection.
 */
function disconnectDb($connection)
{
    try {
        if (isConnected()) {
            mysqli_close($connection);
            fwrite(STDERR, "Database disconnected!\n");
        }
    } catch (Exception $exception) {
        fwrite(STDERR, "Disconnection failed!\n");
    }

}

/**
 * Identify if MYSQL database has been connected.
 *
 * @return bool
 */
function isConnected()
{
    return mysqli_connect_errno() === 0;
}

/**
 * Create "users" table.
 * @param $dbConfig : Database configuration.
 */
function createTable($dbConfig)
{
    $query = "CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL default '',
    `surname` varchar(255) NOT NULL default '',
    `email` varchar(255) NOT NULL default '',
    PRIMARY KEY  (`id`)
)";

    $connection = connectDb($dbConfig);

    try {
        $creation = $connection->query($query);

        if ($creation) {
            fwrite(STDOUT, "Table 'users' created!\n");
        } else {
            fwrite(STDOUT, "Failed to create table!\n");
        }
    } catch (Exception $exception) {
        $error = mysqli_connect_error();
        fwrite(STDOUT, "Failed to create table: ${error}");
    }

    disconnectDb($connection);
}

/**
 * Read & format data from csv file.
 *
 * @param $file : csv file.
 * @return array
 */
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

function rebuildDbTable($connection, $data, $dryRun)
{
    if (!$dryRun) {
        checkTableExists($connection);
        truncateTable($connection);
    }

    foreach ($data as $info) {
        $user = [
            'name' => $info[0],
            'surname' => $info[1],
            'email' => $info[2]
        ];

        if (isValidEmail($user['email'])) {
            if (!$dryRun) {
                try {
                    insertData($connection, $user);
                } catch (Exception $exception) {
                    die("Errors happen when inserting user data.\n");
                }
            }
        } else {
            fwrite(STDOUT, "${user['email']} is an invalid email.\n");
        }
    }
}

/**
 * Validate email.
 *
 * @param $email
 * @return bool
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check if table 'users' exist.
 *
 * @param $connection
 */
function checkTableExists($connection)
{
    if (!$connection->query("select 1 from `users`")) {
        die("table 'users' does not exist.\n");
    }
}

function truncateTable($connection)
{
    $truncateTable = $connection->query("TRUNCATE TABLE `users`");

    $truncateTable
        ? fwrite(STDOUT, "Table cleared!\n")
        : fwrite(STDOUT, "No rows have been cleared.\n");
}

/**
 * Insert user data into 'users' table.
 *
 * @param $connection
 * @param $data
 */
function insertData($connection, $data)
{
    $name = addslashes($data['name']);
    $surname = addslashes($data['surname']);
    $email = addslashes($data['email']);
    var_dump($email);

    $sql = "INSERT INTO `users` (name, surname, email) VALUES ('${name}', '${surname}', '${email}')";

    if ($connection->query($sql)) {
        fwrite(STDOUT, "New user created.\n");
    } else {
        fwrite(STDERR, "Errors happen when creating new user: " . $connection->error . "\n");
    }
}
