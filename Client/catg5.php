<?php 
session_start();
require 'includes/db.php'; 

$id_categorie_actuelle = 5;
$stmt = $pdo->prepare("SELECT * FROM JEU WHERE id_categorie = ?");
$stmt->execute([$id_categorie_actuelle]);
$jeux_catg = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Battle Zone</title>
    <link rel="stylesheet" href="assets/css/games5.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/index.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="catg-page">

    <?php include 'includes/navbar.php'; ?>

    <div class="catg-header">
        <h1 class="catg-title"></h1>
        
    </div>

    <div class="blue-container">
        <div class="games-wrapper-catg">
            <div class="games-container">
                <?php foreach($jeux_catg as $jeu): 
                    $is_disponible = (strtolower($jeu['etat']) === 'disponible');
                    $card_class = $is_disponible ? "game-card" : "game-card indisponible";
                    $jeu_data = htmlspecialchars(json_encode([
                        'nom' => $jeu['nom'],
                        'description' => $jeu['description'],
                        'tarif' => $jeu['tarif'],
                        'duree_session' => $jeu['duree_session'],
                        'id_jeu' => $jeu['id_jeu'],
                        'img_jeu' => $jeu['img_jeu'],
                        'age_min' => $jeu['age_min'],
                        'capacite' => $jeu['capacite'],
                    ]), ENT_QUOTES, 'UTF-8');

                    $onclick_attr = $is_disponible
                        ? "onclick=\"openModalFromCard(this)\" data-jeu=\"$jeu_data\""
                        : "";
                ?>
                    <div class="<?php echo $card_class; ?>" 
                         style="background-image: url('<?php echo htmlspecialchars($jeu['img_jeu']); ?>');"
                         <?php echo $onclick_attr; ?>>
                        <div class="game-title">
                            <?php echo htmlspecialchars($jeu['nom']); ?>
                            <?php if(!$is_disponible): ?>
                                <br><span style="color: #ff4d4d; font-size: 13px; font-weight: normal;">Indisponible</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/modal_jeu.php'; ?>
     
    <script>
    var panierActuel = <?php
        $panier_js = [];
        if (isset($_SESSION['panier'])) {
            foreach ($_SESSION['panier'] as $ticket) {
                $panier_js[$ticket['id_jeu']] = ($panier_js[$ticket['id_jeu']] ?? 0) + $ticket['quantite'];
            }
        }
        echo empty($panier_js) ? '{}' : json_encode($panier_js);
    ?>;
    </script>
    <script src="assets/js/games.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/index.js"></script>
</body>
</html>
