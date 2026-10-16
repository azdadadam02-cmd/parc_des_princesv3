<?php

session_start();

session_unset();
session_destroy();

header("Location: /parc_des_princesv3/login.php");
exit;