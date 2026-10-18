<?php
session_start();
require 'includes/db.php'; 

if (!isset($_SESSION['user_email'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    die("<h2 style='color:white; text-align:center;'>Erreur : Informations manquantes.</h2>");
}

$id = intval($_GET['id']);
$type = $_GET['type'];
$email_client = $_SESSION['user_email'];
$details = null;
$tickets = []; 

if ($type === 'commande') {
    // نجيبو تفاصيل الكوموند
    $stmt = $pdo->prepare("
        SELECT c.*, u.telephone 
        FROM COMMANDE c 
        LEFT JOIN UTILISATEUR u ON u.email = c.email_client 
        WHERE c.num_commande = ? AND c.email_client = ?
    ");
    $stmt->execute([$id, $email_client]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($details) {
        // إذا كانت الكوموند تخلصات (payée)، الألعاب غايكونو فـ جدول BILLET
        if (strtolower($details['statut_commande']) === 'payée') {
            $stmt_t = $pdo->prepare("
                SELECT j.nom, b.quantite, b.prix_unitaire, (b.quantite * b.prix_unitaire) as sous_total 
                FROM BILLET b 
                JOIN contient c ON b.id_billet = c.id_billet 
                JOIN JEU j ON c.id_jeu = j.id_jeu 
                WHERE b.num_commande = ?
            ");
            $stmt_t->execute([$id]);
            $tickets = $stmt_t->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // إذا كانت باقا en attente، الألعاب مازالين غير فـ جدول choisir من خلال num_reservation
            if (!empty($details['num_reservation'])) {
                $stmt_t = $pdo->prepare("
                    SELECT j.nom, c.quantite, c.prix_unitaire, (c.quantite * c.prix_unitaire) as sous_total 
                    FROM choisir c 
                    JOIN JEU j ON c.id_jeu = j.id_jeu 
                    WHERE c.num_reservation = ?
                ");
                $stmt_t->execute([$details['num_reservation']]);
                $tickets = $stmt_t->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

    $titre_page = "Bon de Commande";
    $couleur_theme = "#5a55ff";
} 
elseif ($type === 'reservation') {
    // نجيبو تفاصيل الرزرفاسيون
    $stmt = $pdo->prepare("
        SELECT r.*, u.telephone 
        FROM RESERVATION r 
        LEFT JOIN UTILISATEUR u ON u.email = r.email_client 
        WHERE r.num_reservation = ? AND r.email_client = ?
    ");
    $stmt->execute([$id, $email_client]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // نجيبو الألعاب من جدول choisir
    $stmt_t = $pdo->prepare("
        SELECT j.nom, c.quantite, c.prix_unitaire, (c.quantite * c.prix_unitaire) as sous_total 
        FROM choisir c 
        JOIN JEU j ON c.id_jeu = j.id_jeu 
        WHERE c.num_reservation = ?
    ");
    $stmt_t->execute([$id]);
    $tickets = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

    $titre_page = "Bon de Réservation";
    $couleur_theme = "#ff4d4d";
}
elseif ($type === 'billet') {
    $stmt = $pdo->prepare("
        SELECT b.*, u.telephone, c.email_client, j.nom as jeu_nom, j.duree_session 
        FROM BILLET b
        JOIN COMMANDE c ON b.num_commande = c.num_commande
        JOIN UTILISATEUR u ON u.email = c.email_client
        JOIN contient ct ON b.id_billet = ct.id_billet
        JOIN JEU j ON ct.id_jeu = j.id_jeu
        WHERE b.id_billet = ? AND c.email_client = ?
    ");
    $stmt->execute([$id, $email_client]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        $titre_page = "Billet : " . strtoupper($details['jeu_nom']);
        $couleur_theme = "#10ac84";
        $tickets = [[
            'nom' => $details['jeu_nom'],
            'quantite' => $details['quantite'],
            'prix_unitaire' => $details['prix_unitaire'],
            'sous_total' => $details['quantite'] * $details['prix_unitaire']
        ]];
    }
}

if (!$details) {
    die("<h2 style='color:red; text-align:center;'>Document introuvable ou accès refusé.</h2>");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - <?php echo $titre_page; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/bonde.css?v=<?php echo time(); ?>">

    <style>
        :root {
            --theme-color: <?php echo $couleur_theme; ?>;
        }
    </style>
</head>
<body>

    <div class="bonde-container">
        <div class="bonde-header">
            <img src="img/logo.png" class="logo">
            <h1><?php echo $titre_page; ?></h1>
            <p>
                <?php 
                if ($type === 'billet') {
                    echo "Document N° " . $details['num_commande'] . " - Billet N° " . $id;
                } else {
                    echo "Document N° " . $id;
                }
                ?>
            </p>
        </div>

        <div class="bonde-body">
            
            <div class="info-row">
                <span class="info-label">Client :</span>
                <span class="info-value">
                    <?php 
                    $nom = isset($_SESSION['user_nom']) ? $_SESSION['user_nom'] : 'Client';
                    echo htmlspecialchars($nom) . " <span style='color:#aaa; font-weight:normal; font-size:13px;'>(" . htmlspecialchars($email_client) . ")</span>"; 
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Téléphone :</span>
                <span class="info-value">
                    <?php echo isset($details['telephone']) && !empty($details['telephone']) ? htmlspecialchars($details['telephone']) : "<span style='color:#888; font-style:italic;'>Non renseigné</span>"; ?>
                </span>
            </div>

            <?php if ($type === 'billet'): ?>
                <div class="info-row">
                    <span class="info-label">Date Réservée (Début) :</span>
                    <span class="info-value" style="color: #10ac84;">
                        <?php echo !empty($details['date_creation']) ? htmlspecialchars($details['date_creation']) : "Non définie"; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de Fin :</span>
                    <span class="info-value" style="color: #ff4d4d;">
                        <?php echo !empty($details['date_fin']) ? htmlspecialchars($details['date_fin']) : "Non définie"; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Durée de Session :</span>
                    <span class="info-value">
                        <?php echo !empty($details['duree_session']) ? htmlspecialchars($details['duree_session']) . " minutes" : "Non définie"; ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="info-row">
                    <span class="info-label">Date de création :</span>
                    <span class="info-value">
                        <?php echo isset($details['date_commande']) ? $details['date_commande'] : (isset($details['date_creation']) ? $details['date_creation'] : ""); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($type === 'commande'): ?>
    <div class="info-row">
        <span class="info-label">Période de paiement :</span>
        <span class="info-value">
            <?php
            $dateDebut = $details['date_commande']
                ?? $details['date_creation']
                ?? null;

            $dateFin = $details['date_limite_paiement']
                ?? $details['date_visite']
                ?? null;

            if (!empty($dateDebut) && !empty($dateFin)) {
                echo htmlspecialchars(
                    date('d/m/Y', strtotime($dateDebut))
                    . ' vers '
                    . date('d/m/Y', strtotime($dateFin))
                );
            } else {
                echo 'Période non définie';
            }
            ?>
        </span>
    </div>
<?php endif; ?>

            <div class="info-row">
                <span class="info-label">Statut :</span>
                <span class="info-value">
                    <span class="status-badge">
                        <?php 
                        if ($type === 'billet') {
                            echo htmlspecialchars($details['statut_billet']);
                        } else {
                            echo isset($details['statut_commande']) ? htmlspecialchars($details['statut_commande']) : htmlspecialchars($details['statut_reservation']); 
                        }
                        ?>
                    </span>
                </span>
            </div>

            <div class="tickets-section" style="margin-top: 30px; margin-bottom: 20px;">
                <h3 style="color: var(--theme-color); font-family: 'Bebas Neue', sans-serif; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; font-size: 24px;">Détails des achats</h3>
                
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.2); color: #ccc; text-align: left;">
                            <th style="padding: 10px 5px;">Jeu / Activité</th>
                            <th style="padding: 10px 5px; text-align: center;">Qté</th>
                            <th style="padding: 10px 5px; text-align: right;">Prix Unité</th>
                            <th style="padding: 10px 5px; text-align: right;">Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($tickets && count($tickets) > 0): ?>
                            <?php foreach($tickets as $t): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px 5px; color: #fff;"><?php echo htmlspecialchars($t['nom']); ?></td>
                                <td style="padding: 12px 5px; text-align: center; color: #fff;"><?php echo $t['quantite']; ?></td>
                                <td style="padding: 12px 5px; text-align: right; color: #ccc;"><?php echo number_format($t['prix_unitaire'], 2); ?> DH</td>
                                <td style="padding: 12px 5px; text-align: right; font-weight: bold; color: #fff;"><?php echo number_format($t['sous_total'], 2); ?> DH</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="padding: 15px; text-align: center; color: #888;">Aucun détail de ticket trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-row">
                <span>TOTAL PAYÉ :</span>
                <span>
                    <?php 
                    if ($type === 'billet') {
                        $montant = $details['quantite'] * $details['prix_unitaire'];
                    } else {
                        $montant = isset($details['montant_total']) ? $details['montant_total'] : (isset($details['montant']) ? $details['montant'] : 0);
                    }
                    echo number_format($montant, 2) . " MAD"; 
                    ?>
                </span>
            </div>
        </div>

        <a href="../index.php" class="btn-retour"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
        
        <button onclick="window.print()" class="btn-retour btn-print">
            <i class="fas fa-print"></i> Imprimer le document
        </button>

    </div>

</body>
</html>