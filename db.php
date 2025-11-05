<?php

function getDatabaseConnection(){


    $db_server = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "4150_project";
    
    // Create connection
    $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}

try {
    $conn = getDatabaseConnection();
} catch (Exception $e) {
    die($e->getMessage());
}
?>