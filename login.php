<?php
session_start();

$signin_errors = $_SESSION['signin_errors'] ?? [];
$signup_errors = $_SESSION['signup_errors'] ?? [];
$signin_old = $_SESSION['signin_old'] ?? [];
$signup_old = $_SESSION['signup_old'] ?? [];

unset(
    $_SESSION['signin_errors'],
    $_SESSION['signup_errors'],
    $_SESSION['signin_old'],
    $_SESSION['signup_old']
);

$mode_signup = !empty($signup_errors) || (($_GET['form'] ?? '') === 'signup');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Connexion</title>
    <link rel="stylesheet" href="Client/assets/css/login.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="cadrLogin<?php echo $mode_signup ? ' right-panel-active' : ''; ?>" id="mainContainer">

        <!-- FORMULAIRE CRÉER COMPTE -->
        <div class="formLogin">
            <img src="Client/img/logo.png" alt="Logo" class="logoLoginR">

            <form action="auth.php" method="POST" class="form1" novalidate>
                <h1>Créer Compte</h1>

                <div class="input-group">
                    <input type="text" name="nom" placeholder="Nom complet"
                           value="<?php echo htmlspecialchars($signup_old['nom'] ?? ''); ?>"
                           class="<?php echo isset($signup_errors['nom']) ? 'input-error' : ''; ?>">
                    <?php if (isset($signup_errors['nom'])): ?>
                        <span class="field-error"><?php echo htmlspecialchars($signup_errors['nom']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <input type="email" name="email" placeholder="Email"
                           value="<?php echo htmlspecialchars($signup_old['email'] ?? ''); ?>"
                           class="<?php echo isset($signup_errors['email']) ? 'input-error' : ''; ?>">
                    <?php if (isset($signup_errors['email'])): ?>
                        <span class="field-error"><?php echo htmlspecialchars($signup_errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <input type="password" name="mot_passe" placeholder="Mot de passe"
                           class="<?php echo isset($signup_errors['mot_passe']) ? 'input-error' : ''; ?>">
                    <?php if (isset($signup_errors['mot_passe'])): ?>
                        <span class="field-error"><?php echo htmlspecialchars($signup_errors['mot_passe']); ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" name="btn_signup">S'inscrire</button>
            </form>
        </div>

        <!-- FORMULAIRE SE CONNECTER -->
        <div class="formSingin">
            <img src="Client/img/logo.png" alt="Logo" class="logoLoginL">

            <form action="auth.php" method="POST" class="form2" novalidate>
                <h1>Se Connecter</h1>

                <div class="input-group">
                    <input type="email" name="email" placeholder="Email"
                           value="<?php echo htmlspecialchars($signin_old['email'] ?? ''); ?>"
                           class="<?php echo isset($signin_errors['email']) ? 'input-error' : ''; ?>">
                    <?php if (isset($signin_errors['email'])): ?>
                        <span class="field-error"><?php echo htmlspecialchars($signin_errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <input type="password" name="mot_passe" placeholder="Mot de passe"
                           class="<?php echo isset($signin_errors['mot_passe']) ? 'input-error' : ''; ?>">
                    <?php if (isset($signin_errors['mot_passe'])): ?>
                        <span class="field-error"><?php echo htmlspecialchars($signin_errors['mot_passe']); ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" name="btn_login">Se connecter</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">

                <div class="overlayRight">
                    <h1>Bon retour !</h1>
                    <p>Ravi de te revoir, héros ! Connecte-toi et profite.</p>
                    <button class="ghost" id="signUpBtn">S'inscrire</button>
                </div>

                <div class="overlayLeft">
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