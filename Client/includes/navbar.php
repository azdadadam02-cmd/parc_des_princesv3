<?php 
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$clientDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$CLIENT_URL = (stripos($clientDir, $docRoot) === 0) ? substr($clientDir, strlen($docRoot)) : '/Client';
$ROOT_URL = dirname($CLIENT_URL);
if ($ROOT_URL === '/' || $ROOT_URL === '\\' || $ROOT_URL === '.') { $ROOT_URL = ''; }
$unread_count = 0;
$toutes_notifs = []; 

if(isset($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];
    $nom = $_SESSION['user_nom'] ?? 'Client';
    $premiere_lettre = strtoupper(substr($nom, 0, 1));
    
    $stmt_vu_cmd = $pdo->prepare("SELECT COUNT(*) FROM COMMANDE WHERE email_client = ? AND vu = 0");
    $stmt_vu_cmd->execute([$email]);
    $unread_cmd = $stmt_vu_cmd->fetchColumn(); 

    $stmt_vu_res = $pdo->prepare("SELECT COUNT(*) FROM RESERVATION WHERE email_client = ? AND vu = 0");
    $stmt_vu_res->execute([$email]);
    $unread_res = $stmt_vu_res->fetchColumn(); 

    $stmt_vu_billet = $pdo->prepare("
        SELECT COUNT(b.id_billet) 
        FROM BILLET b 
        JOIN COMMANDE c ON b.num_commande = c.num_commande 
        WHERE c.email_client = ? AND b.vu = 0
    ");
    $stmt_vu_billet->execute([$email]);
    $unread_billet = $stmt_vu_billet->fetchColumn(); 

    $unread_count = $unread_cmd + $unread_res + $unread_billet;

    
    $stmt_cmds = $pdo->prepare("SELECT num_commande as id, num_commande as id_link, statut_commande as statut, date_commande as date_notif, montant_total as montant, 'commande' as type, '' as jeu_nom FROM COMMANDE WHERE email_client = ? ORDER BY date_commande DESC LIMIT 5");
    $stmt_cmds->execute([$email]);
    $commandes = $stmt_cmds->fetchAll(PDO::FETCH_ASSOC);

    $stmt_res = $pdo->prepare("SELECT num_reservation as id, num_reservation as id_link, statut_reservation as statut, date_creation as date_notif, montant, 'reservation' as type, '' as jeu_nom FROM RESERVATION WHERE email_client = ? ORDER BY date_creation DESC LIMIT 5");
    $stmt_res->execute([$email]);
    $reservations = $stmt_res->fetchAll(PDO::FETCH_ASSOC);

    $stmt_billets = $pdo->prepare("
        SELECT b.id_billet as id, b.id_billet as id_link, b.statut_billet as statut, b.date_creation as date_notif, b.prix_unitaire as montant, 'billet' as type, j.nom as jeu_nom 
        FROM BILLET b 
        JOIN COMMANDE c ON b.num_commande = c.num_commande 
        JOIN contient ct ON b.id_billet = ct.id_billet 
        JOIN JEU j ON ct.id_jeu = j.id_jeu 
        WHERE c.email_client = ? AND c.statut_commande = 'Payé' 
        ORDER BY b.id_billet DESC LIMIT 5
    ");
    $stmt_billets->execute([$email]);
    $billets = $stmt_billets->fetchAll(PDO::FETCH_ASSOC);

    $toutes_notifs = array_merge($commandes, $reservations, $billets);
    usort($toutes_notifs, function($a, $b) {
        return strtotime($b['date_notif']) - strtotime($a['date_notif']);
    });
}
?>

<div class="top">
    <img src="<?php echo $CLIENT_URL; ?>/img/logo.png" class="logo">

    <nav class="navbar">
        <a href="<?php echo $ROOT_URL; ?>/index.php">Accueil</a>
        <a href="https://maps.app.goo.gl/i5cZtAKhvjNpBfdm9" target="_blank" >localisation</a>
        
        <?php if(isset($_SESSION['user_email'])): ?>
            <a href="<?php echo $CLIENT_URL; ?>/actions/logout.php" style="color: #ff4d4d;">Déconnexion</a>
        <?php else: ?>
            <a href="<?php echo $ROOT_URL; ?>/login.php">Connexion</a>
        <?php endif; ?>

        <a href="javascript:void(0)" onclick="togglePanier()">Panier</a>
    </nav>
    
    <div class="panier-dropdown" id="panierDropdown">
        <span class="close-panier-btn" onclick="document.getElementById('panierDropdown').classList.remove('show');">&times;</span>
        <h4>Votre Panier</h4>
        <div class="panier-items">
            <?php 
            $total_panier = 0;
            if(isset($_SESSION['panier']) && count($_SESSION['panier']) > 0) {
                foreach($_SESSION['panier'] as $index => $ticket) {
                    $sous_total = $ticket['tarif'] * $ticket['quantite'];
                    $total_panier += $sous_total;
                    echo "<div class='panier-item'>";
                    echo "  <img src='" . htmlspecialchars($ticket['img_jeu'] ?? $ticket['img'] ?? '') . "' alt='jeu' class='panier-img'>";
                    echo "  <div class='panier-details'>";
                    echo "      <p class='panier-nom'>" . htmlspecialchars($ticket['nom']) . "</p>";
                    echo "      <p class='panier-prix'>" . $ticket['quantite'] . " x " . $ticket['tarif'] . " MAD = <strong>" . $sous_total . " MAD</strong></p>";
                    echo "  </div>";
                    echo "  <a href='#' class='btn-supprimer' data-index='" . $index . "'><i class='fas fa-trash'></i></a>";
                    echo "</div>";
                }
            } else {
                echo "<p style='font-size: 13px; text-align: center; margin-top: 20px;'>Votre panier est vide.</p>";
            }
            ?>
        </div>
        
        <?php if(isset($_SESSION['panier']) && count($_SESSION['panier']) > 0): ?>
            <div class="panier-footer">
                <p>Total: <strong><?php echo $total_panier; ?> MAD</strong></p>
                <a href="<?php echo $CLIENT_URL; ?>/valider_reservation.php" class="btn-valider">RESERVER</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if(isset($_SESSION['user_email'])): ?>
    <nav class="profile">
      
        <div class="profile-info" onclick="openNotif()" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <div class="avatar-wrapper">
                <?php if($unread_count > 0): ?>
                    <span class="red-dot" id="redDot"></span>
                <?php endif; ?>
                <div class="avatar"><?php echo $premiere_lettre; ?></div>
            </div>
            <a href="javascript:void(0)" class="user-email" style="pointer-events: none;"><?php echo $email; ?></a>
        </div>
        
       <div class="notif-dropdown" id="notifDropdown">
            <span class="close-panier-btn" onclick="closeNotif()">&times;</span>
            
            <h4>Vos Notifications</h4>

            <div class="notif-list">
                <?php 
                if(count($toutes_notifs) > 0) {
                    foreach($toutes_notifs as $notif) {
                        
                        if ($notif['type'] == 'commande') {
                            $titre = "Commande";
                            $couleur = "#5a55ff";
                            $link_type = "commande";
                        } elseif ($notif['type'] == 'reservation') {
                            $titre = "Réservation";
                            $couleur = "#ff4d4d";
                            $link_type = "reservation";
                        } else {
                            $titre = "Billet (" . htmlspecialchars($notif['jeu_nom']) . ")";
                            $couleur = "#10ac84";
                            $link_type = "billet";
                        }
                        
                        echo "<a href='" . $CLIENT_URL . "/bonde.php?id=" . $notif['id_link'] . "&type=" . $link_type . "' class='notif-item-link' style='text-decoration: none; color: inherit; display: block;'>";
                        
                        echo "<div class='notif-item' style='border-left: 4px solid ".$couleur."; padding-left: 10px; margin-bottom: 15px; cursor: pointer; transition: background 0.3s;'>";
                        
                        echo "<p style='margin: 0 0 5px 0;'><strong style='color: ".$couleur.";'>" . $titre . " N°" . $notif['id'] . "</strong></p>";
                        echo "<p style='margin: 0 0 5px 0; font-size: 14px;'>Total : <strong>" . $notif['montant'] . " MAD</strong></p>";
                        echo "<p style='margin: 0; font-size: 13px;'>Statut: <strong>" . $notif['statut'] . "</strong></p>";
                        
                        echo "</div>";
                        echo "</a>";
                    }
                } else {
                    echo "<p style='font-size: 14px; text-align: center; margin-top: 40px; margin-bottom: 40px;'>Aucune notification.</p>";
                }
                ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>
</div>

<script>
var isUserConnected = <?php echo isset($_SESSION['user_email']) ? 'true' : 'false'; ?>;
var CLIENT_URL = '<?php echo $CLIENT_URL; ?>';
var ROOT_URL = '<?php echo $ROOT_URL; ?>';

function togglePanier() {
    if (!isUserConnected) { 
        alert("Veuillez vous connecter pour accéder à votre panier.");
        window.location.href = ROOT_URL + "/login.php"; 
        return; 
    } 

    var panier = document.getElementById("panierDropdown");
    if(panier) {
        panier.classList.toggle("show");
    }

    var notif = document.getElementById("notifDropdown");
    if(notif && notif.classList.contains("show")) {
        notif.classList.remove("show");
    }
}

function openNotif() {
    var notif = document.getElementById("notifDropdown");
    if(notif && !notif.classList.contains("show")) {
        notif.classList.add("show");
    }
    
    var panier = document.getElementById("panierDropdown");
    if(panier && panier.classList.contains("show")) {
        panier.classList.remove("show");
    }

    var dot = document.getElementById("redDot");
    if(dot) {
        dot.style.display = 'none';
        fetch(CLIENT_URL + '/actions/marquer_lu.php')
            .then(response => response.text())
            .then(data => console.log("Notifications lues."))
            .catch(error => console.error(error));
    }
}

function closeNotif() {
    var notif = document.getElementById("notifDropdown");
    if(notif) {
        notif.classList.remove("show");
    }
}
</script>
