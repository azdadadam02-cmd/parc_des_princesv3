<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$email = $_GET['email'] ?? '';

if (empty($email)) {
    header("Location: index.php");
    exit;
}


$stmt = $pdo->prepare("
    SELECT email
    FROM responsable_de_jeu
    WHERE email = :email
");

$stmt->execute([
    ':email' => $email
]);

$responsable = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$responsable) {
    die("Responsable introuvable.");
}


try {

    $pdo->beginTransaction();


    

    $stmt = $pdo->prepare("
        UPDATE responsable_de_jeu
        SET statut_responsable = 'inactif'
        WHERE email = :email
    ");

    $stmt->execute([
        ':email' => $email
    ]);


    

    $stmt = $pdo->prepare("
        UPDATE jeu
        SET email_responsable = NULL
        WHERE email_responsable = :email
    ");

    $stmt->execute([
        ':email' => $email
    ]);


    $pdo->commit();


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        "Erreur : " .
        $e->getMessage()
    );
}


header("Location: index.php");
exit;