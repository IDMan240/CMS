<?php

/* =========================================================
   CALIBRATION MANAGEMENT SYSTEM
   LOGOUT
========================================================= */

session_start();

/* Remove all session variables */
$_SESSION = [];


/* Destroy the session */
session_destroy();


/* Return to login */
header("Location: ../public/login.php");

exit;