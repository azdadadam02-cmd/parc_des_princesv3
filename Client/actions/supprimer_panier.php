<?php
session_start();
require '../includes/db.php';

if (isset($_GET['index']) && isset($_SESSION['panier'])) {
    $index = $_GET['index'];
    
    if (isset($_SESSION['panier'][$index])) {
        unset($_SESSION['panier'][$index]);
        $_SESSION['panier'] = array_values($_SESSION['panier']); 
    }
}

include '../includes/navbar.php'; 
?>
