<?php 
session_start(); 
require __DIR__ . '/admin/includes/db.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Accueil</title>
    <link rel="stylesheet" href="Client/assets/css/index.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
     <?php include 'Client/includes/navbar.php'; ?>

    
    <section class="section1">
        <div class="down">
    
        </div>
        <a href="#section2" class="btn-browse">voire les tiket</a>
    </section>
<!-- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
    <section class="section2" id="section2">
        <div class="tiket">
            <!-- 1. L'menu dyal les catégories lfo9 (GROUPS, SPECIAL, JOURNE) -->
            <div class="tiket-header">
                <ul class="tiket-tabs">
                    <?php
                    try {
                        $stmt_cat = $pdo->query("SELECT * FROM CATEGORIE_TIKET ORDER BY id_categorie ASC");
                        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($categories as $index => $cat) {
                            $activeClass = ($index === 0) ? 'active' : '';
                            echo '<li class="tiket-tab ' . $activeClass . '" data-cat="' . $cat['id_categorie'] . '">' 
                                 . htmlspecialchars(strtoupper($cat['nom_categorie'])) . 
                                 '</li>';
                        }
                    } catch (PDOException $e) {
                        echo '<li class="tiket-tab active" data-cat="all">GROUPS</li>';
                        echo '<li class="tiket-tab" data-cat="all">SPECIAL</li>';
                        echo '<li class="tiket-tab" data-cat="all">JOURNE</li>';
                    }
                    ?>
                </ul>
                <div class="tiket-divider"></div>
            </div>

            <!-- 2. Les cartes dyal les tickets -->
            <div class="tiket-cards-container">
                <?php
                try {
                    $stmt_promos = $pdo->query("SELECT * FROM PROMOTION WHERE etat = 'Actif'");
                    $promotions = $stmt_promos->fetchAll(PDO::FETCH_ASSOC);
                    $first_cat_id = $categories[0]['id_categorie'] ?? null;

                    if (count($promotions) > 0) {
                        foreach ($promotions as $promo) {
                            // Nbiyyno f l'bdya ghir les tickets dyal la première catégorie (GROUPS)
                            $displayStyle = ($first_cat_id && $promo['id_categorie'] != $first_cat_id) ? 'display: none;' : 'display: flex;';
                            ?>
                            <div class="tiket-card" data-cat="<?php echo htmlspecialchars($promo['id_categorie']); ?>" style="<?php echo $displayStyle; ?>">
                                
                                <!-- L'fo9 dyal l'carte (Titre) -->
                                <div class="tiket-card-top">
                                    <h2><?php echo htmlspecialchars($promo['nom_promo']); ?></h2>
                                </div>

                                <!-- L'west dyal l'carte (Les 3 attributs b dawa2ir l'bidin) -->
                                <div class="tiket-card-body">
                                    <div class="tiket-info-row">
                                        <span class="tiket-circle"></span>
                                        <p><?php echo htmlspecialchars($promo['description_jeux'] ?? 'TOUS LES JEUX'); ?></p>
                                    </div>
                                    <div class="tiket-info-row">
                                        <span class="tiket-circle"></span>
                                        <p>VALIDE <?php echo htmlspecialchars($promo['delai_validite']); ?> JOUR(S)</p>
                                    </div>
                                    <div class="tiket-info-row">
                                        <span class="tiket-circle"></span>
                                        <p><?php echo htmlspecialchars($promo['capacite_membres']); ?> PERSONNE(S)</p>
                                    </div>
                                </div>

                                <!-- L'te7t dyal l'carte (Prix + Bouton) -->
                                <div class="tiket-card-bottom">
                                    <div class="tiket-prix"><?php echo htmlspecialchars($promo['valeur']); ?> DH</div>
                                    <a href="Client/actions/ajouter_panier_promo.php?id=<?php echo $promo['id_promo']; ?>" class="btn-achetier">ACHETER</a>
                                </div>

                            </div>
                            <?php
                        }
                    } else {
                        echo '<p style="color: #fff; font-family: \'Montserrat\', sans-serif;">Aucun ticket disponible pour le moment.</p>';
                    }
                } catch (PDOException $e) {
                    echo '<p style="color: #fff;">Erreur lors du chargement des tickets.</p>';
                }
                ?>
            </div>
             
        </div>

<!-- ss -->
    </section>
    <section class = "section2-2">
        <a href="Client/catg5.php" class="btn-browse-CAT5">DÉCOUVRIR</a> 
    </section>

    <section class="section3">
        <a href="Client/catg2.php" class="btn-browse-CAT1">DÉCOUVRIR</a>   
    </section>

    <section class="section4">
        <a href="Client/catg1.php" class="btn-browse-CAT2">DÉCOUVRIR</a>
    </section>

    <section class="section5">
        <a href="Client/catg3.php" class="btn-browse-CAT3">DÉCOUVRIR</a>
    </section>

    <section class="section6">
        <a href="Client/catg4.php" class="btn-browse-CAT4">DÉCOUVRIR</a>
    </section>

    <script src="Client/assets/js/index.js"></script>
</body>
</html>
