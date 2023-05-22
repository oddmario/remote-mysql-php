<?php
function getDbConn() {
    global $config;

    $conn = new mysqli($config['db']['hostname'], $config['db']['username'], $config['db']['password'], $config['db']['name']);

    if( $conn->connect_error ) {
        die("Database connection failed.");
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
