<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../admin/includes/db.php';

const ROLE_AGENT = 'agent';

$email_agent = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';

$role_session = strtolower(
    trim((string) ($_SESSION['user_role'] ?? $_SESSION['role'] ?? ''))
);

if ($email_agent === '' || $role_session !== ROLE_AGENT) {
    header('Location: ../login.php');
    exit;
}

$st = $pdo->prepare("SELECT 1 FROM agent_de_vente WHERE email = ?");
$st->execute([$email_agent]);

if (!$st->fetch()) {
    http_response_code(403);
    die("Accès refusé.");
}

const MODES_PAIEMENT = ['espèces', 'carte'];
const MAX_PAR_LIGNE = 20;
const EMAIL_VISITEUR = 'visiteur@parc.local';

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function icone(string $nom): string
{
    static $formes = [
        'billet' => '<path d="M3 8.5V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a2.5 2.5 0 0 0 0 5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3.5a2.5 2.5 0 0 0 0-5z"/><path d="M14 5v14" stroke-dasharray="2 2.5"/>',
    ];

    $d = $formes[$nom] ?? '';

    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $d
        . '</svg>';
}

function ucfirst_fr(string $s): string
{
    return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}

function dh($v): string
{
    return number_format((float) $v, 2, ',', ' ') . ' DH';
}

function date_fr(?string $d, bool $heure = false): string
{
    if (!$d) {
        return '—';
    }

    $t = strtotime($d);

    return $t ? date($heure ? 'd/m/Y H:i' : 'd/m/Y', $t) : '—';
}

function date_valide(string $s): string
{
    $d = DateTime::createFromFormat('Y-m-d', $s);

    return ($d && $d->format('Y-m-d') === $s) ? $s : '';
}

function classe_statut_reservation(?string $s): string
{
    switch (mb_strtolower(trim((string) $s))) {
        case 'confirmée':
            return 'is-ok';

        case 'payée':
            return 'is-info';

        case 'utilisée':
            return 'is-muted';

        case 'annulée':
            return 'is-danger';

        default:
            return 'is-warn';
    }
}

function evaluer_billet(array $b): array
{
    $s = mb_strtolower(trim((string) $b['statut_billet']));

    if (mb_strtolower((string) $b['statut_commande']) !== 'payée') {
        return [
            false,
            'Non payé',
            'is-danger',
            "La commande associée n'est pas payée."
        ];
    }

    if ($s === 'annulé') {
        return [
            false,
            'Annulé',
            'is-danger',
            'Ce billet a été annulé.'
        ];
    }

    if (!empty($b['date_fin']) && strtotime($b['date_fin']) < time()) {
        return [
            false,
            'Expiré',
            'is-danger',
            'Ce billet a expiré le ' . date_fr($b['date_fin'], true) . '.'
        ];
    }

    if ($s === 'utilisé') {
        return [
            false,
            'Déjà utilisé',
            'is-warn',
            'Ce billet à usage unique a déjà été utilisé.'
        ];
    }

    return [
        true,
        'Valide',
        'is-ok',
        "Billet valide, l'accès peut être autorisé."
    ];
}

function csrf_token(string $nom): string
{
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        $_SESSION['csrf_token'] === ''
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_champ(string $nom): string
{
    return '<input type="hidden" name="csrf" value="'
        . h(csrf_token($nom))
        . '">';
}

function csrf_ok(string $nom): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf']);
}

function csrf_renouveler(string $nom): void
{
    unset($_SESSION['csrf_token']);
}

const TYPES_BILLETS = [
    'enfant' => [
        'libelle' => 'Enfant',
        'prix' => 50.00,
        'validite_jours' => 1
    ],
    'adulte' => [
        'libelle' => 'Adulte',
        'prix' => 100.00,
        'validite_jours' => 1
    ],
    'famille' => [
        'libelle' => 'Famille',
        'prix' => 300.00,
        'validite_jours' => 1
    ],
    'pass' => [
        'libelle' => 'Pass journée',
        'prix' => 150.00,
        'validite_jours' => 1
    ],
];

function types_billets(): array
{
    $out = [];

    foreach (TYPES_BILLETS as $id => $t) {
        $out[] = ['id_type' => $id, 'usage_unique' => 1] + $t;
    }

    return $out;
}

function code_billet(int $id_billet): string
{
    return 'PP-' . str_pad((string) $id_billet, 6, '0', STR_PAD_LEFT);
}

function id_depuis_code(string $code): ?int
{
    if (!preg_match('/^(?:PP-?)?0*([0-9]{1,9})$/i', trim($code), $m)) {
        return null;
    }

    return (int) $m[1] ?: null;
}

