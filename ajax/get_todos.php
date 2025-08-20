<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, task, is_completed FROM coach_todos WHERE coach_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir is_completed en boolean
    foreach ($todos as &$todo) {
        $todo['is_completed'] = (bool)$todo['is_completed'];
    }
    
    echo json_encode($todos);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>