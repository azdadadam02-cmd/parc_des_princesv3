<?php
require_once __DIR__.'/_commun.php';
$q=trim((string)($_GET['q']??''));$cat=(string)($_GET['categorie']??'');$etat=(string)($_GET['etat']??'');$offre=(string)($_GET['offre']??'');
$w=[];$args=[];
if($q!==''){$w[]='(p.nom_promo LIKE ? OR p.description_jeux LIKE ?)';$args[]='%'.$q.'%';$args[]='%'.$q.'%';}
if($cat!==''){$w[]='p.categorie_ticket=?';$args[]=$cat;}
if($etat!==''){$w[]='p.etat=?';$args[]=$etat;}
if($offre==='sans')$w[]='p.id_offre IS NULL';
elseif(ctype_digit($offre) && (int)$offre>0){$w[]='p.id_offre=?';$args[]=(int)$offre;}
$s=$pdo->prepare('SELECT p.*,o.nom_offre,o.etat annonce_etat,o.date_debut annonce_debut,o.date_fin annonce_fin,
    (SELECT COUNT(*) FROM offre_jeu oj WHERE oj.id_offre=p.id_offre) nb_jeux
    FROM promotion_billet p LEFT JOIN offre o ON o.id_offre=p.id_offre'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY p.id_promo DESC');
$s->execute($args);$tickets=$s->fetchAll(PDO::FETCH_ASSOC);
$offres=$pdo->query('SELECT id_offre,nom_offre FROM offre ORDER BY nom_offre')->fetchAll(PDO::FETCH_ASSOC);
$flash=$_SESSION['promotions_flash']??'';unset($_SESSION['promotions_flash']);
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tickets promotionnels</title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/offres-admin.css"></head><body>
<?php require __DIR__.'/../includes/sidebar.php';?><main>
<div class="page-header"><div><h1>Tickets promotionnels</h1><p>Chaque ticket appartient à une annonce et reprend ses jeux.</p></div><div class="actions-box"><a href="../offres/index.php" class="btn-secondary">Annonces</a><a href="ajouter.php" class="btn-primary">+ Ajouter un ticket</a></div></div>
<?php if($flash):?><p class="alert-success" role="status"><?=offre_h($flash)?></p><?php endif;?>
<form class="filters-box" method="get"><input name="q" type="search" value="<?=offre_h($q)?>" placeholder="Nom ou description" aria-label="Rechercher"><select name="offre" aria-label="Annonce"><option value="">Toutes les annonces</option><option value="sans" <?=$offre==='sans'?'selected':''?>>Sans annonce</option><?php foreach($offres as $o):?><option value="<?=(int)$o['id_offre']?>" <?=$offre===(string)$o['id_offre']?'selected':''?>><?=offre_h($o['nom_offre'])?></option><?php endforeach;?></select><select name="categorie" aria-label="Catégorie"><option value="">Toutes les catégories</option><?php foreach(promo_categories() as $v=>$label):?><option value="<?=$v?>" <?=$cat===$v?'selected':''?>><?=$label?></option><?php endforeach;?></select><select name="etat" aria-label="État"><option value="">Tous les états</option><?php foreach(['Actif','Inactif'] as $e):?><option <?=$etat===$e?'selected':''?>><?=$e?></option><?php endforeach;?></select><button class="btn-primary">Filtrer</button><a href="index.php" class="btn-secondary">Réinitialiser</a></form>
<div class="table-box"><table><thead><tr><th>Ticket</th><th>Annonce / Jeux</th><th>Catégorie</th><th>Tarif</th><th>Personnes / Validité</th><th>État</th><th>Actions</th></tr></thead><tbody>
<?php foreach($tickets as $p):?><tr><td><strong><?=offre_h($p['nom_promo'])?></strong><br><small>#<?=(int)$p['id_promo']?></small></td><td><?php if($p['id_offre']):?><a href="../offres/details.php?id=<?=(int)$p['id_offre']?>"><?=offre_h($p['nom_offre'])?></a><br><small><?=(int)$p['nb_jeux']?> jeu(x) inclus</small><?php else:?><span class="field-help">À rattacher à une annonce</span><?php endif;?></td><td><?=offre_h(promo_categories()[$p['categorie_ticket']]??$p['categorie_ticket'])?></td><td><?=offre_h(promo_prix($p))?></td><td><?=max(1,(int)$p['condition_min'])?>–<?=max(1,(int)$p['capacite_membres'])?> personne(s)<br><?=max(1,(int)$p['delai_validite'])?> jour(s)</td><td><span class="status-badge"><?=offre_h($p['etat'])?></span><?php if($p['id_offre'] && ($p['annonce_etat']!=='Disponible' || ($p['annonce_debut'] && $p['annonce_debut']>date('Y-m-d')) || ($p['annonce_fin'] && $p['annonce_fin']<date('Y-m-d')) || ($p['date_fin'] && $p['date_fin']<date('Y-m-d')))):?><br><small class="field-help">Hors période ou annonce indisponible</small><?php endif;?></td><td><div class="actions-box"><a class="btn-edit" href="modifier.php?id=<?=(int)$p['id_promo']?>">Modifier</a><a class="btn-delete" href="desactiver.php?id=<?=(int)$p['id_promo']?>">Désactiver</a></div></td></tr><?php endforeach;?>
<?php if(!$tickets):?><tr><td colspan="7">Aucun ticket trouvé. Créez une annonce, puis ajoutez son premier ticket.</td></tr><?php endif;?></tbody></table></div>
</main></body></html>