const ETAT_DISPONIBLE = 'Disponible';
const ETAT_INDISPONIBLE = 'Indisponible';

function maj_automatique(PDO $pdo): void
{
    try {
        $echue = "c.statut_commande = 'en attente'
                  AND COALESCE(
                      c.date_limite_paiement,
                      DATE_ADD(DATE(r.date_visite), INTERVAL 1 DAY)
                  ) <= NOW()";

        $pdo->exec(
            "UPDATE billet b
             JOIN commande c ON c.num_commande = b.num_commande
             LEFT JOIN reservation r ON r.num_reservation = c.num_reservation
             SET b.statut_billet = 'annulé'
             WHERE b.statut_billet = 'en attente'
               AND $echue"
        );

        $pdo->exec(
            "UPDATE reservation r
             JOIN commande c ON c.num_reservation = r.num_reservation
             SET r.statut_reservation = 'annulée'
             WHERE r.statut_reservation IN ('en attente', 'confirmée')
               AND $echue"
        );

        $pdo->exec(
            "UPDATE commande c
             LEFT JOIN reservation r ON r.num_reservation = c.num_reservation
             SET c.statut_commande = 'annulée'
             WHERE $echue"
        );

        $pdo->exec(
            "UPDATE reservation
             SET statut_reservation = 'utilisée'
             WHERE statut_reservation IN ('confirmée', 'payée')
               AND date_visite < CURDATE()"
        );
    } catch (Throwable $e) {
        error_log('maj_automatique : ' . $e->getMessage());
    }
}

function liberer_jeux(PDO $pdo): void
{
}

function telephone_valide(?string $tel): string
{
    $t = preg_replace('/[\s.\-]/', '', (string) $tel);

    return preg_match('/^[0-9]{10}$/', $t) ? $t : '';
}

