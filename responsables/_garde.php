<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../admin/includes/db.php';

const ROLE_RESPONSABLE = 'responsable';

$email_resp = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
$role_session = strtolower(trim((string)($_SESSION['user_role'] ?? $_SESSION['role'] ?? '')));

if ($email_resp === '' || $role_session !== ROLE_RESPONSABLE) {
    header('Location: ../login.php');
    exit;
}

$st = $pdo->prepare("SELECT 1 FROM responsable_de_jeu WHERE email = ? AND LOWER(COALESCE(statut_responsable, 'actif')) = 'actif'");
$st->execute([$email_resp]);
if (!$st->fetch()) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?form=login');
    exit;
}



function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function csrf_token(string $nom): string {
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_champ(string $nom): string {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token($nom)) . '">';
}
function csrf_ok(string $nom): bool {
    return isset($_POST['csrf'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf']);
}

function ucfirst_fr(string $s): string {
    return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}

function classe_etat_jeu(?string $etat): string {
    $e = mb_strtolower(trim((string)$etat));
    if (in_array($e, ['disponible', 'en fonctionnement'], true)) return 'is-ok';
    if ($e === 'en maintenance')                                  return 'is-warn';
    return 'is-danger';
}

function classe_statut_reservation(?string $s): string {
    switch (mb_strtolower(trim((string)$s))) {
        case 'confirmée': return 'is-ok';
        case 'payée':     return 'is-info';
        case 'utilisée':  return 'is-muted';
        case 'annulée':   return 'is-danger';
        default:          return 'is-warn';   // en attente
    }
}

function jeu_du_responsable(PDO $pdo, int $id_jeu, string $email): ?array {
    $st = $pdo->prepare(
        "SELECT j.*, c.nom_categorie
         FROM JEU j JOIN CATEGORIE c ON c.id_categorie = j.id_categorie
         WHERE j.id_jeu = ? AND j.email_responsable = ?"
    );
    $st->execute([$id_jeu, $email]);
    return $st->fetch() ?: null;
}

function mes_jeux(PDO $pdo, string $email): array {
    $st = $pdo->prepare("SELECT id_jeu, nom FROM JEU WHERE email_responsable = ? ORDER BY nom");
    $st->execute([$email]);
    return $st->fetchAll();
}



function entete(string $titre, bool $afficherTitre = true): void {
    $page = basename($_SERVER['SCRIPT_NAME']);
    if ($page === 'jeu.php') $page = 'index.php';

    $liens = [
        'index.php'          => 'Mes jeux',
        'signaler_panne.php' => 'Signaler une panne',
        'depenses.php'       => 'Dépenses',
        'planning.php'       => 'Planning',
    ];
    $email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? 'Compte';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= h($titre) ?> - Parc des Princes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="row align-center">

            <div class="col-6 col-lg-auto">
                 <img src="assets/logo.png" alt="Parc des Prince" class="site-logo">
            </div>

            <div class="col-12 col-lg order-3 order-lg-2">
                <nav class="nav-pill">
                    <?php foreach ($liens as $url => $nom): ?>
                        <a href="<?= $url ?>" class="<?= $page === $url ? 'active' : '' ?>"><?= h($nom) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="col-6 col-lg-auto order-2 order-lg-3 text-end">
                <div class="header-account">
                    <div class="profile-box" title="<?= h($email) ?>">
                        <span class="profile-circle"><?= h(strtoupper(substr($email, 0, 1))) ?></span>
                        <span class="profile-email"><?= h($email) ?></span>
                    </div>
                    <a href="../logout.php" class="logout-btn">Déconnexion</a>
                </div>
            </div>

        </div>
    </div>
</header>

<main class="container">

<?php if ($afficherTitre): ?>
    <div class="row">
        <div class="col-12">
            <h1 class="page-title"><?= h($titre) ?></h1>
        </div>
    </div>
<?php endif; ?>

<?php
}

function pied(): void {
?>

</main>
</body>
</html>
<?php
}
