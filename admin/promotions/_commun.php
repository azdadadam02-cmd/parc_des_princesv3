<?php
require_once __DIR__ . '/../offres/_commun.php';
try { $pdo->query('SELECT id_offre,categorie_ticket FROM promotion_billet LIMIT 0'); }
catch (PDOException $e) { http_response_code(503); exit('Mise à jour requise : importez database/ajout_admin_offres.sql dans votre base via phpMyAdmin.'); }
function promo_categories(): array { return ['groups'=>'Groupes','special'=>'Spécial','journee'=>'Journée']; }
function promo_types(): array { return ['forfait'=>'Prix fixe par personne (DH)','pourcentage'=>'Réduction en % sur les jeux inclus','reduction'=>'Réduction en DH par personne']; }
function promo_prix(array $p): string {
    $v=number_format((float)$p['valeur'],2,',',' ');
    if ($p['type_promo']==='forfait') return $v.' DH / personne';
    if ($p['type_promo']==='pourcentage') return '−'.$v.' % / personne';
    if ($p['type_promo']==='reduction') return '−'.$v.' DH / personne';
    return $v.' (type à configurer)';
}
function promo_lire(PDO $pdo,int $id): array {
    $s=$pdo->prepare('SELECT * FROM promotion_billet WHERE id_promo=?');$s->execute([$id]);$p=$s->fetch(PDO::FETCH_ASSOC);
    if (!$p) { http_response_code(404);exit('Ticket introuvable.'); } return $p;
}
function promo_id(): int {
    $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
    if (!$id || $id<1) { http_response_code(404);exit('Ticket introuvable.'); }return $id;
}
function promo_flash(string $msg): void { $_SESSION['promotions_flash']=$msg;header('Location: index.php');exit; }
function promo_jeux(PDO $pdo,int $idOffre): array {
    $s=$pdo->prepare('SELECT j.id_jeu,j.nom,j.tarif,j.etat,j.capacite FROM offre_jeu oj JOIN jeu j ON j.id_jeu=oj.id_jeu WHERE oj.id_offre=? ORDER BY j.nom');$s->execute([$idOffre]);return $s->fetchAll(PDO::FETCH_ASSOC);
}
function promo_image(?string $image): string {
    $image=trim((string)$image);
    if (preg_match('~^https://[^\s]+$~D',$image))return $image;
    if ($image!=='' && preg_match('~^/?[a-zA-Z0-9_.-]+(?:/[a-zA-Z0-9_.-]+)*$~D',$image) && !in_array('..',explode('/',$image),true))return '../../'.ltrim($image,'/');
    return '../assets/img/logo.png';
}