function erreurs_infos_client(array $u): array
{
    $err = [];

    if (
        trim((string) ($u['nom'] ?? '')) === '' ||
        trim((string) ($u['prenom'] ?? '')) === ''
    ) {
        $err[] = "nom ou prénom vide";
    }

    if (telephone_valide($u['telephone'] ?? '') === '') {
        $err[] = "téléphone invalide (10 chiffres attendus)";
    }

    if (!filter_var((string) ($u['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        $err[] = "e-mail invalide";
    }

    return $err;
}

function fin_de_journee(string $dt): string
{
    return date('Y-m-d 23:59:59', strtotime($dt));
}

function valider_reservation(PDO $pdo, int $num, string $agent): array
{
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare(
            "SELECT r.*, u.nom, u.prenom, u.telephone, u.email
             FROM reservation r
             JOIN utilisateur u ON u.email = r.email_client
             WHERE r.num_reservation = ?
               AND r.origine = 'en ligne'
             FOR UPDATE"
        );

        $st->execute([$num]);
        $r = $st->fetch();

        if (!$r) {
            return _echec($pdo, "Réservation introuvable.");
        }

        if (
            mb_strtolower(trim((string) $r['statut_reservation']))
            !== 'en attente'
        ) {
            return _echec($pdo, "Cette réservation n'est plus en attente.");
        }

        if (strtotime($r['date_visite']) < strtotime('today')) {
            return _echec($pdo, "La date de visite est dépassée.");
        }

        if ($e = erreurs_infos_client($r)) {
            return _echec(
                $pdo,
                "Informations client invalides : " . implode(', ', $e) . "."
            );
        }

        $st = $pdo->prepare(
            "SELECT j.id_jeu, j.nom, j.etat, j.capacite
             FROM choisir ch
             JOIN jeu j ON j.id_jeu = ch.id_jeu
             WHERE ch.num_reservation = ?
             FOR UPDATE"
        );

        $st->execute([$num]);
        $jeux = $st->fetchAll();

        if (!$jeux) {
            return _echec($pdo, "Aucun jeu n'est associé à cette réservation.");
        }

        foreach ($jeux as $j) {
            if (
                mb_strtolower($j['etat'])
                !== mb_strtolower(ETAT_DISPONIBLE)
            ) {
                return _echec(
                    $pdo,
                    "Le jeu « {$j['nom']} » n'est pas disponible (état : {$j['etat']})."
                );
            }

            if (
                $j['capacite'] !== null &&
                (int) $r['nb_personnes'] > (int) $j['capacite']
            ) {
                return _echec(
                    $pdo,
                    "Le jeu « {$j['nom']} » accepte au maximum "
                    . (int) $j['capacite']
                    . " joueurs."
                );
            }
        }

        $st = $pdo->prepare(
            "SELECT 1
             FROM commande
             WHERE num_reservation = ?
               AND statut_commande <> 'annulée'"
        );

        $st->execute([$num]);

        if ($st->fetch()) {
            return _echec(
                $pdo,
                "Une commande existe déjà pour cette réservation."
            );
        }

        $pdo->prepare(
            "UPDATE reservation
             SET statut_reservation = 'confirmée',
                 email_agent = ?
             WHERE num_reservation = ?"
        )->execute([$agent, $num]);

        $pdo->prepare(
            "INSERT INTO commande (
                montant_total,
                statut_commande,
                email_client,
                email_agent,
                num_reservation,
                date_limite_paiement
             ) VALUES (?, 'en attente', ?, ?, ?, ?)"
        )->execute([
            $r['montant'],
            $r['email_client'],
            $agent,
            $num,
            fin_de_journee($r['date_visite'])
        ]);

        $num_commande = (int) $pdo->lastInsertId();

        $pdo->commit();

        return [
            true,
            "Réservation #$num validée : commande #$num_commande créée."
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('valider_reservation : ' . $e->getMessage());

        return [
            false,
            "Erreur technique : aucune modification n'a été enregistrée."
        ];
    }
}

function annuler_reservation(PDO $pdo, int $num): array
{
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare(
            "SELECT statut_reservation
             FROM reservation
             WHERE num_reservation = ?
               AND origine = 'en ligne'
             FOR UPDATE"
        );

        $st->execute([$num]);
        $s = $st->fetchColumn();

        if ($s === false) {
            return _echec($pdo, "Réservation introuvable.");
        }

        $s_normalise = mb_strtolower(trim((string) $s));

        if (!in_array($s_normalise, ['en attente', 'confirmée'], true)) {
            return _echec(
                $pdo,
                "Cette réservation ne peut plus être annulée (statut : $s)."
            );
        }

        $st = $pdo->prepare(
            "SELECT 1
             FROM commande
             WHERE num_reservation = ?
               AND statut_commande = 'payée'"
        );

        $st->execute([$num]);

        if ($st->fetch()) {
            return _echec(
                $pdo,
                "La commande est déjà payée : annulation impossible."
            );
        }

        $pdo->prepare(
            "UPDATE billet b
             JOIN commande c ON c.num_commande = b.num_commande
             SET b.statut_billet = 'annulé'
             WHERE c.num_reservation = ?
               AND c.statut_commande = 'en attente'"
        )->execute([$num]);

        $pdo->prepare(
            "UPDATE commande
             SET statut_commande = 'annulée'
             WHERE num_reservation = ?
               AND statut_commande = 'en attente'"
        )->execute([$num]);

        $pdo->prepare(
            "UPDATE reservation
             SET statut_reservation = 'annulée'
             WHERE num_reservation = ?"
        )->execute([$num]);

        $pdo->commit();

        return [true, "Réservation #$num annulée."];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('annuler_reservation : ' . $e->getMessage());

        return [
            false,
            "Erreur technique : aucune modification n'a été enregistrée."
        ];
    }
}

function encaisser_commande(
    PDO $pdo,
    int $num_commande,
    string $agent
): array {
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare(
            "SELECT c.num_commande,
                    c.montant_total,
                    c.num_reservation,
                    c.date_limite_paiement,
                    r.date_visite,
                    r.nb_personnes
             FROM commande c
             LEFT JOIN reservation r ON r.num_reservation = c.num_reservation
             WHERE c.num_commande = ?
               AND c.statut_commande = 'en attente'
             FOR UPDATE"
        );

        $st->execute([$num_commande]);
        $c = $st->fetch();

        if (!$c) {
            return _echec(
                $pdo,
                "Commande introuvable ou déjà traitée.",
                $num_commande
            );
        }

        $limite = $c['date_limite_paiement']
            ?: ($c['date_visite'] ? fin_de_journee($c['date_visite']) : null);

        if ($limite && strtotime($limite) < time()) {
            return _echec(
                $pdo,
                "La date limite de paiement est dépassée : la commande va être annulée.",
                $num_commande
            );
        }

        $pdo->prepare(
            "UPDATE commande
             SET statut_commande = 'payée',
                 email_agent = ?,
                 date_paiement = NOW()
             WHERE num_commande = ?
               AND statut_commande = 'en attente'"
        )->execute([$agent, $num_commande]);

        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM billet WHERE num_commande = ?"
        );

        $st->execute([$num_commande]);

        if ((int) $st->fetchColumn() === 0) {
            $nb = max(1, (int) ($c['nb_personnes'] ?? 1));
            $prix = round((float) $c['montant_total'] / $nb, 2);

            $debut = date(
                'Y-m-d 00:00:00',
                strtotime($c['date_visite'] ?? 'today')
            );

            $fin = fin_de_journee($c['date_visite'] ?? 'today');

            $ins = $pdo->prepare(
                "INSERT INTO billet (
                    date_creation,
                    date_fin,
                    type,
                    statut_billet,
                    quantite,
                    prix_unitaire,
                    num_commande,
                    email_agent
                 ) VALUES (?, ?, 'Pass journée', 'valide', 1, ?, ?, ?)"
            );

            $lien = $pdo->prepare(
                "INSERT INTO contient (id_billet, id_jeu)
                 SELECT ?, id_jeu
                 FROM choisir
                 WHERE num_reservation = ?"
            );

            for ($i = 0; $i < $nb; $i++) {
                $ins->execute([
                    $debut,
                    $fin,
                    $prix,
                    $num_commande,
                    $agent
                ]);

                if ($c['num_reservation']) {
                    $lien->execute([
                        (int) $pdo->lastInsertId(),
                        $c['num_reservation']
                    ]);
                }
            }
        } else {
            $pdo->prepare(
                "UPDATE billet
                 SET statut_billet = 'valide',
                     email_agent = ?
                 WHERE num_commande = ?
                   AND statut_billet = 'en attente'"
            )->execute([$agent, $num_commande]);
        }

        if ($c['num_reservation']) {
            $pdo->prepare(
                "UPDATE reservation
                 SET statut_reservation = 'payée'
                 WHERE num_reservation = ?
                   AND statut_reservation IN ('en attente', 'confirmée')"
            )->execute([$c['num_reservation']]);
        }

        $pdo->commit();

        return [
            true,
            "Commande #$num_commande payée.",
            $num_commande
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('encaisser_commande : ' . $e->getMessage());

        return [
            false,
            "Erreur technique : aucun paiement n'a été enregistré.",
            $num_commande
        ];
    }
}

