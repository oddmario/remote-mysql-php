<?php
function getDbConn($db = '') {
    global $config;

    $database = '';
    if( empty($db) ) {
        $database = $config['db']['name'];
    } else {
        $database = $db;
    }

    $conn = new mysqli($config['db']['hostname'], $config['db']['username'], $config['db']['password'], $database);

    if( $conn->connect_error ) {
        die("Database connection failed.");
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
