<?php
session_start();
require_once '../../admin/includes/db.php'; 

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_promo = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT id_promo, nom_promo, valeur FROM promotion_billet WHERE id_promo = ? AND etat = 'Actif'");
        $stmt->execute([$id_promo]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($promo) {
            if (!isset($_SESSION['panier'])) {
                $_SESSION['panier'] = [];
            }

            $ticket_existe = false;
            foreach ($_SESSION['panier'] as $index => $item) {
                if (isset($item['id_promo']) && $item['id_promo'] == $id_promo) {
                    $_SESSION['panier'][$index]['quantite'] += 1;
                    $ticket_existe = true;
                    break;
                }
            }

            if (!$ticket_existe) {
                $_SESSION['panier'][] = [
                    'id_promo' => $promo['id_promo'],
                    'nom' => $promo['nom_promo'],
                    'tarif' => $promo['valeur'],
                    'quantite' => 1,
                    'img_jeu' => 'Client/img/logo.png' 
                ];
            }

            // Hna rjje3naha l index.php l'asli dyalk!
            header("Location: ../../index.php?ajout=success#section2");
            exit();

        } else {
            header("Location: ../../index.php?erreur=ticket_introuvable#section2");
            exit();
        }

    } catch (PDOException $e) {
        header("Location: ../../index.php?erreur=bd#section2");
        exit();
    }
} else {
    header("Location: ../../index.php?erreur=id_manquant");
    exit();
}
?>