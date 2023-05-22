<?php
error_reporting(0);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once("config.php");
require_once("db.php");

header("Content-Type: application/json");

if( !isset($_GET['password']) || empty($_GET['password']) ) {
    http_response_code(401);
    
    echo json_encode(array(
        "error" => true,
        "err" => "Unauthorized",
    ), JSON_PRETTY_PRINT);
    
    die();
}

if( $_GET['password'] !== $config['password'] ) {
    http_response_code(401);
    
    echo json_encode(array(
        "error" => true,
        "err" => "Unauthorized",
    ), JSON_PRETTY_PRINT);
    
    die();
}

if( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code(400);
    
    echo json_encode(array(
        "error" => true,
        "err" => "Bad Request",
    ), JSON_PRETTY_PRINT);
    
    die();
}

$request = file_get_contents("php://input");
$request = json_decode($request, true);

if( json_last_error() !== JSON_ERROR_NONE ) {
    http_response_code(400);
    
    echo json_encode(array(
        "error" => true,
        "err" => "Bad Request",
    ), JSON_PRETTY_PRINT);
    
    die();
}

if( !isset($request['sql']) ) {
    http_response_code(422);
    
    echo json_encode(array(
        "error" => true,
        "err" => "Missing the `sql` parameter",
    ), JSON_PRETTY_PRINT);
    
    die();
}

$conn = getDbConn();
$stmt = $conn->prepare($request['sql']);

if( is_bool($stmt) ) {
    http_response_code(500);
    
    echo json_encode(array(
        "error" => true,
        "err" => $conn->error,
    ), JSON_PRETTY_PRINT);
    
    $conn->close();
    
    die();
}

if( isset($request['bind_letters']) ) {
    $stmt->bind_param($request['bind_letters'], ...$request['bind_values']);
}

if( !$stmt->execute() ) {
    http_response_code(500);
    
    echo json_encode(array(
        "error" => true,
        "err" => $conn->error,
    ), JSON_PRETTY_PRINT);
    
    $stmt->close();
    $conn->close();
    
    die();
}

$result = $stmt->get_result();
$insertID = $stmt->insert_id;
$stmt->close();
$conn->close();

$rows = array();
if( !is_bool($result) ) {
    if( !isset($request['no_rows']) ) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
    }
}

echo json_encode(array(
    "error" => false,
    "rows" => $rows,
    "num_rows" => $result->num_rows,
    "insert_id" => $insertID,
), JSON_PRETTY_PRINT);