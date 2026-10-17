<?php
require_once __DIR__ . '/../promotions/_commun.php';
$q = trim((string)($_GET['q'] ?? ''));
$etat = (string)($_GET['etat'] ?? '');
$params = [];
$where = [];
if ($q !== '') { $where[] = '(o.nom_offre LIKE ? OR o.description LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
if ($etat !== '') { $where[] = 'o.etat = ?'; $params[] = $etat; }
$sql = "SELECT o.*, (SELECT COUNT(*) FROM promotion_billet p WHERE p.id_offre=o.id_offre) AS nb_tickets, (SELECT GROUP_CONCAT(j.nom ORDER BY j.nom SEPARATOR ', ')
        FROM offre_jeu oj JOIN jeu j ON j.id_jeu = oj.id_jeu WHERE oj.id_offre = o.id_offre) AS jeux
        FROM offre o" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.id_offre DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $offres = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query("SELECT COUNT(*) AS total,
    COALESCE(SUM(CASE WHEN etat = 'Disponible' AND (date_debut IS NULL OR date_debut <= CURRENT_DATE)
    AND (date_fin IS NULL OR date_fin >= CURRENT_DATE) THEN 1 ELSE 0 END), 0) AS actives FROM offre")->fetch(PDO::FETCH_ASSOC);
$etats = $pdo->query('SELECT DISTINCT etat FROM offre WHERE etat IS NOT NULL ORDER BY etat')->fetchAll(PDO::FETCH_COLUMN);
$flash = $_SESSION['offres_flash'] ?? ''; unset($_SESSION['offres_flash']);
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Annonces / Offres | Parc des Princes</title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/offres-admin.css"></head><body>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>
<main>
<div class="page-header"><div><h1>Annonces / Offres</h1><p>Créez l’annonce avec image, choisissez ses jeux, puis ajoutez ses tickets.</p></div><a href="ajouter.php" class="btn-primary">+ Ajouter une annonce</a></div>
<?php if ($flash): ?><div class="alert-success" role="status"><?= offre_h($flash) ?></div><?php endif; ?>
<div class="offre-summary"><span><?= (int)$stats['total'] ?> offre(s) au total</span><span><?= (int)$stats['actives'] ?> disponible(s) aujourd’hui</span><span><?= count($offres) ?> résultat(s)</span></div>
<form class="filters-box" method="get"><input type="search" name="q" value="<?= offre_h($q) ?>" placeholder="Nom ou description…" aria-label="Rechercher une offre"><select name="etat" aria-label="Filtrer par état"><option value="">Tous les états</option><?php foreach ($etats as $e): ?><option value="<?= offre_h($e) ?>" <?= $etat === $e ? 'selected' : '' ?>><?= offre_h($e) ?></option><?php endforeach; ?></select><button class="btn-primary">Rechercher</button><a class="btn-secondary" href="index.php">Réinitialiser</a></form>
<div class="table-box"><table><thead><tr><th>Image</th><th>Annonce / Tickets</th><th>Jeux inclus</th><th>Prix</th><th>Début</th><th>Fin</th><th>État</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($offres as $o): ?><tr><td><img class="annonce-thumb" src="<?=offre_h(promo_image($o['img_offre']))?>" alt="<?=offre_h($o['nom_offre'])?>"></td><td><strong><?= offre_h($o['nom_offre']) ?></strong><br><small><?= (int)$o['nb_tickets'] ?> ticket(s) associé(s)</small><br><span class="field-help"><?= offre_h(offre_resume((string)($o['description'] ?? ''))) ?></span></td><td><?= offre_h($o['jeux'] ?: 'Aucun jeu associé') ?></td><td style="white-space:nowrap"><?= number_format((float)$o['prix_offre'], 2, ',', ' ') ?> DH</td><td><?= offre_h($o['date_debut'] ?: 'Sans limite') ?></td><td><?= offre_h($o['date_fin'] ?: 'Sans limite') ?></td><td><span class="status-badge"><?= offre_h($o['etat']) ?></span></td><td><div class="actions-box"><a class="btn-secondary" href="details.php?id=<?= (int)$o['id_offre'] ?>">Voir / Tickets</a><a href="modifier.php?id=<?= (int)$o['id_offre'] ?>" class="btn-edit">Modifier</a><a href="supprimer.php?id=<?= (int)$o['id_offre'] ?>" class="btn-delete">Supprimer</a></div></td></tr><?php endforeach; ?>
<?php if (!$offres): ?><tr><td colspan="8">Aucune offre trouvée. Ajoutez une offre ou modifiez vos filtres.</td></tr><?php endif; ?>
</tbody></table></div></main></body></html>