function _echec(PDO $pdo, string $msg, ?int $id = null): array
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    return $id === null ? [false, $msg] : [false, $msg, $id];
}

maj_automatique($pdo);

function entete(string $titre, bool $afficherTitre = true): void
{
    $page = basename($_SERVER['SCRIPT_NAME']);

    if (
        in_array(
            $page,
            ['recu.php', 'Billet.php', 'vente.php', 'paiements.php'],
            true
        )
    ) {
        $page = 'commandes.php';
    }

    if (in_array($page, ['tous_billets.php', 'billets.php'], true)) {
        $page = 'index.php';
    }

    $liens = [
        'index.php' => 'Dashboard',
        'reservations.php' => 'Réservations',
        'commandes.php' => 'Commandes',
    ];

    $email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? 'Compte';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= h($titre) ?> - Parc des Princes</title>

    <link
        rel="stylesheet"
        href="assets/style.css?v=<?= @filemtime(__DIR__ . '/assets/style.css') ?: time() ?>"
    >
    <link
        rel="stylesheet"
        href="assets/agent.css?v=<?= @filemtime(__DIR__ . '/assets/agent.css') ?: time() ?>"
    >

    <style>
        .agent-header .agent-side--end {
            flex: 0 0 auto;
            text-align: right;
            margin-left: clamp(1.5rem, 4vw, 4rem);
        }

        .agent-header .header-account {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 12px;
            max-width: 100%;
        }

        .agent-header .profile-box {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 5px 16px 5px 5px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            background: rgba(19, 17, 44, 0.85);
        }

        .agent-header .profile-circle {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #4f46e5;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }

        .agent-header .profile-email {
            font-size: 13px;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .agent-header .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 18px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            background: rgba(239, 68, 68, 0.08);
            color: #f87171;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            text-decoration: none;
            transition: border-color 0.2s ease;
        }

        .agent-header .logout-btn:hover {
            border-color: currentColor;
        }
    </style>
</head>
<body>

<header class="site-header agent-header">
    <div class="container agent-header-inner">
        <div class="agent-side">
            <img
                src="assets/logo.png"
                alt="Parc des Prince"
                class="site-logo"
            >
        </div>

        <nav class="nav-pill">
            <?php foreach ($liens as $url => $nom): ?>
                <a
                    href="<?= $url ?>"
                    class="<?= $page === $url ? 'active' : '' ?>"
                ><?= h($nom) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="agent-side agent-side--end">
            <div class="header-account">
                <div class="profile-box" title="<?= h($email) ?>">
                    <span class="profile-circle">
                        <?= h(strtoupper(substr($email, 0, 1))) ?>
                    </span>

                    <span class="profile-email">
                        <?= h($email) ?>
                    </span>
                </div>

                <a href="../logout.php" class="logout-btn">
                    Déconnexion
                </a>
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

function pied(): void
{
?>

</main>
</body>
</html>
<?php
}