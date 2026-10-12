<?php
session_start();
require '../includes/db.php';

$role = strtolower(trim((string)($_SESSION['user_role'] ?? $_SESSION['role'] ?? '')));

if (!isset($_SESSION['user_email']) || $role !== 'client') {
    header("Location: ../../login.php?form=login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['date_visite'])) {
    header("Location: ../../index.php");
    exit();
}

$email_client = $_SESSION['user_email'];

$date_visite = trim((string)($_POST['date_visite'] ?? ''));
$telephone = trim((string)($_POST['telephone'] ?? ''));
$date_naissance = trim((string)($_POST['date_naissance'] ?? ''));
$cin = strtoupper(trim((string)($_POST['cin'] ?? '')));

$timestamp_visite = strtotime($date_visite);
$timestamp_actuel = time();
$timestamp_max = strtotime('+3 months');

if (!$timestamp_visite || $timestamp_visite < $timestamp_actuel) {
    die("<script>alert('Erreur : Impossible de réserver une date dans le passé !'); window.history.back();</script>");
}

if ($timestamp_visite > $timestamp_max) {
    die("<script>alert('Erreur : Vous ne pouvez pas réserver plus de 3 mois à l\\'avance !'); window.history.back();</script>");
}

if (strlen($telephone) !== 10 || !ctype_digit($telephone)) {
    die("<script>alert('Le numéro de téléphone doit contenir exactement 10 chiffres.'); window.history.back();</script>");
}

$date_naissance_obj = DateTime::createFromFormat('!Y-m-d', $date_naissance);
$aujourdhui = new DateTime('today');

if (
    !$date_naissance_obj ||
    $date_naissance_obj->format('Y-m-d') !== $date_naissance ||
    $date_naissance_obj > $aujourdhui
) {
    die("<script>alert('Date de naissance invalide.'); window.history.back();</script>");
}

$age = $date_naissance_obj->diff($aujourdhui)->y;

if ($age < 16) {
    die("<script>alert('Vous devez avoir au moins 16 ans pour effectuer une réservation.'); window.history.back();</script>");
}

if ($cin === '' || !preg_match('/^[A-Z0-9-]{4,20}$/', $cin)) {
    die("<script>alert('Veuillez saisir un CIN valide.'); window.history.back();</script>");
}

if (empty($_SESSION['panier'])) {
    header("Location: ../../index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $st = $pdo->prepare("SELECT 1 FROM client WHERE email = ?");
    $st->execute([$email_client]);

    if (!$st->fetchColumn()) {
        throw new RuntimeException("Compte client invalide.");
    }

    $pdo->prepare("
        UPDATE utilisateur
        SET telephone = ?
        WHERE email = ?
    ")->execute([
        $telephone,
        $email_client
    ]);

    $pdo->prepare("
        UPDATE client
        SET date_naissance = ?,
            cin = ?
        WHERE email = ?
    ")->execute([
        $date_naissance,
        $cin,
        $email_client
    ]);

    $montant = 0.0;
    $nb_personnes = 0;
    $lignes = [];

    foreach ($_SESSION['panier'] as $cle => $ticket) {

        $id_jeu =
            isset($ticket['id_jeu'])
                ? (int)$ticket['id_jeu']
                : (
                    isset($ticket['id'])
                        ? (int)$ticket['id']
                        : (int)$cle
                );

        $quantite = max(
            1,
            (int)($ticket['quantite'] ?? 1)
        );

        if ($id_jeu <= 0) {
            continue;
        }

        $st = $pdo->prepare("
            SELECT tarif
            FROM jeu
            WHERE id_jeu = ?
        ");

        $st->execute([$id_jeu]);

        $tarif_db = $st->fetchColumn();

        if ($tarif_db === false) {
            throw new RuntimeException("Jeu introuvable.");
        }

        $tarif = (float)$tarif_db;

        $montant += $tarif * $quantite;
        $nb_personnes += $quantite;

        $lignes[] = [
            $id_jeu,
            $quantite,
            $tarif
        ];
    }

    if (!$lignes) {
        throw new RuntimeException("Aucun jeu valide dans le panier.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO reservation
        (
            date_creation,
            date_visite,
            nb_personnes,
            montant,
            statut_reservation,
            email_client,
            email_agent,
            vu,
            origine
        )
        VALUES
        (
            NOW(),
            ?,
            ?,
            ?,
            'en attente',
            ?,
            NULL,
            0,
            'en ligne'
        )
    ");

    $stmt->execute([
        $date_visite,
        $nb_personnes,
        $montant,
        $email_client
    ]);

    $num_reservation = (int)$pdo->lastInsertId();

    $stmt_choisir = $pdo->prepare("
        INSERT INTO choisir
        (
            num_reservation,
            id_jeu,
            quantite,
            prix_unitaire
        )
        VALUES (?, ?, ?, ?)
    ");

    foreach ($lignes as [$id_jeu, $quantite, $prix_unitaire]) {

        $stmt_choisir->execute([
            $num_reservation,
            $id_jeu,
            $quantite,
            $prix_unitaire
        ]);
    }

    $pdo->commit();

    unset($_SESSION['panier']);

    header("Location: ../../index.php?reservation=success");
    exit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        "Erreur lors de la réservation : "
        . htmlspecialchars($e->getMessage())
    );
}
