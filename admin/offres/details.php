<?php
require_once __DIR__.'/_commun.php';$id=offre_id();offre_lire($pdo,$id);header('Location: modifier.php?id='.$id);exit;
