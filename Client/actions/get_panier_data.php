<?php
session_start();
header('Content-Type: application/json');

$panier_js = [];
if(isset($_SESSION['panier'])) {
    foreach($_SESSION['panier'] as $ticket) {
        if ((int)($ticket['id_promo'] ?? 0) > 0 || !isset($ticket['id_jeu'])) continue;
        $id = $ticket['id_jeu'];
        $quantite = intval($ticket['quantite']);
        
        if (isset($panier_js[$id])) {
            $panier_js[$id] += $quantite;
        } else {
            $panier_js[$id] = $quantite;
        }
    }
}
echo json_encode($panier_js);
?>
