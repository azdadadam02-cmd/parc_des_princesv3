<?php
require_once __DIR__ . '/_commun.php';
$error='';$cin=exp_cin((string)($_GET['cin']??''));
if($_SERVER['REQUEST_METHOD']==='POST') {
 try {
    exp_csrf();$pdo->beginTransaction();
    if(($_POST['action']??'')==='lever') {
        $motif=trim((string)($_POST['motif_levee']??''));
        if(strlen($motif)<5 || strlen($motif)>2000)throw new DomainException('Précisez la raison de la levée (5 à 2000 octets).');
        $s=$pdo->prepare('UPDATE signalement_client SET actif=0,leve_par=?,leve_le=NOW(),motif_levee=? WHERE id_signalement=? AND actif=1');
        $s->execute([$_SESSION['admin_email'],$motif,(int)($_POST['id']??0)]);
        if($s->rowCount()!==1)throw new DomainException('Signalement déjà levé ou introuvable.');
    } else {
        $cin=exp_cin((string)($_POST['cin']??''));$motif=trim((string)($_POST['motif']??''));
        if(!exp_cin_valide($cin))throw new DomainException('CIN invalide (4 à 20 lettres/chiffres).');
        if(strlen($motif)<5 || strlen($motif)>2000)throw new DomainException('Décrivez l’incident (5 à 2000 octets).');
        $s=$pdo->prepare('INSERT INTO signalement_client (cin,motif,cree_par) VALUES (?,?,?)');$s->execute([$cin,$motif,$_SESSION['admin_email']]);
    }
    $pdo->commit();header('Location: index.php?saved=1');exit;
 } catch(DomainException $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();}
 catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log($e->getMessage());$error='Enregistrement impossible. Vérifiez la migration SQL.';}
}
$s=$pdo->prepare('SELECT * FROM signalement_client'.($cin!==''?' WHERE cin=?':'').' ORDER BY actif DESC,cree_le DESC,id_signalement DESC');$s->execute($cin!==''?[$cin]:[]);$rows=$s->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Signalements CIN</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body><?php require __DIR__.'/../includes/sidebar.php';?><main>
<div class="page-header"><div><h1>Signalements des clients</h1><p>Un signalement actif empêche les nouvelles réservations pour ce CIN, y compris avec un autre compte.</p></div><a class="btn-secondary" href="../clients/index.php">Clients</a></div>
<?php if($error):?><p class="alert-error" role="alert"><?=exp_h($error)?></p><?php endif;?><?php if(isset($_GET['saved'])):?><p class="alert-success">Modification enregistrée.</p><?php endif;?>
<div class="form-box"><h2>Signaler un incident</h2><form method="post"><input type="hidden" name="csrf" value="<?=exp_h(exp_token())?>"><div class="form-grid"><div class="form-group"><label>CIN *</label><input name="cin" value="<?=exp_h($cin)?>" required maxlength="30"></div><div class="form-group full-width"><label>Motif de l’incident *</label><textarea name="motif" required minlength="5" maxlength="2000" placeholder="Date de l’incident et faits constatés"></textarea></div></div><div class="form-actions"><button class="btn-primary">Activer le signalement</button></div></form></div>
<form method="get" class="filters-box" style="margin-top:24px"><input name="cin" placeholder="Rechercher un CIN" value="<?=exp_h($cin)?>"><button class="btn-primary">Rechercher</button><a href="index.php" class="btn-secondary">Tout voir</a></form>
<div class="table-box"><table><thead><tr><th>CIN</th><th>Incident</th><th>Statut</th><th>Historique / action</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=exp_h($r['cin'])?></td><td style="white-space:pre-line"><?=exp_h($r['motif'])?><br><small><?=exp_h($r['cree_le'].' — '.$r['cree_par'])?></small></td><td><?=$r['actif']?'Blocage actif':'Levé'?></td><td><?php if($r['actif']):?><form method="post"><input type="hidden" name="csrf" value="<?=exp_h(exp_token())?>"><input type="hidden" name="action" value="lever"><input type="hidden" name="id" value="<?=(int)$r['id_signalement']?>"><label>Raison de la levée<input name="motif_levee" required minlength="5" maxlength="2000"></label><button class="btn-secondary">Lever le signalement</button></form><?php else:?><?=exp_h($r['leve_le'].' — '.$r['leve_par'])?><br><?=exp_h($r['motif_levee'])?><?php endif;?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="4">Aucun signalement.</td></tr><?php endif;?></tbody></table></div></main></body></html>
