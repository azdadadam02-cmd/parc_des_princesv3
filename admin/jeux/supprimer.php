<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    header("Location: index.php");
    exit;
}

try {

    $stmt = $pdo->prepare("
        DELETE FROM jeu
        WHERE id_jeu = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    header("Location: index.php");
    exit;

} catch (PDOException $e) {

    die(
        "Impossible de supprimer ce jeu : "
        . $e->getMessage()
    );
}