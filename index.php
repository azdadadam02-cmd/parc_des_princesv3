<?php 
session_start(); 
require __DIR__ . '/admin/includes/db.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Accueil</title>
    <link rel="stylesheet" href="Client/assets/css/index.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
     <?php include 'Client/includes/navbar.php'; ?>

    
    <section class="section1">
        <div class="down">
    
        </div>
        <a href="#section2" class="btn-browse">voire les tiket</a>
    </section>

    <section class="section2" id="section2">
        <div class="tiket">

        </div>
    </section>
    <section class = "section2-2">

            
    </section>

    <section class="section3">
        <a href="Client/catg2.php" class="btn-browse-CAT1">DÉCOUVRIR</a>   
    </section>

    <section class="section4">
        <a href="Client/catg1.php" class="btn-browse-CAT2">DÉCOUVRIR</a>
    </section>

    <section class="section5">
        <a href="Client/catg3.php" class="btn-browse-CAT3">DÉCOUVRIR</a>
    </section>

    <section class="section6">
        <a href="Client/catg4.php" class="btn-browse-CAT4">DÉCOUVRIR</a>
    </section>

    <script src="Client/assets/js/index.js"></script>
</body>
</html>
