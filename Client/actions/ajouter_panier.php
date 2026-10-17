<?php
session_start();
require '../includes/db.php';
require_once __DIR__.'/../includes/tarifs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_jeu']) && isset($_POST['quantite'])) {
    
    $id_jeu = $_POST['id_jeu'];
    $quantite = intval($_POST['quantite']);

    $stmt = $pdo->prepare("SELECT nom, tarif, tarif_promo, img_jeu, capacite FROM JEU WHERE id_jeu = ?");
    $stmt->execute([$id_jeu]);
    $jeu = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($jeu) {
        $capacite_max = intval($jeu['capacite']);
      
        if (!isset($_SESSION['panier'])) {
            $_SESSION['panier'] = [];
        }

        $jeu_existe = false;
        $quantite_deja_au_panier = 0;
        $index_au_panier = -1;

        foreach ($_SESSION['panier'] as $key => $ticket) {
            if ((int)($ticket['id_promo'] ?? 0) === 0 && (int)($ticket['id_jeu'] ?? 0) === (int)$id_jeu) {
                $quantite_deja_au_panier = intval($ticket['quantite']);
                $jeu_existe = true;
                $index_au_panier = $key;
                break;
            }
        }

        $quantite_totale_voulue = $quantite_deja_au_panier + $quantite;

        if ($quantite_totale_voulue > $capacite_max) {
             echo "Erreur: Max atteint";
             exit();
        }

        if ($jeu_existe) {
            $_SESSION['panier'][$index_au_panier]['quantite'] += $quantite;
            $_SESSION['panier'][$index_au_panier]['tarif'] = client_prix_jeu($jeu);
        } else {
            $_SESSION['panier'][] = [
                'id_jeu' => $id_jeu,
                'nom' => $jeu['nom'],
                'tarif' => client_prix_jeu($jeu),
                'quantite' => $quantite,
                'img' => $jeu['img_jeu']
            ];
        }
    }
    
    include '../includes/navbar.php';
    exit();
}
?>
