<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

function bi_h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function bi_date(string $v): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);

    return $d && $d->format('Y-m-d') === $v;
}

function bi_dh($v): string
{
    return number_format((float) $v, 2, ',', ' ') . ' DH';
}

$du = is_string($_GET['du'] ?? null)
    ? $_GET['du']
    : date('Y-m-01', strtotime('-11 months'));

$au = is_string($_GET['au'] ?? null)
    ? $_GET['au']
    : date('Y-m-d');

$erreur = '';

if (
    !bi_date($du) ||
    !bi_date($au) ||
    $du > $au ||
    strtotime($au) - strtotime($du) > 5 * 366 * 86400
) {
    $erreur = 'Choisissez une période valide, de cinq ans maximum. La période par défaut est affichée.';

    $du = date('Y-m-01', strtotime('-11 months'));
    $au = date('Y-m-d');
}

$debut = $du . ' 00:00:00';

$fin = (new DateTimeImmutable($au))
    ->modify('+1 day')
    ->format('Y-m-d 00:00:00');

$journalier = (new DateTimeImmutable($du))
    ->diff(new DateTimeImmutable($au))
    ->days <= 62;

$formatSql = $journalier ? '%Y-%m-%d' : '%Y-%m';

$requete = function (
    string $sql,
    array $params = []
) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($params);

    return $s;
};

$recettes = (float) $requete(
    "SELECT COALESCE(SUM(montant_total), 0)
     FROM commande
     WHERE statut_commande = 'payée'
       AND COALESCE(date_paiement, date_commande) >= ?
       AND COALESCE(date_paiement, date_commande) < ?",
    [$debut, $fin]
)->fetchColumn();

$depenses = (float) $requete(
    'SELECT COALESCE(SUM(montant_depense), 0)
     FROM depense
     WHERE date_depense >= ?
       AND date_depense < ?',
    [$du, $fin]
)->fetchColumn();

$reservations = (int) $requete(
    'SELECT COUNT(*)
     FROM reservation
     WHERE date_creation >= ?
       AND date_creation < ?',
    [$debut, $fin]
)->fetchColumn();

$billets = (int) $requete(
    "SELECT COALESCE(SUM(b.quantite), 0)
     FROM billet b
     JOIN commande c ON c.num_commande = b.num_commande
     WHERE c.statut_commande = 'payée'
       AND COALESCE(c.date_paiement, c.date_commande) >= ?
       AND COALESCE(c.date_paiement, c.date_commande) < ?",
    [$debut, $fin]
)->fetchColumn();

$etats = $requete(
    "SELECT COALESCE(etat, 'Non défini') AS etat,
            COUNT(*) AS total
     FROM jeu
     GROUP BY etat
     ORDER BY total DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$totalJeux = array_sum(array_column($etats, 'total'));

$recParPeriode = $requete(
    "SELECT DATE_FORMAT(
                COALESCE(date_paiement, date_commande),
                '$formatSql'
            ) AS p,
            SUM(montant_total) AS m
     FROM commande
     WHERE statut_commande = 'payée'
       AND COALESCE(date_paiement, date_commande) >= ?
       AND COALESCE(date_paiement, date_commande) < ?
     GROUP BY p",
    [$debut, $fin]
)->fetchAll(PDO::FETCH_KEY_PAIR);

$depParPeriode = $requete(
    "SELECT DATE_FORMAT(date_depense, '$formatSql') AS p,
            SUM(montant_depense) AS m
     FROM depense
     WHERE date_depense >= ?
       AND date_depense < ?
     GROUP BY p",
    [$du, $fin]
)->fetchAll(PDO::FETCH_KEY_PAIR);

