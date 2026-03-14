<?php
session_start();

// $timeout = 1800; // 30 minutes
$timeout = 86400; // 24 hours

// If not logged in → redirect
if (!isset($_SESSION["uid"])) {
    header("Location: adminLogin.php");
    exit;
}

// If session expired → destroy & redirect
if (isset($_SESSION["LAST_ACTIVITY"]) &&
    (time() - $_SESSION["LAST_ACTIVITY"] > $timeout)) {

    session_unset();
    session_destroy();
    header("Location: adminLogin.php");
    exit;
}

// Update last activity time
$_SESSION["LAST_ACTIVITY"] = time();