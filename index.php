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
            <?php
            try {
                $stmt_aff = $pdo->query("SELECT * FROM affiche ORDER BY id_affiche DESC");
                $affiches = $stmt_aff->fetchAll(PDO::FETCH_ASSOC);
                $nb_affiches = count($affiches);
            } catch (PDOException $e) {
                $affiches = [];
                $nb_affiches = 0;
            }
            ?>

            <?php if ($nb_affiches > 0): ?>
                <div class="affiche-slider" id="afficheSlider">
                    <!-- L-piste li kat-scrolla -->
                    <div class="affiche-track" id="afficheTrack">
                        <?php foreach ($affiches as $aff): ?>
                            <div class="affiche-slide">
                                <img src="<?php echo htmlspecialchars($aff['img_affiche']); ?>" alt="Affiche Promotionnelle">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Ila kano ktr mn tswira w7da, nbiyyno Next > w Prev < -->
                    <?php if ($nb_affiches > 1): ?>
                        <button type="button" class="affiche-btn prev" id="btnPrevAffiche" aria-label="Précédent">
                            &#10094;
                        </button>
                        <button type="button" class="affiche-btn next" id="btnNextAffiche" aria-label="Suivant">
                            &#10095;
                        </button>

                        <!-- N9i6at (Dots) lte7t -->
                        <div class="affiche-dots" id="afficheDots">
                            <?php for ($i = 0; $i < $nb_affiches; $i++): ?>
                                <span class="affiche-dot <?php echo ($i === 0) ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="affiche-empty">
                    <p>Aucune affiche disponible pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
        <a href="#section2" class="btn-browse">voire les tiket</a>
    </section>
