<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['session_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session ID manquant']);
    exit();
}

$session_id = (int)$input['session_id'];

try {
    // Vérifier que la séance appartient à un programme du coach
    $stmt = $pdo->prepare("
        SELECT ts.id 
        FROM training_sessions ts
        JOIN training_programs tp ON tp.id = ts.program_id
        WHERE ts.id = ? AND tp.coach_id = ?
    ");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Séance non trouvée']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    // Supprimer d'abord les exercices de la séance
    $stmt = $pdo->prepare("DELETE FROM session_exercises WHERE session_id = ?");
    $stmt->execute([$session_id]);
    
    // Puis supprimer la séance
    $stmt = $pdo->prepare("DELETE FROM training_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Séance supprimée']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erreur suppression séance: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>