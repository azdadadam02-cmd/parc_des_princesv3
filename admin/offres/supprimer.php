<?php
require_once __DIR__ . '/_commun.php';
$id = offre_id(); $offre = offre_lire($pdo, $id); $erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!offre_csrf_valide()) { $erreur = 'La session du formulaire a expiré. Veuillez réessayer.'; }
    else {
        try {
            $pdo->beginTransaction();
            $lock=$pdo->prepare('SELECT id_offre FROM offre WHERE id_offre=? FOR UPDATE');$lock->execute([$id]);
            $check=$pdo->prepare('SELECT COUNT(*) FROM promotion_billet WHERE id_offre=?');$check->execute([$id]);
            if ((int)$check->fetchColumn()>0) throw new DomainException('Cette annonce possède des tickets. Rendez-la Indisponible pour conserver leurs liens et leur historique.');
            $stmt = $pdo->prepare('DELETE FROM offre_jeu WHERE id_offre = ?'); $stmt->execute([$id]);
            $stmt = $pdo->prepare('DELETE FROM offre WHERE id_offre = ?'); $stmt->execute([$id]);
            $pdo->commit(); offre_flash('L’offre a bien été supprimée.');
        } catch (DomainException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erreur=$e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Suppression offre : ' . $e->getMessage()); $erreur = 'La suppression est impossible pour le moment. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Supprimer l’offre | Parc des Princes</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>
<main><div class="page-header"><div><h1>Supprimer l’offre</h1><p>Confirmez la suppression de cette formule.</p></div><a class="btn-secondary" href="index.php">Retour aux offres</a></div><div class="form-box">
<?php if ($erreur): ?><div class="alert-error" role="alert"><?= offre_h($erreur) ?></div><?php endif; ?>
<h2><?= offre_h($offre['nom_offre']) ?></h2><p style="margin-top:12px">Cette offre et ses associations aux jeux seront supprimées définitivement. Les jeux eux-mêmes seront conservés.</p>
<form method="post" class="form-actions"><input type="hidden" name="csrf" value="<?= offre_h(offre_token()) ?>"><button class="btn-delete" type="submit">Confirmer la suppression</button><a href="index.php" class="btn-secondary">Annuler</a></form>
</div></main></body></html>
