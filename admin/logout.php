<?php

session_start();

session_unset();
session_destroy();

header("Location: /parc-des-prince/login.php");
exit;