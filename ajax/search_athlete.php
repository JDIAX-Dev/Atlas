<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode(['found' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    echo json_encode(['found' => false, 'error' => 'Email manquant']);
    exit();
}

$email = trim($_POST['email']);

try {
    // Rechercher l'athlète par email
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE email = ? AND user_type = 'athlete'");
    $stmt->execute([$email]);
    $athlete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$athlete) {
        echo json_encode(['found' => false]);
        exit();
    }
    
    // Vérifier si l'athlète n'est pas déjà ajouté
    $stmt = $pdo->prepare("SELECT id FROM coach_athlete_relations WHERE coach_id = ? AND athlete_id = ?");
    $stmt->execute([$_SESSION['user_id'], $athlete['id']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode([
            'found' => true,
            'already_added' => true,
            'athlete' => $athlete
        ]);
    } else {
        echo json_encode([
            'found' => true,
            'already_added' => false,
            'athlete' => $athlete
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['found' => false, 'error' => 'Erreur base de données']);
}
?>