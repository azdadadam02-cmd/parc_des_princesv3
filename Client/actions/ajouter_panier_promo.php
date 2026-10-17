<?php
session_start();
require_once '../../admin/includes/db.php'; 

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_promo = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT id_promo, nom_promo, valeur, jeux_inclus FROM promotion_billet WHERE id_promo = ? AND etat = 'Actif'");
        $stmt->execute([$id_promo]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($promo) {
            if (!isset($_SESSION['panier'])) {
                $_SESSION['panier'] = [];
            }

            // Nchofo ch7al mn merra had l'pack digha f l'panier
            $qte_deja_panier = 0;
            $index_existant = null;
            foreach ($_SESSION['panier'] as $index => $item) {
                if (isset($item['id_promo']) && (int)$item['id_promo'] === $id_promo) {
                    $qte_deja_panier = (int)$item['quantite'];
                    $index_existant = $index;
                    break;
                }
            }

            $nouvelle_qte = $qte_deja_panier + 1;

            // Vérification dyal la capacité dyal ga3 les jeux li f l'pack
            $jeux_inclus = json_decode($promo['jeux_inclus'] ?? '{}', true);
            if (!is_array($jeux_inclus) || empty($jeux_inclus)) {
                header("Location: ../../index.php?erreur=indisponible#section2");
                exit();
            }

            foreach ($jeux_inclus as $id_j => $qte_requise) {
                $st_j = $pdo->prepare("SELECT capacite FROM jeu WHERE id_jeu = ?");
                $st_j->execute([(int)$id_j]);
                $cap_dispo = (int)$st_j->fetchColumn();

                if ($cap_dispo < ((int)$qte_requise * $nouvelle_qte)) {
                    echo "<script>alert('Stock insuffisant pour ajouter ce pack !'); window.location.href='../../index.php#section2';</script>";
                    exit();
                }
            }

            if ($index_existant !== null) {
                $_SESSION['panier'][$index_existant]['quantite'] = $nouvelle_qte;
            } else {
                $_SESSION['panier'][] = [
                    'id_promo' => $promo['id_promo'],
                    'nom' => $promo['nom_promo'],
                    'tarif' => $promo['valeur'],
                    'quantite' => 1,
                    'img_jeu' => 'Client/img/logo.png' 
                ];
            }

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