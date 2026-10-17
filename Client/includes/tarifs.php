<?php

function client_prix_jeu(array $jeu): float {
    $normal=(float)$jeu['tarif'];
    $promo=$jeu['tarif_promo']??null;
    if($promo!==null && $promo!=='' && is_numeric($promo) && (float)$promo>=0 && (float)$promo<$normal)return (float)$promo;
    return $normal;
}
function client_actualiser_tarifs_panier(PDO $pdo): void {
    if(empty($_SESSION['panier']) || !is_array($_SESSION['panier']))return;
    $ids=[];
    foreach($_SESSION['panier'] as $ticket){
        if((int)($ticket['id_promo']??0)>0)continue;
        $id=(int)($ticket['id_jeu']??$ticket['id']??0);
        if($id>0)$ids[$id]=$id;
    }
    if(!$ids)return;
    $s=$pdo->prepare('SELECT id_jeu,tarif,tarif_promo FROM jeu WHERE id_jeu IN ('.implode(',',array_fill(0,count($ids),'?')).')');
    $s->execute(array_values($ids));$jeux=$s->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
    foreach($_SESSION['panier'] as &$ticket){
        if((int)($ticket['id_promo']??0)>0)continue;
        $id=(int)($ticket['id_jeu']??$ticket['id']??0);
        if(isset($jeux[$id]))$ticket['tarif']=client_prix_jeu($jeux[$id]);
    }
    unset($ticket);
}
