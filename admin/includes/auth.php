<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_email'])) {

    header("Location: /parc-des-prince/login.php");
    exit;
}