$resParPeriode = $requete(
    "SELECT DATE_FORMAT(date_creation, '$formatSql') AS p,
            COUNT(*) AS m
     FROM reservation
     WHERE date_creation >= ?
       AND date_creation < ?
     GROUP BY p",
    [$debut, $fin]
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Billets donnant accès à plusieurs jeux : répartir leur montant à parts égales.
$topJeux = $requete(
    "SELECT j.nom,
            SUM(b.prix_unitaire * b.quantite / n.nb) AS montant
     FROM commande c
     JOIN billet b ON b.num_commande = c.num_commande
     JOIN contient ct ON ct.id_billet = b.id_billet
     JOIN jeu j ON j.id_jeu = ct.id_jeu
     JOIN (
         SELECT id_billet,
                COUNT(*) AS nb
         FROM contient
         GROUP BY id_billet
     ) n ON n.id_billet = b.id_billet
     WHERE c.statut_commande = 'payée'
       AND COALESCE(c.date_paiement, c.date_commande) >= ?
       AND COALESCE(c.date_paiement, c.date_commande) < ?
     GROUP BY j.id_jeu, j.nom
     ORDER BY montant DESC, j.nom
     LIMIT 5",
    [$debut, $fin]
)->fetchAll(PDO::FETCH_ASSOC);

$serie = [];

$cursor = new DateTimeImmutable(
    $journalier ? $du : substr($du, 0, 7) . '-01'
);

$limite = new DateTimeImmutable($au);

while ($cursor <= $limite) {
    $key = $cursor->format($journalier ? 'Y-m-d' : 'Y-m');

    $serie[] = [
        'periode' => $key,
        'recettes' => (float) ($recParPeriode[$key] ?? 0),
        'depenses' => (float) ($depParPeriode[$key] ?? 0),
        'reservations' => (int) ($resParPeriode[$key] ?? 0)
    ];

    $cursor = $cursor->modify(
        $journalier ? '+1 day' : '+1 month'
    );
}

$net = $recettes - $depenses;

$chartData = [
    'serie' => $serie,
    'etats' => $etats,
    'top' => $topJeux,
    'journalier' => $journalier
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pilotage du parc | Parc des Princes</title>

    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main>
        <header class="dashboard-header">
            <div>
                <span class="dashboard-eyebrow">
                    ESPACE ADMINISTRATEUR
                </span>

                <h1>
                    Bonjour <?= bi_h($_SESSION['admin_nom'] ?? 'Admin') ?>
                </h1>

                <p>
                    Consultez l’activité et les performances de votre parc.
                </p>
            </div>

            <div class="dashboard-account">
                <div class="profile-box">
                    <span class="profile-circle">
                        <?= bi_h(
                            strtoupper(
                                substr($_SESSION['admin_email'] ?? 'A', 0, 1)
                            )
                        ) ?>
                    </span>

                    <span class="profile-email">
                        <?= bi_h($_SESSION['admin_email'] ?? '') ?>
                    </span>
                </div>

                <a href="logout.php" class="logout-btn">
                    Déconnexion
                </a>
            </div>
        </header>

        <?php if ($erreur): ?>
            <div class="alert-error" role="alert">
                <?= bi_h($erreur) ?>
            </div>
        <?php endif; ?>

        <form method="get" class="filters-box dashboard-filter">
            <div class="dashboard-filter-heading">
                <div>
                    <h2>Période d’analyse</h2>

                    <p>
                        Du <?= bi_h((new DateTimeImmutable($du))->format('d/m/Y')) ?>
                        au <?= bi_h((new DateTimeImmutable($au))->format('d/m/Y')) ?>
                    </p>
                </div>

                <div class="period-shortcuts" aria-label="Périodes rapides">
                    <a href="?du=<?= date('Y-m-d', strtotime('-29 days')) ?>&amp;au=<?= date('Y-m-d') ?>">
                        30 jours
                    </a>

                    <a href="dashboard.php">
                        12 mois
                    </a>
                </div>
            </div>

            <div class="dashboard-filter-fields">
                <div class="dashboard-date">
                    <label for="du">Date de début</label>

                    <input
                        type="date"
                        id="du"
                        name="du"
                        lang="fr"
                        required
                        value="<?= bi_h($du) ?>"
                    >
                </div>

                <span class="date-separator" aria-hidden="true"></span>

                <div class="dashboard-date">
                    <label for="au">Date de fin</label>

                    <input
                        type="date"
                        id="au"
                        name="au"
                        lang="fr"
                        required
                        value="<?= bi_h($au) ?>"
                    >
                </div>

                <button type="submit" class="btn-primary dashboard-apply">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <circle cx="10.5" cy="10.5" r="6.5"/>
                        <path d="m16 16 4.5 4.5"/>
                    </svg>

                    Afficher les résultats
                </button>
            </div>
        </form>

        <div class="bi-kpis">
            <div class="bi-kpi bi-kpi--revenue">
                <small>Recettes encaissées</small>

                <strong><?= bi_dh($recettes) ?></strong>

                <span>Commandes payées sur la période</span>
            </div>

            <div class="bi-kpi bi-kpi--expense">
                <small>Dépenses enregistrées</small>

                <strong><?= bi_dh($depenses) ?></strong>

                <span>Frais du parc et des jeux</span>
            </div>

            <div class="bi-kpi bi-kpi--net">
                <small>Résultat net</small>

                <strong><?= bi_dh($net) ?></strong>

                <span>
                    <?= $recettes > 0
                        ? 'Marge : '
                            . number_format($net / $recettes * 100, 1, ',', ' ')
                            . ' %'
                        : 'Marge non calculable sans recettes'
                    ?>
                </span>
            </div>
        </div>

        <div class="bi-meta">
            <a href="jeux/index.php">
                <strong><?= (int) $totalJeux ?></strong>
                Jeux du parc · état actuel
            </a>

            <a href="reservations/index.php">
                <strong><?= $reservations ?></strong>
                Réservations créées sur la période
            </a>

            <a href="billets/index.php">
                <strong><?= $billets ?></strong>
                Billets vendus sur la période
            </a>
        </div>

        <div class="bi-grid">
            <section class="chart-card chart-card--wide">
                <div class="chart-header">
                    <h2>Performance financière</h2>

                    <span>
                        Recettes, dépenses et résultat
                        · <?= $journalier ? 'par jour' : 'par mois' ?> · DH
                    </span>
                </div>

                <div class="chart-legend">
                    <span>
                        <i class="legend-dot" style="--dot: #22d3ee"></i>
                        Recettes
                    </span>

                    <span>
                        <i class="legend-dot" style="--dot: #fb7185"></i>
                        Dépenses
                    </span>

                    <span>
                        <i class="legend-dot" style="--dot: #a78bfa"></i>
                        Résultat net
                    </span>
                </div>

                <div class="chart-container">
                    <canvas
                        id="financeChart"
                        role="img"
                        aria-label="Évolution des recettes, dépenses et résultat sur la période"
                    ></canvas>
                </div>

                <details class="chart-data">
                    <summary>Consulter les données</summary>

                    <div class="chart-data-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Période</th>
                                    <th>Recettes</th>
                                    <th>Dépenses</th>
                                    <th>Résultat</th>
                                    <th>Réservations</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($serie as $v): ?>
                                    <tr>
                                        <td><?= bi_h($v['periode']) ?></td>
                                        <td><?= bi_dh($v['recettes']) ?></td>
                                        <td><?= bi_dh($v['depenses']) ?></td>
                                        <td>
                                            <?= bi_dh($v['recettes'] - $v['depenses']) ?>
                                        </td>
                                        <td><?= $v['reservations'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>

            <section class="chart-card">
                <div class="chart-header">
                    <h2>Rythme des réservations</h2>

                    <span>
                        Réservations créées
                        · <?= $journalier ? 'par jour' : 'par mois' ?>
                        · tous statuts
                    </span>
                </div>

                <div class="chart-container">
                    <canvas
                        id="reservationsChart"
                        role="img"
                        aria-label="Nombre de réservations créées"
                    ></canvas>
                </div>

                <p class="field-help">
                    Les données détaillées figurent dans le tableau de performance.
                </p>
            </section>

            <section class="chart-card">
                <div class="chart-header">
                    <h2>État du parc</h2>

                    <span>
                        Répartition actuelle des <?= (int) $totalJeux ?> jeux
                        · hors filtre de dates
                    </span>
                </div>

                <div class="chart-container">
                    <canvas
                        id="etatChart"
                        role="img"
                        aria-label="Répartition des jeux par état"
                    ></canvas>
                </div>

                <details class="chart-data">
                    <summary>Consulter la répartition</summary>

                    <ul>
                        <?php foreach ($etats as $v): ?>
                            <li>
                                <?= bi_h($v['etat']) ?> : <?= (int) $v['total'] ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            </section>

            <section class="chart-card chart-card--wide">
                <div class="chart-header">
                    <h2>Top 5 des jeux par ventes de billets</h2>

                    <span>
                        Montant des billets payés,
                        réparti entre les jeux associés · DH
                    </span>
                </div>

                <div class="chart-container">
                    <canvas
                        id="topChart"
                        role="img"
                        aria-label="Classement des jeux par montant de billets vendus"
                    ></canvas>
                </div>

                <details class="chart-data">
                    <summary>Consulter le classement</summary>

                    <ol>
                        <?php foreach ($topJeux as $v): ?>
                            <li>
                                <?= bi_h($v['nom']) ?> : <?= bi_dh($v['montant']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </details>
            </section>
        </div>

        <div class="section-box">
            <h2>Accès rapide</h2>

            <div class="quick-links">
                <a class="btn-secondary" href="offres/index.php">
                    Gérer les affiches
                </a>

                <a class="btn-secondary" href="reservations/index.php">
                    Réservations
                </a>

                <a class="btn-secondary" href="Ventes/index.php">
                    Ventes
                </a>

                <a class="btn-secondary" href="maintenance/index.php">
                    Maintenance
                </a>
            </div>
        </div>

        <script id="bi-data" type="application/json"><?= json_encode(
            $chartData,
            JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_UNICODE
        ) ?></script>

        <script src="assets/vendor/chart.umd.min.js"></script>
        <script src="assets/js/dashboard.js"></script>
    </main>

</body>
</html>