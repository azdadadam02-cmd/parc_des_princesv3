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

    $pdo->prepare("UPDATE utilisateur SET telephone = ? WHERE email = ?")
        ->execute([$telephone, $email_client]);

    $pdo->prepare("UPDATE client SET date_naissance = ?, cin = ? WHERE email = ?")
        ->execute([$date_naissance, $cin, $email_client]);

    $montant = 0.0;
    $nb_personnes = 0;
    $lignes = [];

    foreach ($_SESSION['panier'] as $cle => $ticket) {
        $quantite = max(1, (int)($ticket['quantite'] ?? 1));

        // CAS 1 : Ila kan Ticket Promotion (Pack)
        if (isset($ticket['id_promo']) && (int)$ticket['id_promo'] > 0) {
            $id_promo = (int)$ticket['id_promo'];

            $st_promo = $pdo->prepare("
                SELECT nom_promo, valeur, capacite_membres, jeux_inclus 
                FROM promotion_billet 
                WHERE id_promo = ? AND etat = 'Actif'
            ");
            $st_promo->execute([$id_promo]);
            $promo_db = $st_promo->fetch(PDO::FETCH_ASSOC);

            if (!$promo_db) {
                throw new RuntimeException("Ce pack promotionnel n'est plus disponible.");
            }

            $prix_pack = (float)$promo_db['valeur'];
            $cap_membres = max(1, (int)$promo_db['capacite_membres']);

            // L'montant total kayb9a howa taman l'pack bla ma ytbdl!
            $montant += $prix_pack * $quantite;
            $nb_personnes += $cap_membres * $quantite;

            $jeux_inclus = json_decode($promo_db['jeux_inclus'] ?? '{}', true);
            if (!is_array($jeux_inclus) || empty($jeux_inclus)) {
                throw new RuntimeException("Aucun jeu configuré dans le pack : " . $promo_db['nom_promo']);
            }

            foreach ($jeux_inclus as $id_j => $qte_par_pack) {
                $id_j = (int)$id_j;
                $qte_totale_jeu = (int)$qte_par_pack * $quantite;

                if ($qte_totale_jeu <= 0) {
                    continue;
                }

                // Njibo hta tarif l-asli dyal lo3ba mn table JEU
                $st_jeu = $pdo->prepare("SELECT nom, tarif, capacite FROM jeu WHERE id_jeu = ? FOR UPDATE");
                $st_jeu->execute([$id_j]);
                $jeu_info = $st_jeu->fetch(PDO::FETCH_ASSOC);

                if (!$jeu_info || (int)$jeu_info['capacite'] < $qte_totale_jeu) {
                    throw new RuntimeException("Stock épuisé pour le jeu : " . ($jeu_info['nom'] ?? ('#' . $id_j)));
                }

                // N9sso la capacité mn table JEU
                $pdo->prepare("UPDATE jeu SET capacite = capacite - ? WHERE id_jeu = ?")
                    ->execute([$qte_totale_jeu, $id_j]);

                // N7tto tarif l-asli dyal lo3ba f choisir bach l-client ychof ch7al katdir kol lo3ba
                $tarif_jeu_reel = (float)$jeu_info['tarif'];

                $lignes[] = [
                    $id_j,
                    $qte_totale_jeu,
                    $tarif_jeu_reel
                ];
            }
        } 
        // CAS 2 : Ila kan Jeu 3adi
        else {
            $id_jeu = isset($ticket['id_jeu']) ? (int)$ticket['id_jeu'] : (isset($ticket['id']) ? (int)$ticket['id'] : 0);

            if ($id_jeu <= 0) {
                continue;
            }

            $st = $pdo->prepare("SELECT nom, tarif, capacite FROM jeu WHERE id_jeu = ? FOR UPDATE");
            $st->execute([$id_jeu]);
            $jeu_db = $st->fetch(PDO::FETCH_ASSOC);

            if (!$jeu_db) {
                throw new RuntimeException("Jeu introuvable.");
            }

            if ((int)$jeu_db['capacite'] < $quantite) {
                throw new RuntimeException("Capacité insuffisante pour le jeu : " . $jeu_db['nom']);
            }

            $pdo->prepare("UPDATE jeu SET capacite = capacite - ? WHERE id_jeu = ?")
                ->execute([$quantite, $id_jeu]);

            $tarif = (float)$jeu_db['tarif'];
            $montant += $tarif * $quantite;
            $nb_personnes += $quantite;

            $lignes[] = [
                $id_jeu,
                $quantite,
                $tarif
            ];
        }
    }

    if (!$lignes) {
        throw new RuntimeException("Aucun ticket valide dans le panier.");
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

    // Njm3o les jeux ila t3awdo f l'panier m3a l-7ifad 3la tarif l-asli dyalhom
    $lignes_groupees = [];
    foreach ($lignes as [$id_jeu, $quantite, $prix_unitaire]) {
        if (!isset($lignes_groupees[$id_jeu])) {
            $lignes_groupees[$id_jeu] = ['quantite' => 0, 'prix_unitaire' => $prix_unitaire];
        }
        $lignes_groupees[$id_jeu]['quantite'] += $quantite;
    }

    $stmt_choisir = $pdo->prepare("
        INSERT INTO choisir (num_reservation, id_jeu, quantite, prix_unitaire)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($lignes_groupees as $id_jeu => $data) {
        $stmt_choisir->execute([
            $num_reservation,
            $id_jeu,
            $data['quantite'],
            $data['prix_unitaire']
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
    die("<script>alert('Erreur : " . addslashes($e->getMessage()) . "'); window.location.href='../../index.php#section2';</script>");
}