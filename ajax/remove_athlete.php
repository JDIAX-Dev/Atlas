<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['athlete_id'])) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

$athlete_id = (int)$_POST['athlete_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM coach_athlete_relations WHERE coach_id = ? AND athlete_id = ?");
    $result = $stmt->execute([$_SESSION['user_id'], $athlete_id]);
    
    echo json_encode(['success' => $result]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>