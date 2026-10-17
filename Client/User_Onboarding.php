<?php
session_start();

if (file_exists(__DIR__ . '/includes/db.php')) {
    require_once __DIR__ . '/includes/db.php';
} elseif (file_exists(__DIR__ . '/../admin/includes/db.php')) {
    require_once __DIR__ . '/../admin/includes/db.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Guide & Aide (Onboarding)</title>
    <!-- CSS dyal Navbar w Panier -->
    <link rel="stylesheet" href="assets/css/index.css?v=<?php echo time(); ?>">
    <!-- CSS dyal User Onboarding -->
    <link rel="stylesheet" href="assets/css/User_Onboarding.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- 1. HERO HEADER -->
    <header class="onboard-hero">
        <div class="onboard-hero-content">
            <span class="onboard-badge"><i class="fas fa-compass"></i> GUIDE UTILISATEUR</span>
            <h1>COMMENT RÉSERVER AU <span>PARC DES PRINCES</span> ?</h1>
            <p>
                Suivez ce guide étape par étape pour choisir vos packs, gérer votre panier, 
                valider votre réservation et suivre son statut en toute simplicité.
            </p>
            <div class="onboard-hero-btns">
                <a href="#guide-timeline" class="btn-onboard-primary">COMMENCER LE GUIDE <i class="fas fa-arrow-down"></i></a>
                <a href="../index.php#section2" class="btn-onboard-outline">VOIR LES TICKETS</a>
            </div>
        </div>
    </header>

    <!-- 2. VERTICAL TIMELINE (LES 4 ÉTAPES AVEC IMAGES) -->
    <section class="onboard-timeline-section" id="guide-timeline">
        <div class="timeline-container">

            <!-- ÉTAPE 1 : CHOISIR UN TICKET OU UN JEU -->
            <div class="timeline-item">
                <div class="timeline-marker">1</div>
                <div class="timeline-content">
                    <div class="timeline-text">
                        <span class="step-tag">ÉTAPE 01 • SÉLECTION</span>
                        <h2>EXPLOREZ LES CATÉGORIES & CHOISISSEZ VOTRE PACK</h2>
                        <p>
                            Rendez-vous dans la section des tickets sur la page d'accueil et filtrez les offres selon votre besoin : 
                            <strong>GROUPS</strong> (en famille ou entre amis), <strong>SPECIAL</strong> (packs thématiques) ou <strong>JOURNE</strong> (pass journée).
                        </p>
                        <ul class="step-highlights">
                            <li>
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Bouton DÉTAILS :</strong> Cliquez dessus pour afficher la description complète et la liste exacte des jeux inclus dans le billet.
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-ban"></i>
                                <div>
                                    <strong>Filtre "Indisponible" :</strong> Si l'un des jeux inclus dans un pack n'a plus de places disponibles (capacité épuisée), la carte s'assombrit automatiquement avec la mention <em>Indisponible</em>.
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Tswira dyal l-5otwa 1 -->
                    <div class="timeline-media">
                        <div class="media-Browser-bar">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                            <span class="browser-title">Aperçu : Sélection des Tickets</span>
                        </div>
                        <div class="media-frame">
                            <!-- Beddel src b tswira dyalk f Client/img/ -->
                            <img src="img/onboarding_step1.png" alt="Étape 1 - Choisir un Ticket" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="media-fallback">
                                <i class="fas fa-ticket-alt"></i>
                                <span>Image Étape 1 : Sélection des Packs (GROUPS / SPECIAL / JOURNE)</span>
                                <small>Placez votre capture dans : <code>Client/img/onboarding_step1.png</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 2 : GÉRER LE PANIER -->
            <div class="timeline-item">
                <div class="timeline-marker">2</div>
                <div class="timeline-content reverse">
                    <div class="timeline-text">
                        <span class="step-tag">ÉTAPE 02 • PANIER</span>
                        <h2>VÉRIFIEZ ET MODIFIEZ VOTRE PANIER</h2>
                        <p>
                            Dès que vous cliquez sur <strong>ACHETER</strong>, votre pack ou jeu est ajouté instantanément à votre panier situé dans la barre de navigation en haut.
                        </p>
                        <ul class="step-highlights">
                            <li>
                                <i class="fas fa-plus-circle"></i>
                                <div>
                                    <strong>Augmenter la quantité :</strong> Cliquez à nouveau sur <em>ACHETER</em> sur le même pack pour augmenter le nombre de billets (tant que le stock des jeux le permet).
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-trash-alt"></i>
                                <div>
                                    <strong>Supprimer un article :</strong> Ouvrez le menu <em>Panier</em> en haut et cliquez sur l'icône de corbeille rouge à droite de l'article pour le retirer immédiatement.
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Passer à la réservation :</strong> Vérifiez le montant total en MAD puis cliquez sur le bouton bleu <strong>RESERVER</strong>.
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Tswira dyal l-5otwa 2 -->
                    <div class="timeline-media">
                        <div class="media-Browser-bar">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                            <span class="browser-title">Aperçu : Gestion du Panier</span>
                        </div>
                        <div class="media-frame">
                            <img src="img/onboarding_step2.png" alt="Étape 2 - Gérer le Panier" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="media-fallback">
                                <i class="fas fa-shopping-basket"></i>
                                <span>Image Étape 2 : Fenêtre Votre Panier & Bouton RESERVER</span>
                                <small>Placez votre capture dans : <code>Client/img/onboarding_step2.png</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 3 : FORMULAIRE DE VALIDATION -->
            <div class="timeline-item">
                <div class="timeline-marker">3</div>
                <div class="timeline-content">
                    <div class="timeline-text">
                        <span class="step-tag">ÉTAPE 03 • VALIDATION</span>
                        <h2>REMPLISSEZ VOS INFORMATIONS DE VISITE</h2>
                        <p>
                            Pour finaliser la réservation, vous devez être connecté à votre compte client. Sur la page <strong>Détails de votre Réservation</strong>, vérifiez le récapitulatif et complétez le formulaire :
                        </p>
                        <ul class="step-highlights">
                            <li>
                                <i class="fas fa-phone-alt"></i>
                                <div>
                                    <strong>Numéro de téléphone :</strong> Doit comporter exactement <strong>10 chiffres</strong> (ex: 0612345678).
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-user-check"></i>
                                <div>
                                    <strong>Âge minimum & CIN :</strong> Vous devez avoir <strong>au moins 16 ans</strong> et saisir un numéro de <strong>CIN valide</strong>.
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <strong>Date et heure de visite :</strong> Choisissez une date future (jusqu'à <strong>3 mois maximum</strong> à l'avance).
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Tswira dyal l-5otwa 3 -->
                    <div class="timeline-media">
                        <div class="media-Browser-bar">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                            <span class="browser-title">Aperçu : Validation de la Réservation</span>
                        </div>
                        <div class="media-frame">
                            <img src="img/onboarding_step3.png" alt="Étape 3 - Formulaire de Réservation" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="media-fallback">
                                <i class="fas fa-clipboard-check"></i>
                                <span>Image Étape 3 : Formulaire (Téléphone, Date naissance, CIN, Date visite)</span>
                                <small>Placez votre capture dans : <code>Client/img/onboarding_step3.png</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 4 : QUE SE PASSE-T-IL APRÈS ? (STATUT EN ATTENTE & BON DE RÉSERVATION) -->
            <div class="timeline-item">
                <div class="timeline-marker">4</div>
                <div class="timeline-content reverse">
                    <div class="timeline-text">
                        <span class="step-tag step-tag-alert">ÉTAPE 04 • SUIVI & BON DE RÉSERVATION</span>
                        <h2>QUE SE PASSE-T-IL APRÈS VOTRE CONFIRMATION ?</h2>
                        <p>
                            Une fois votre réservation confirmée, vos places sont immédiatement réservées dans le système et une notification apparaît dans votre profil en haut à droite.
                        </p>

                        <!-- Box d'explication importante bach mayshabch l-app khassra -->
                        <div class="status-reassurance-box">
                            <h4><i class="fas fa-shield-alt"></i> Pourquoi le statut affiche-t-il "en attente" ?</h4>
                            <p>
                                Pas d'inquiétude, <strong>l'application fonctionne normalement !</strong> Le statut de
                                <span class="badge-inline-attente">en attente</span> signifie simplement que votre demande a bien été enregistrée 
                                et qu'elle attend la validation finale par un agent du parc.
                            </p>
                        </div>

                        <ul class="step-highlights">
                            <li>
                                <i class="fas fa-bell"></i>
                                <div>
                                    <strong>Notifications (Point Rouge) :</strong> Cliquez sur votre profil en haut à droite pour voir vos réservations et leur statut en temps réel.
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-file-invoice-dollar"></i>
                                <div>
                                    <strong>Bon de Réservation & Économies :</strong> En cliquant sur la notification, vous accédez à votre <em>Bon de Réservation</em> détaillant le prix réel de chaque jeu et le <strong>Total Payé</strong> réduit grâce à votre Pack !
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Tswira dyal l-5otwa 4 -->
                    <div class="timeline-media">
                        <div class="media-Browser-bar">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                            <span class="browser-title">Aperçu : Notifications & Bon de Réservation</span>
                        </div>
                        <div class="media-frame">
                            <img src="img/onboarding_step4.png" alt="Étape 4 - Bon de réservation et Notifications" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="media-fallback">
                                <i class="fas fa-receipt"></i>
                                <span>Image Étape 4 : Notification "en attente" & Bon de Réservation</span>
                                <small>Placez votre capture dans : <code>Client/img/onboarding_step4.png</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 3. GUIDE DES STATUTS -->
    <section class="onboard-status-section">
        <div class="status-container">
            <h2>COMPRENDRE LES STATUTS DE VOTRE RÉSERVATION</h2>
            <p class="status-subtitle">Voici la signification des différents états que vous pouvez rencontrer sur la plateforme :</p>

            <div class="status-cards-grid">
                <div class="status-card">
                    <span class="status-pill attente">EN ATTENTE</span>
                    <h3>Demande Enregistrée</h3>
                    <p>Votre réservation est bien reçue et vos places sont bloquées. Un agent du parc va vérifier et valider votre dossier.</p>
                </div>

                <div class="status-card">
                    <span class="status-pill valide">VALIDÉE / PAYÉ</span>
                    <h3>Réservation Confirmée</h3>
                    <p>Votre réservation a été approuvée ! Présentez simplement votre Bon de Réservation (Document N°) à l'entrée du parc.</p>
                </div>

                <div class="status-card">
                    <span class="status-pill indispo">INDISPONIBLE</span>
                    <h3>Capacité Atteinte</h3>
                    <p>Le quota de billets pour l'un des jeux inclus est atteint pour le moment. Choisissez un autre pack disponible.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. FAQ INTERACTIVE -->
    <section class="onboard-faq-section">
        <div class="faq-container">
            <h2>QUESTIONS FRÉQUENTES (FAQ)</h2>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Pourquoi ma réservation reste-t-elle "en attente" après la confirmation ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>
                        Lorsque vous confirmez votre réservation en ligne, elle est envoyée directement à notre équipe avec le statut <strong>"en attente"</strong>. 
                        L'application n'est ni bloquée ni en panne : vos places sont déjà déduites du stock pour vous les garantir. Dès qu'un agent valide votre demande, vous recevrez une notification (point rouge sur votre profil).
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Pourquoi le prix des jeux dans le Bon de Réservation est différent du Total Payé ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>
                        Dans votre <strong>Bon de Réservation</strong>, nous affichons le tarif unitaire réel de chaque jeu inclus dans votre pack pour vous montrer la valeur réelle des activités, 
                        tandis que le <strong>TOTAL PAYÉ</strong> en bas correspond uniquement au tarif promotionnel du Pack que vous avez choisi. Cela vous permet de voir clairement combien vous avez économisé !
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Pourquoi certains packs ou jeux deviennent-ils noirs avec la mention "Indisponible" ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>
                        Chaque jeu du parc possède une capacité maximale de places. Si un seul jeu inclus dans un Pack n'a plus assez de places disponibles, 
                        le pack entier passe automatiquement en mode <strong>Indisponible</strong> pour éviter toute réservation incomplète.
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Quelles sont les conditions pour pouvoir valider une réservation ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>
                        Vous devez être connecté avec un compte client, avoir au moins <strong>16 ans</strong>, fournir un numéro de téléphone marocain valide à <strong>10 chiffres</strong>, 
                        un numéro de <strong>CIN</strong> valide, et choisir une date de visite comprise entre aujourd'hui et 3 mois maximum.
                    </p>
                </div>
            </div>

            <div class="onboard-cta-bottom">
                <h3>PRÊT À VIVRE L'EXPÉRIENCE ?</h3>
                <a href="../index.php#section2" class="btn-onboard-primary">CHOISIR MON TICKET MAINTENANT</a>
            </div>
        </div>
    </section>

    <script src="assets/js/index.js?v=<?php echo time(); ?>"></script>
    <script>
        function toggleFaq(btn) {
            const item = btn.parentElement;
            item.classList.toggle('open');
        }
    </script>
</body>
</html>