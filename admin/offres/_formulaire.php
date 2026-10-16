<?php
require_once __DIR__ . '/_commun.php';
$offreId = $offreId ?? null;
$offre = $offreId ? offre_lire($pdo, $offreId) : ['nom_offre'=>'', 'description'=>'', 'prix_offre'=>'', 'date_debut'=>'', 'date_fin'=>'', 'etat'=>'Disponible', 'img_offre'=>''];
$jeux = $pdo->query('SELECT id_jeu, nom FROM jeu ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);
$selection = [];
if ($offreId) {
    $stmt = $pdo->prepare('SELECT id_jeu FROM offre_jeu WHERE id_offre = ?'); $stmt->execute([$offreId]);
    $selection = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
$etats = array_values(array_unique(['Disponible', 'Indisponible', (string)$offre['etat']]));
$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!offre_csrf_valide()) { $erreurs[] = 'La session du formulaire a expiré. Veuillez réessayer.'; }
    foreach (['nom_offre','description','prix_offre','date_debut','date_fin','etat','img_offre'] as $champ) {
        $offre[$champ] = is_string($_POST[$champ] ?? null) ? trim($_POST[$champ]) : '';
    }
    $offre['prix_offre'] = str_replace(',', '.', $offre['prix_offre']);
    if ($offre['nom_offre'] === '' || offre_longueur($offre['nom_offre']) > 100) { $erreurs[] = 'Le nom doit contenir entre 1 et 100 caractères.'; }
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $offre['prix_offre'])) { $erreurs[] = 'Indiquez un prix positif ou nul avec deux décimales maximum.'; }
    foreach (['date_debut','date_fin'] as $champ) {
        if ($offre[$champ] !== '' && !offre_date_valide($offre[$champ])) { $erreurs[] = 'Une date saisie est invalide.'; }
    }
    if ($offre['date_debut'] !== '' && $offre['date_fin'] !== '' && $offre['date_debut'] > $offre['date_fin']) { $erreurs[] = 'La date de fin doit être supérieure ou égale à la date de début.'; }
    if (!in_array($offre['etat'], $etats, true)) { $erreurs[] = 'Choisissez un état valide.'; }
    // A local path or public HTTPS image URL; no executable or data URL.
    $image = $offre['img_offre'];
    if (offre_longueur($image) > 255 || ($image !== '' && !preg_match('~^(https://[^\s]+|/?[a-zA-Z0-9_.-]+(?:/[a-zA-Z0-9_.-]+)*)$~D', $image))) { $erreurs[] = 'Utilisez un chemin local ou une URL HTTPS de 255 caractères maximum pour l’image.'; }
    $raw = $_POST['jeux'] ?? [];
    $selection = [];
    if (!is_array($raw)) { $erreurs[] = 'La sélection des jeux est invalide.'; }
    else {
        foreach ($raw as $v) {
            if (!is_string($v) || !preg_match('/^\d+$/D', $v) || (int)$v < 1) { $erreurs[] = 'La sélection des jeux est invalide.'; break; }
            $selection[] = (int)$v;
        }
    }
    $selection = array_values(array_unique($selection));
    $idsValides = array_map('intval', array_column($jeux, 'id_jeu'));
    if (array_diff($selection, $idsValides)) { $erreurs[] = 'Un des jeux sélectionnés n’existe plus.'; }
    if (!$erreurs) {
        try {
            $pdo->beginTransaction();
            if ($offreId) {
                $lock = $pdo->prepare('SELECT id_offre FROM offre WHERE id_offre = ? FOR UPDATE'); $lock->execute([$offreId]);
                if (!$lock->fetchColumn()) { throw new RuntimeException('Offre supprimée par un autre administrateur.'); }
            }
            $valeurs = [$offre['nom_offre'], $offre['description'] ?: null, $offre['prix_offre'], $offre['date_debut'] ?: null, $offre['date_fin'] ?: null, $offre['etat'], $offre['img_offre'] ?: null];
            if ($offreId) {
                $stmt = $pdo->prepare('UPDATE offre SET nom_offre=?, description=?, prix_offre=?, date_debut=?, date_fin=?, etat=?, img_offre=? WHERE id_offre=?');
                $stmt->execute(array_merge($valeurs, [$offreId]));
                $stmt = $pdo->prepare('DELETE FROM offre_jeu WHERE id_offre=?'); $stmt->execute([$offreId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO offre (nom_offre,description,prix_offre,date_debut,date_fin,etat,img_offre) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute($valeurs); $offreId = (int)$pdo->lastInsertId();
            }
            $stmt = $pdo->prepare('INSERT INTO offre_jeu (id_offre,id_jeu) VALUES (?,?)');
            foreach ($selection as $idJeu) { $stmt->execute([$offreId, $idJeu]); }
            $pdo->commit(); offre_flash('L’offre a bien été enregistrée.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Gestion offres : ' . $e->getMessage());
            $erreurs[] = 'Enregistrement impossible. Vérifiez que l’offre et les jeux sélectionnés existent encore, puis réessayez.';
        }
    }
}
$titre = $offreId ? 'Modifier l’offre' : 'Ajouter une offre';
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= offre_h($titre) ?> | Parc des Princes</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>
<main><div class="page-header"><div><h1><?= offre_h($titre) ?></h1><p>Définissez votre formule et les jeux qu’elle inclut.</p></div><a href="index.php" class="btn-secondary">Retour aux offres</a></div>
<div class="form-box">
<?php if ($erreurs): ?><div class="alert-error" role="alert"><ul><?php foreach (array_unique($erreurs) as $e): ?><li><?= offre_h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= offre_h(offre_token()) ?>"><div class="form-grid">
<div class="form-group"><label for="nom">Nom de l’offre *</label><input id="nom" name="nom_offre" maxlength="100" required value="<?= offre_h($offre['nom_offre']) ?>"></div>
<div class="form-group"><label for="prix">Prix de l’offre (DH) *</label><input id="prix" name="prix_offre" type="number" min="0" max="99999999.99" step="0.01" required value="<?= offre_h($offre['prix_offre']) ?>"></div>
<div class="form-group"><label for="debut">Date de début</label><input id="debut" name="date_debut" type="date" value="<?= offre_h($offre['date_debut']) ?>"><span class="field-help">Facultatif : vide = pas de limite de début.</span></div>
<div class="form-group"><label for="fin">Date de fin</label><input id="fin" name="date_fin" type="date" value="<?= offre_h($offre['date_fin']) ?>"><span class="field-help">Facultatif : vide = pas de limite de fin.</span></div>
<div class="form-group"><label for="etat">État *</label><select id="etat" name="etat" required><?php foreach ($etats as $e): ?><option value="<?= offre_h($e) ?>" <?= $offre['etat'] === $e ? 'selected' : '' ?>><?= offre_h($e) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="image">Image (chemin ou URL HTTPS)</label><input id="image" name="img_offre" maxlength="255" value="<?= offre_h($offre['img_offre']) ?>" placeholder="Client/assets/images/offre.png"><span class="field-help">Référence une image existante ; aucun fichier à téléverser.</span></div>
<div class="form-group full-width"><label for="description">Description</label><textarea id="description" name="description"><?= offre_h($offre['description']) ?></textarea></div>
<fieldset class="form-group full-width" style="border:0;padding:0;margin:0"><legend class="form-label">Jeux inclus</legend><p class="field-help">Sélectionnez les jeux associés à cette offre.</p><div class="checkbox-list"><?php foreach ($jeux as $jeu): ?><label><input type="checkbox" name="jeux[]" value="<?= (int)$jeu['id_jeu'] ?>" <?= in_array((int)$jeu['id_jeu'], $selection, true) ? 'checked' : '' ?>><?= offre_h($jeu['nom']) ?></label><?php endforeach; ?><?php if (!$jeux): ?><p class="field-help">Aucun jeu enregistré pour le moment.</p><?php endif; ?></div></fieldset>
</div><div class="form-actions"><button class="btn-primary" type="submit">Enregistrer l’offre</button><a class="btn-secondary" href="index.php">Annuler</a></div></form>
</div></main></body></html>
