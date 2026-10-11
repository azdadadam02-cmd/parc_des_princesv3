<?php
session_start();

$signin_errors = $_SESSION['signin_errors'] ?? [];
$signup_errors = $_SESSION['signup_errors'] ?? [];
unset($_SESSION['signin_errors'], $_SESSION['signup_errors']);

$mode_signup = !empty($signup_errors);

function afficher_erreurs(array $erreurs): void
{
    if (!$erreurs) {
        return;
    }
    echo '<div class="error-box">';
    foreach ($erreurs as $erreur) {
        echo '<p class="error-text">' . htmlspecialchars($erreur) . '</p>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Connexion</title>
    <link rel="stylesheet" href="Client/assets/css/login.css?v=1">
</head>
<body>

    <div class="cadrLogin<?php echo $mode_signup ? ' right-panel-active' : ''; ?>" id="mainContainer">

        <div class="formLogin">
            <img src="Client/img/logo.png" alt="Logo" class="logoLoginR">

            <form action="auth.php" method="POST" class="form1">
                <h1>Créer Compte</h1>
                <input type="text" name="nom" placeholder="Nom complet" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="mot_passe" placeholder="Mot de passe" required>
                <button type="submit" name="btn_signup">S'inscrire</button>
            </form>
        </div>

        <div class="formSingin">
            <img src="Client/img/logo.png" alt="Logo" class="logoLoginL">

            <form action="auth.php" method="POST" class="form2">
                <h1>Se Connecter</h1>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="mot_passe" placeholder="Mot de passe" required>
                <button type="submit" name="btn_login">Se connecter</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">

                <div class="overlayRight">
                    <?php afficher_erreurs($signin_errors); ?>
                    <h1>Bon retour !</h1>
                    <p>Ravi de te revoir, héros ! Connecte-toi et profite.</p>
                    <button class="ghost" id="signUpBtn">S'inscrire</button>
                </div>

                <div class="overlayLeft">
                    <?php afficher_erreurs($signup_errors); ?>
                    <h1>Rejoins-nous !</h1>
                    <p>Tu n'as pas de compte ? Crée-en un facilement.</p>
                    <button class="ghost" id="signInBtn">Se connecter</button>
                </div>

            </div>
        </div>
    </div>

    <script src="Client/assets/js/login.js"></script>
</body>
</html>
