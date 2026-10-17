<?php
require_once __DIR__.'/_commun.php';
$id=$promoId??null;
$f=$id?promo_lire($pdo,$id):['id_offre'=>(int)($_GET['offre']??0),'nom_promo'=>'','categorie_ticket'=>'special','type_promo'=>'forfait','valeur'=>'','condition_min'=>1,'capacite_membres'=>1,'delai_validite'=>1,'date_fin'=>'','etat'=>'Actif','description_jeux'=>''];
$offres=$pdo->query('SELECT id_offre,nom_offre,etat,date_debut,date_fin FROM offre ORDER BY nom_offre')->fetchAll(PDO::FETCH_ASSOC);
$erreurs=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    foreach(['id_offre','nom_promo','categorie_ticket','type_promo','valeur','condition_min','capacite_membres','delai_validite','date_fin','etat','description_jeux'] as $key)$f[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
    $f['valeur']=str_replace(',','.',$f['valeur']);
    if(!offre_csrf_valide())$erreurs[]='Formulaire expiré. Rechargez la page puis réessayez.';
    if(!ctype_digit((string)$f['id_offre']) || (int)$f['id_offre']<1)$erreurs[]='Choisissez une annonce.';
    if($f['nom_promo']==='' || offre_longueur($f['nom_promo'])>100)$erreurs[]='Le nom doit contenir entre 1 et 100 caractères.';
    if(!isset(promo_categories()[$f['categorie_ticket']]))$erreurs[]='Catégorie de ticket invalide.';
    if(!isset(promo_types()[$f['type_promo']]))$erreurs[]='Choisissez un mode de calcul du tarif.';
    if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/D',$f['valeur']) || ($f['type_promo']==='pourcentage' && (float)$f['valeur']>100))$erreurs[]='Valeur invalide : montant positif ou nul, ou pourcentage entre 0 et 100.';
    foreach(['condition_min','capacite_membres','delai_validite'] as $key){if(filter_var($f[$key],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]])===false)$erreurs[]='Minimum, maximum et validité : nombre entier de 1 à 1000.';}
    if((int)$f['condition_min']>(int)$f['capacite_membres'])$erreurs[]='Le minimum de personnes dépasse le maximum.';
    if($f['date_fin']!=='' && !offre_date_valide($f['date_fin']))$erreurs[]='Date de fin invalide.';
    if(!in_array($f['etat'],['Actif','Inactif'],true))$erreurs[]='État invalide.';
    if(offre_longueur($f['description_jeux'])>255)$erreurs[]='La description est limitée à 255 caractères.';
    if(!$erreurs){
        try{
            $pdo->beginTransaction();
            $s=$pdo->prepare('SELECT * FROM offre WHERE id_offre=? FOR UPDATE');$s->execute([(int)$f['id_offre']]);$annonce=$s->fetch(PDO::FETCH_ASSOC);
            if(!$annonce)throw new DomainException('L’annonce sélectionnée n’existe plus.');
            $jeuxInclus=promo_jeux($pdo,(int)$f['id_offre']);
            if(!$jeuxInclus)throw new DomainException('Ajoutez au moins un jeu à cette annonce avant de créer son ticket.');
            if($f['date_fin']!=='' && (($annonce['date_debut'] && $f['date_fin']<$annonce['date_debut']) || ($annonce['date_fin'] && $f['date_fin']>$annonce['date_fin'])))throw new DomainException('La date de fin du ticket doit être comprise dans la période de l’annonce. Laissez-la vide pour reprendre la fin de l’annonce.');
            $args=[(int)$f['id_offre'],$f['nom_promo'],$f['categorie_ticket'],$f['type_promo'],$f['valeur'],(int)$f['condition_min'],(int)$f['capacite_membres'],(int)$f['delai_validite'],$f['date_fin']?:null,$f['etat'],$f['description_jeux']?:null];
            if($id){
                $s=$pdo->prepare('SELECT id_promo FROM promotion_billet WHERE id_promo=? FOR UPDATE');$s->execute([$id]);
                if(!$s->fetchColumn())throw new DomainException('Ce ticket a été supprimé.');
                $s=$pdo->prepare('UPDATE promotion_billet SET id_offre=?,nom_promo=?,categorie_ticket=?,type_promo=?,valeur=?,condition_min=?,capacite_membres=?,delai_validite=?,date_fin=?,etat=?,description_jeux=? WHERE id_promo=?');$s->execute(array_merge($args,[$id]));
            }else{
                $s=$pdo->prepare('INSERT INTO promotion_billet (id_offre,nom_promo,categorie_ticket,type_promo,valeur,condition_min,capacite_membres,delai_validite,date_fin,etat,description_jeux) VALUES (?,?,?,?,?,?,?,?,?,?,?)');$s->execute($args);
            }
            $pdo->commit();promo_flash('Le ticket a bien été enregistré.');
        }catch(DomainException $e){if($pdo->inTransaction())$pdo->rollBack();$erreurs[]=$e->getMessage();}
        catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Ticket promo : '.$e->getMessage());$erreurs[]='Enregistrement impossible. Vérifiez que le SQL de mise à jour a été exécuté et réessayez.';}
    }
}
$jeuxParOffre=[];foreach($offres as $o)$jeuxParOffre[(int)$o['id_offre']]=promo_jeux($pdo,(int)$o['id_offre']);
$title=$id?'Modifier le ticket':'Ajouter un ticket';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=offre_h($title)?></title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/offres-admin.css"></head><body><?php require __DIR__.'/../includes/sidebar.php';?><main>
<div class="page-header"><div><h1><?=offre_h($title)?></h1><p>Définissez le tarif et les conditions ; les jeux sont ceux de l’annonce choisie.</p></div><a class="btn-secondary" href="index.php">Retour aux tickets</a></div>
<?php if($erreurs):?><div class="alert-error" role="alert"><ul><?php foreach(array_unique($erreurs) as $e):?><li><?=offre_h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<?php if(!$offres):?><p class="alert-error">Créez d’abord une annonce avec ses jeux. <a href="../offres/ajouter.php">Ajouter une annonce</a></p><?php endif;?>
<div class="form-box"><form method="post"><input type="hidden" name="csrf" value="<?=offre_h(offre_token())?>"><div class="form-grid">
<div class="form-group full-width"><label for="id_offre">Annonce associée *</label><select id="id_offre" name="id_offre" required><option value="">Choisir une annonce</option><?php foreach($offres as $o):?><option value="<?=(int)$o['id_offre']?>" <?=(int)$f['id_offre']===(int)$o['id_offre']?'selected':''?>><?=offre_h($o['nom_offre'].' — '.$o['etat'])?></option><?php endforeach;?></select></div>
<div class="form-group full-width"><label>Jeux inclus, hérités de l’annonce</label><div class="promo-games" id="jeux-inclus" aria-live="polite"></div><p class="field-help">Pour changer les jeux, modifiez l’annonce. Tous ses tickets reprendront cette sélection.</p></div>
<div class="form-group"><label for="nom_promo">Nom du ticket *</label><input id="nom_promo" name="nom_promo" required maxlength="100" value="<?=offre_h($f['nom_promo'])?>"></div>
<div class="form-group"><label for="categorie_ticket">Catégorie du ticket *</label><select id="categorie_ticket" name="categorie_ticket"><?php foreach(promo_categories() as $v=>$label):?><option value="<?=$v?>" <?=$f['categorie_ticket']===$v?'selected':''?>><?=$label?></option><?php endforeach;?></select></div>
<div class="form-group"><label for="type_promo">Mode de calcul *</label><select id="type_promo" name="type_promo"><?php if(!isset(promo_types()[$f['type_promo']])):?><option value="" selected>Ancien type : choisir un mode</option><?php endif;?><?php foreach(promo_types() as $v=>$label):?><option value="<?=$v?>" <?=$f['type_promo']===$v?'selected':''?>><?=$label?></option><?php endforeach;?></select></div>
<div class="form-group"><label for="valeur">Valeur (DH ou %) *</label><input id="valeur" type="number" name="valeur" min="0" max="99999999.99" step=".01" required value="<?=offre_h($f['valeur'])?>"></div>
<?php foreach(['condition_min'=>'Minimum de personnes','capacite_membres'=>'Maximum de personnes','delai_validite'=>'Validité du billet (jours)'] as $key=>$label):?><div class="form-group"><label for="<?=$key?>"><?=$label?> *</label><input id="<?=$key?>" type="number" name="<?=$key?>" min="1" max="1000" required value="<?=offre_h($f[$key])?>"></div><?php endforeach;?>
<div class="form-group"><label for="date_fin">Date de fin du ticket</label><input id="date_fin" type="date" name="date_fin" value="<?=offre_h($f['date_fin'])?>"><span class="field-help">Vide : utilise la date de fin de l’annonce. Le début vient aussi de l’annonce.</span></div>
<div class="form-group"><label for="etat">État *</label><select id="etat" name="etat"><?php foreach(['Actif','Inactif'] as $e):?><option <?=$f['etat']===$e?'selected':''?>><?=$e?></option><?php endforeach;?></select></div>
<div class="form-group full-width"><label for="description_jeux">Description / conditions</label><textarea id="description_jeux" name="description_jeux" maxlength="255"><?=offre_h($f['description_jeux'])?></textarea></div>
<div class="form-group full-width"><div class="promo-preview" id="tarif-apercu" role="status" aria-live="polite"></div><p class="field-help">Aperçu administratif pour le nombre minimum de personnes. Aucun billet ni réservation n’est créé ici.</p></div>
</div><div class="form-actions"><button type="submit" class="btn-primary" <?=$offres?'':'disabled'?>>Enregistrer le ticket</button><a class="btn-secondary" href="index.php">Annuler</a></div></form></div>
<script id="promo-games-data" type="application/json"><?=json_encode($jeuxParOffre,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE)?></script>
<script src="../assets/js/promotions-admin.js"></script></main></body></html>