<!-- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
    <section class="section2" id="section2">
        <div class="tiket">
            <!-- 1. L'menu dyal les catégories lfo9 (GROUPS, SPECIAL, JOURNE) -->
            <div class="tiket-header">
                <ul class="tiket-tabs">
                    <li class="tiket-tab active" data-cat="GROUPS">GROUPS</li>
                    <li class="tiket-tab" data-cat="SPECIAL">SPECIAL</li>
                    <li class="tiket-tab" data-cat="JOURNE">JOURNE</li>
                </ul>
                <div class="tiket-divider"></div>
            </div>

            <!-- 2. Les cartes dyal les tickets -->
            <div class="tiket-cards-container">
                <?php
                try {
                    // Njibo ga3 les noms dyal les jeux mn table JEU bach nbiyynohom f Modal Détails
                    $jeux_map = [];
                    $stmt_j = $pdo->query("SELECT id_jeu, nom FROM JEU");
                    while ($row_j = $stmt_j->fetch(PDO::FETCH_ASSOC)) {
                        $jeux_map[$row_j['id_jeu']] = $row_j['nom'];
                    }

                    $stmt_promos = $pdo->query("SELECT * FROM promotion_billet WHERE etat = 'Actif'");
                    $promotions = $stmt_promos->fetchAll(PDO::FETCH_ASSOC);

                    if (count($promotions) > 0) {
                        foreach ($promotions as $promo) {
                            $cat_actuelle = strtoupper(trim($promo['categorie_ticket']));
                            $displayStyle = ($cat_actuelle === 'GROUPS') ? 'display: flex;' : 'display: none;';

                            // N7wlo jeux_inclus (JSON) l liste dyal smiyat l'al3ab bach tban f Détails
                            $jeux_inclus_arr = json_decode($promo['jeux_inclus'] ?? '{}', true);
                            $liste_jeux_details = [];
                            if (is_array($jeux_inclus_arr)) {
                                foreach ($jeux_inclus_arr as $id_j => $qte_j) {
                                    $nom_j = $jeux_map[$id_j] ?? ("Jeu #" . $id_j);
                                    $liste_jeux_details[] = $nom_j . " : " . $qte_j . " Ticket(s)";
                                }
                            }
                            $jeux_details_str = implode('|', $liste_jeux_details);
                            ?>
                            <div class="tiket-card" data-cat="<?php echo htmlspecialchars($cat_actuelle); ?>" style="<?php echo $displayStyle; ?>">
                                
                                <!-- L'fo9 dyal l'carte : Date Fin f l'blasa li dwwrti 3liha bl 7mer + Titre -->
                                <div class="tiket-card-top">
                                    <?php if (!empty($promo['date_fin'])): ?>
                                        <span class="tiket-date-fin-badge">
                                            FIN : <?php echo htmlspecialchars($promo['date_fin']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <h2><?php echo htmlspecialchars($promo['nom_promo']); ?></h2>
                                </div>

                                <!-- L'west dyal l'carte b les 3 icônes bleus -->
                                <div class="tiket-card-body">
                                    
                                    <!-- 1. Les catégories (Icône Grid + Loupe b zr9) -->
                                    <div class="tiket-info-row">
                                        <span class="tiket-circle">
                                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
                                                <rect x="3" y="3" width="7" height="7" rx="2" fill="#ffffff"/>
                                                <rect x="13" y="3" width="7" height="7" rx="2" fill="#ffffff"/>
                                                <rect x="3" y="13" width="7" height="7" rx="2" fill="#ffffff"/>
                                                <circle cx="16" cy="16" r="3.2" stroke="#ffffff" stroke-width="2"/>
                                                <line x1="18.5" y1="18.5" x2="21" y2="21" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        <p><?php echo htmlspecialchars($promo['type_promo'] ?? 'CLASSIC + PS5'); ?></p>
                                    </div>

                                    <!-- 2. Le délai (Icône Horloge b zr9) -->
                                    <div class="tiket-info-row">
                                        <span class="tiket-circle">
                                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
                                                <line x1="12" y1="2.5" x2="12" y2="4.5" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"/>
                                                <line x1="12" y1="19.5" x2="12" y2="21.5" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"/>
                                                <line x1="2.5" y1="12" x2="4.5" y2="12" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"/>
                                                <line x1="19.5" y1="12" x2="21.5" y2="12" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"/>
                                                <path d="M12 6.5V12.5H16.5" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <p>VALIDITÉ : <?php echo htmlspecialchars($promo['delai'] ?? '24/H'); ?></p>
                                    </div>

                                    <!-- 3. La capacité (Icône Profile b zr9) -->
                                    <div class="tiket-info-row">
                                        <span class="tiket-circle">
                                            <svg viewBox="0 0 24 24" width="22" height="22" fill="#ffffff">
                                                <circle cx="12" cy="8.5" r="4"/>
                                                <path d="M4.5 20.5C4.5 16.5 7.8 14 12 14C16.2 14 19.5 16.5 19.5 20.5"/>
                                            </svg>
                                        </span>
                                        <p><?php echo htmlspecialchars($promo['capacite_membres']); ?> PERSONNE(S)</p>
                                    </div>

                                </div>

                                <!-- L'te7t dyal l'carte : Prix + Bouton DÉTAILS + Bouton ACHETER -->
                                <div class="tiket-card-bottom">
                                    <div class="tiket-prix"><?php echo htmlspecialchars($promo['valeur']); ?> DH</div>
                                    <div class="tiket-actions">
                                        <button type="button" 
                                                class="btn-details"
                                                data-titre="<?php echo htmlspecialchars($promo['nom_promo']); ?>"
                                                data-desc="<?php echo htmlspecialchars($promo['description'] ?? 'Aucune description disponible.'); ?>"
                                                data-jeux="<?php echo htmlspecialchars($jeux_details_str); ?>"
                                                onclick="ouvrirModalDetails(this)">
                                            DÉTAILS
                                        </button>
                                        <a href="Client/actions/ajouter_panier_promo.php?id=<?php echo $promo['id_promo']; ?>" class="btn-achetier">ACHETER</a>
                                    </div>
                                </div>

                            </div>
                            <?php
                        }
                    } else {
                        echo '<p style="color: #fff; font-family: \'Montserrat\', sans-serif;">Aucun ticket disponible pour le moment.</p>';
                    }
                } catch (PDOException $e) {
                    echo '<p style="color: #fff;">Erreur lors du chargement des tickets: ' . $e->getMessage() . '</p>';
                }
                ?>
            </div>
        </div>

        <!-- 3. Modal (Popup) dyal DÉTAILS -->
        <div id="ticketDetailsModal" class="ticket-modal-overlay" onclick="fermerModalDetails(event)">
            <div class="ticket-modal-box">
                <span class="ticket-modal-close" onclick="document.getElementById('ticketDetailsModal').classList.remove('show')">&times;</span>
                <h3 id="modalTicketTitre">TITRE DU TICKET</h3>
                <p id="modalTicketDesc" class="ticket-modal-desc"></p>
                <h4>Jeux inclus dans ce billet :</h4>
                <ul id="modalTicketJeux" class="ticket-modal-list"></ul>
            </div>
        </div>
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

    <script src="Client/assets/js/index.js?v=<?php echo time(); ?>"></script>
</body>
</html>
