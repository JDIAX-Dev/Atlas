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
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

$session_id = (int)$input['session_id'];
$session_name = trim($input['name']);
$session_notes = trim($input['notes']) ?: null;
$exercises = $input['exercises'] ?? [];

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
    
    // Mettre à jour la séance
    $stmt = $pdo->prepare("
        UPDATE training_sessions 
        SET session_name = ?, notes = ? 
        WHERE id = ?
    ");
    $stmt->execute([$session_name, $session_notes, $session_id]);
    
    // Supprimer tous les exercices existants
    $stmt = $pdo->prepare("DELETE FROM session_exercises WHERE session_id = ?");
    $stmt->execute([$session_id]);
    
    // Ré-insérer les exercices
    if (!empty($exercises)) {
        $order = 1;
        foreach ($exercises as $exercise) {
            $stmt = $pdo->prepare("
                INSERT INTO session_exercises 
                (session_id, exercise_id, exercise_order, sets_count, reps, weight, difficulty, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $session_id,
                (int)$exercise['exercise_id'],
                $order++,
                (int)$exercise['sets'],
                $exercise['reps'],
                !empty($exercise['weight']) ? (float)$exercise['weight'] : null,
                (int)$exercise['difficulty'],
                $exercise['notes'] ?: null
            ]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Séance mise à jour'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erreur mise à jour séance: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>