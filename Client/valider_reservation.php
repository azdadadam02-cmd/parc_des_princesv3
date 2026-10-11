<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['panier']) || count($_SESSION['panier']) == 0) {
    header("Location: ../index.php");
    exit();
}

$email_client = $_SESSION['user_email'];
$total_montant = 0;
$total_personnes = 0;

foreach ($_SESSION['panier'] as $ticket) {
    $total_montant += ($ticket['tarif'] * $ticket['quantite']);
    $total_personnes += $ticket['quantite'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Validation Réservation</title>
    <link rel="stylesheet" href="assets/css/index.css?v=<?php echo time(); ?>">
    <style>
        
        body {
            background-color: #252549;
            color: #000000;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding-top: 100px;
        }
        
        .reservation-container {
            background-color: rgba(255, 255, 255, 0.95);
            border: 1px solid #000000;
            border-radius: 15px;
            padding: 40px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }

        .reservation-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 40px;
            color: #010102;
            text-align: center;
            margin-bottom: 30px;
            letter-spacing: 2px;
        }

        .recap-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .recap-table th, .recap-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .recap-table th {
            color: #000000;
            text-transform: uppercase;
        }

        .total-row {
            font-size: 20px;
            font-weight: bold;
            color: #030303;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
            color: #000000;
        }

        .form-group input[type="datetime-local"] {
            width: 100%;
            padding: 15px;
            background-color: #ffffff;
            
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            color: #000000;
            outline: none;
        }

        .btn-confirmer {
            background-color: #9997cb;
            color: #fff;
            border: none;
            padding: 15px 30px;
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            text-transform: uppercase;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-confirmer:hover {
            background-color: #3f3bc2;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="reservation-container">
        <h2 class="reservation-title">Détails de votre Réservation</h2>
        
        <table class="recap-table">
            <thead>
                <tr>
                    <th>Jeu / Activité</th>
                    <th>Quantité (Tickets)</th>
                    <th>Prix Unitaire</th>
                    <th>Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['panier'] as $ticket): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ticket['nom']); ?></td>
                    <td><?php echo $ticket['quantite']; ?></td>
                    <td><?php echo $ticket['tarif']; ?> MAD</td>
                    <td><?php echo $ticket['tarif'] * $ticket['quantite']; ?> MAD</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Total à payer :</td>
                    <td><?php echo $total_montant; ?> MAD</td>
                </tr>
            </tbody>
        </table>

        <form action="actions/traitement_reservation.php" method="POST">
            <input type="hidden" name="montant" value="<?php echo $total_montant; ?>">
            <input type="hidden" name="nb_personnes" value="<?php echo $total_personnes; ?>">
            
           <div class="form-group">
                <label for="date_visite">Choisissez la date et l'heure de votre visite :</label>
                <?php 
                    $min_date = date('Y-m-d\TH:i'); 
                    $max_date = date('Y-m-d\TH:i', strtotime('+3 months')); 
                ?>
                <div class="form-group">
                <label for="telephone">Numéro de téléphone :</label>
                <input type="text" id="telephone" name="telephone" 
                       pattern="[0-9]{10}" 
                       minlength="10" 
                       maxlength="10" 
                       required 
                       placeholder="Ex: 0612345678" 
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                       style="width: 100%; padding: 15px; background-color: #ffffff; border: 1px solid #ccc; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 16px; color: #000000; outline: none; box-sizing: border-box;">
            </div>
                <input type="datetime-local" id="date_visite" name="date_visite" required 
                       min="<?php echo $min_date; ?>" 
                       max="<?php echo $max_date; ?>">
            </div>

            <button type="submit" class="btn-confirmer">CONFIRMER LA RÉSERVATION</button>
        </form>
    </div>

</body>
</html>
