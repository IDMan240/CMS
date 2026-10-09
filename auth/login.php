<?php

/* =========================================================
   CALIBRATION MANAGEMENT SYSTEM
   LOGIN HANDLER
========================================================= */

session_start();

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   ONLY ACCEPT POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../public/login.php");
    exit;

}


/* =========================================================
   GET FORM DATA
========================================================= */

$username = trim(
    $_POST["username"] ?? ""
);

$password = $_POST["password"] ?? "";


/* =========================================================
   CHECK REQUIRED FIELDS
========================================================= */

if (
    $username === "" ||
    $password === ""
) {

    header(
        "Location: ../public/login.php?error=required"
    );

    exit;

}


/* =========================================================
   FIND USER
========================================================= */

try {

    $statement = $pdo->prepare(
        "SELECT
            id,
            username,
            password,
            full_name,
            role,
            status
         FROM users
         WHERE username = ?
         LIMIT 1"
    );


    $statement->execute([
        $username
    ]);


    $user = $statement->fetch();


} catch (PDOException $error) {

    error_log(
        "Login database error: " .
        $error->getMessage()
    );


    header(
        "Location: ../public/login.php?error=database"
    );

    exit;

}


/* =========================================================
   USER NOT FOUND
========================================================= */

if (!$user) {

    header(
        "Location: ../public/login.php?error=invalid"
    );

    exit;

}


/* =========================================================
   CHECK ACCOUNT STATUS
========================================================= */

if ($user["status"] !== "active") {

    header(
        "Location: ../public/login.php?error=disabled"
    );

    exit;

}


/* =========================================================
   VERIFY PASSWORD
========================================================= */

if (
    !password_verify(
        $password,
        $user["password"]
    )
) {

    header(
        "Location: ../public/login.php?error=invalid"
    );

    exit;

}


/* =========================================================
   LOGIN SUCCESS
========================================================= */

/*
 * Generate a new session ID.
 *
 * This helps prevent session fixation attacks.
 */

session_regenerate_id(true);


/* =========================================================
   STORE USER SESSION
========================================================= */

$_SESSION["user_id"] =
    $user["id"];

$_SESSION["username"] =
    $user["username"];

$_SESSION["full_name"] =
    $user["full_name"];

$_SESSION["role"] =
    $user["role"];


/* =========================================================
   LOGIN TIME
========================================================= */

$_SESSION["login_time"] =
    time();


/* =========================================================
   REDIRECT TO DASHBOARD
========================================================= */

header(
    "Location: ../public/dashboard.php"
);

exit;