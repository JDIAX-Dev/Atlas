<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

$todo_id = (int)$_POST['id'];

try {
    // Basculer l'état de la tâche
    $stmt = $pdo->prepare("UPDATE coach_todos SET is_completed = NOT is_completed WHERE id = ? AND coach_id = ?");
    $result = $stmt->execute([$todo_id, $_SESSION['user_id']]);
    
    echo json_encode(['success' => $result]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>