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
    // Vérifier que l'athlète existe et est bien un athlète
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND user_type = 'athlete'");
    $stmt->execute([$athlete_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Athlète non trouvé']);
        exit();
    }
    
    // Ajouter la relation coach-athlète
    $stmt = $pdo->prepare("INSERT INTO coach_athlete_relations (coach_id, athlete_id) VALUES (?, ?)");
    $result = $stmt->execute([$_SESSION['user_id'], $athlete_id]);
    
    echo json_encode(['success' => $result]);
    
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Duplicate entry
        echo json_encode(['success' => false, 'error' => 'Athlète déjà ajouté']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
    }
}
?>