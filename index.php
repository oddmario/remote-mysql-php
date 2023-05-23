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

$db = '';
if( isset($_GET['database']) && !empty($_GET['database']) ) {
    $db = $_GET['database'];
}
$conn = getDbConn($db);
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
    $b64QueryPrefix = "b64decode::";
    foreach( $request['bind_values'] as $index => $bindValue ) {
        if( substr($bindValue, 0, strlen($b64QueryPrefix)) ===  $b64QueryPrefix ) {
            $request['bind_values'][$index] = base64_decode( str_replace("b64decode::", "", $bindValue) );
        }
    }

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
        if( !isset($request['b64_fields']) ) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            if( $result->num_rows > 0 ) {
                $i = 0;
                while($row = $result->fetch_assoc()) {
                    $rows[$i] = array();
                    foreach($row as $k => $v) {
                        if( in_array($k, $request['b64_fields']) ) {
                            $rows[$i][$k] = base64_encode($v);
                        } else {
                            $rows[$i][$k] = $v;
                        }
                    }
                    $i++;
                }
            }
        }
    }
}

echo json_encode(array(
    "error" => false,
    "rows" => $rows,
    "num_rows" => $result->num_rows,
    "insert_id" => $insertID,
), JSON_PRETTY_PRINT);