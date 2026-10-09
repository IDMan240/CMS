<?php

/* =========================================================
   CALIBRATION MANAGEMENT SYSTEM
   DATABASE CONNECTION
========================================================= */

$host = "localhost";
$dbname = "calibration_management";
$username = "root";
$password = "";


/* =========================================================
   CREATE CONNECTION
========================================================= */

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $error) {

    die(
        "Database connection failed: " .
        $error->getMessage()
    );

}