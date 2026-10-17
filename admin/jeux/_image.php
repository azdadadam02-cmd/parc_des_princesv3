<?php

// ===========================
// Images des jeux
// ===========================

function jeu_racine_web(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $position = strpos($script, '/admin/');

    if ($position === false) {
        return '';
    }

    return substr($script, 0, $position);
}

function jeu_image_affichage($image): string
{
    $image = trim((string)($image ?? ''));

    if ($image === '') {
        return '';
    }

    if (
        preg_match('~^https?://~i', $image) ||
        strpos($image, '/') === 0
    ) {
        return $image;
    }

    if (
        strpos($image, 'Client/') === 0 ||
        strpos($image, 'admin/') === 0
    ) {
        return jeu_racine_web() . '/' . $image;
    }

    // Anciennes photos enregistrées uniquement avec leur nom de fichier.
    return jeu_racine_web() . '/admin/uploads/jeux/' . rawurlencode($image);
}

function jeu_recevoir_image($image_actuelle, bool $obligatoire): array
{
    $fichier = $_FILES['img_jeu'] ?? null;
    $url = $_POST['img_jeu_url'] ?? '';

    if (!is_string($url)) {
        throw new DomainException("Le lien de l'image est invalide.");
    }

    $url = trim($url);

    // Un fichier choisi sur le PC est prioritaire sur le lien URL.
    if ($fichier && ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (
            !is_int($fichier['error'] ?? null) ||
            $fichier['error'] !== UPLOAD_ERR_OK
        ) {
            throw new DomainException("L'image n'a pas pu être envoyée. Vérifiez sa taille et réessayez.");
        }

        if (
            !is_string($fichier['tmp_name'] ?? null) ||
            !is_uploaded_file($fichier['tmp_name']) ||
            ($fichier['size'] ?? 0) <= 0 ||
            $fichier['size'] > 8 * 1024 * 1024
        ) {
            throw new DomainException("L'image doit être un fichier de 8 Mo maximum.");
        }

        $formats = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($fichier['tmp_name']);

        if (
            !isset($formats[$type]) ||
            !getimagesize($fichier['tmp_name'])
        ) {
            throw new DomainException("Choisissez une vraie image JPG, PNG ou WEBP.");
        }

        $dossier = __DIR__ . '/../uploads/jeux/';

        if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
            throw new RuntimeException("Le dossier des images est inaccessible.");
        }

        $nom_fichier = 'jeu_' . bin2hex(random_bytes(16)) . '.' . $formats[$type];
        $chemin_fichier = $dossier . $nom_fichier;

        if (!move_uploaded_file($fichier['tmp_name'], $chemin_fichier)) {
            throw new RuntimeException("L'enregistrement de l'image a échoué.");
        }

        // Chemin utilisable directement depuis Admin, Client et le panier.
        $chemin_web = jeu_racine_web() . '/admin/uploads/jeux/' . $nom_fichier;

        return [$chemin_web, $chemin_fichier];
    }

    if ($url !== '') {
        $schema = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        if (
            strlen($url) > 255 ||
            !filter_var($url, FILTER_VALIDATE_URL) ||
            !in_array($schema, ['http', 'https'], true) ||
            parse_url($url, PHP_URL_USER) !== null ||
            parse_url($url, PHP_URL_PASS) !== null
        ) {
            throw new DomainException("Saisissez un lien HTTP ou HTTPS valide, de 255 caractères maximum.");
        }

        return [$url, null];
    }

    if ($obligatoire && empty($image_actuelle)) {
        throw new DomainException("Choisissez une image sur votre PC ou renseignez son URL.");
    }

    return [$image_actuelle, null];
}
