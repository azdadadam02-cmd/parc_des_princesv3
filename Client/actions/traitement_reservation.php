<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_email']) && isset($_POST['date_visite'])) {
    
    $email_client = $_SESSION['user_email'];
    $date_visite = $_POST['date_visite'];
    $montant = $_POST['montant'];
    $nb_personnes = $_POST['nb_personnes'];
    $statut = 'en attente';
    $timestamp_visite = strtotime($date_visite);
    $timestamp_actuel = time();
    $timestamp_max = strtotime('+3 months');

    if ($timestamp_visite < $timestamp_actuel) {
        die("<script>alert('Erreur : Impossible de réserver une date dans le passé !'); window.history.back();</script>");
    }

    if ($timestamp_visite > $timestamp_max) {
        die("<script>alert('Erreur : Vous ne pouvez pas réserver plus de 3 mois à l\'avance !'); window.history.back();</script>");
    }
    
    try {
        $telephone = $_POST['telephone'];
        
        if(strlen($telephone) !== 10 || !ctype_digit($telephone)) {
            die("<script>alert('Le numéro de téléphone doit contenir exactement 10 chiffres.'); window.history.back();</script>");
        }

        $_SESSION['telephone_client'] = $telephone;

        $pdo->beginTransaction();

        // Le téléphone appartient à UTILISATEUR, pas à RESERVATION.
        $stmt_tel = $pdo->prepare("UPDATE utilisateur SET telephone = ? WHERE email = ?");
        $stmt_tel->execute([$telephone, $email_client]);

        $stmt = $pdo->prepare("INSERT INTO reservation
            (date_visite, nb_personnes, montant, statut_reservation, email_client, origine)
            VALUES (?, ?, ?, ?, ?, 'en ligne')");
        $stmt->execute([$date_visite, $nb_personnes, $montant, $statut, $email_client]);

        $id_nouvelle_reservation = (int)$pdo->lastInsertId();

        if (isset($_SESSION['panier']) && count($_SESSION['panier']) > 0) {
            $stmt_choisir = $pdo->prepare("INSERT INTO choisir (num_reservation, id_jeu) VALUES (?, ?)");
            
            foreach ($_SESSION['panier'] as $cle => $ticket) {
                $id_jeu_reel = null;
                
                if (isset($ticket['id_jeu'])) {
                    $id_jeu_reel = $ticket['id_jeu'];
                } elseif (isset($ticket['id'])) {
                    $id_jeu_reel = $ticket['id'];
                } else {
                    $id_jeu_reel = $cle;
                }

                if ($id_jeu_reel != null) {
                    $stmt_choisir->execute([$id_nouvelle_reservation, $id_jeu_reel]);
                }
            }
        }

        $pdo->commit();

        unset($_SESSION['panier']);
        
        header("Location: ../../index.php?reservation=success");
        exit();

    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Erreur lors de la réservation : " . $e->getMessage());
    }
} else {
    header("Location: ../../index.php");
    exit();
}
?>
