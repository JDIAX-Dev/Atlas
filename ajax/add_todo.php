<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['task'])) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

$task = trim($_POST['task']);
if (empty($task)) {
    echo json_encode(['success' => false, 'error' => 'Tâche vide']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO coach_todos (coach_id, task) VALUES (?, ?)");
    $result = $stmt->execute([$_SESSION['user_id'], $task]);
    
    echo json_encode(['success' => $result]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>