<?php
require_once __DIR__ . '/../../config.php';

$demandeId = isset($_GET['demande_id']) ? (int) $_GET['demande_id'] : 0;
$target = $demandeId > 0
    ? front_url('proposition.php?demande_id=' . $demandeId)
    : front_url('mes-demandes.php');

header('Location: ' . $target);
exit;
