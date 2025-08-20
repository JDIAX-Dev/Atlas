<?php
// ajax/place_session.php
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

if (!$input || !isset($input['session_id']) || !isset($input['week']) || !isset($input['day'])) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

$session_id = (int)$input['session_id'];
$week = (int)$input['week'];
$day = (int)$input['day'];

// Validation des données
if ($week < 0 || $day < 0 || $day > 7) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit();
}

try {
    // Vérifier que la séance appartient à un programme du coach
    $stmt = $pdo->prepare("
        SELECT ts.id, tp.duration_weeks, tp.sessions_per_week 
        FROM training_sessions ts
        JOIN training_programs tp ON tp.id = ts.program_id
        WHERE ts.id = ? AND tp.coach_id = ?
    ");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $session_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session_info) {
        echo json_encode(['success' => false, 'error' => 'Séance non trouvée']);
        exit();
    }
    
    // Vérifier que la semaine est dans les limites du programme
    if ($week > $session_info['duration_weeks']) {
        echo json_encode(['success' => false, 'error' => 'Semaine hors limites du programme']);
        exit();
    }
    
    // Vérifier s'il y a déjà une séance à cette position
    $stmt = $pdo->prepare("
        SELECT ts.id, ts.session_name 
        FROM training_sessions ts
        JOIN training_programs tp ON tp.id = ts.program_id
        WHERE tp.coach_id = ? AND ts.week_number = ? AND ts.day_of_week = ? 
        AND ts.program_id = (SELECT program_id FROM training_sessions WHERE id = ?)
        AND ts.id != ?
    ");
    $stmt->execute([$_SESSION['user_id'], $week, $day, $session_id, $session_id]);
    $existing_session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_session) {
        echo json_encode([
            'success' => false, 
            'error' => 'Il y a déjà une séance à cette position : ' . $existing_session['session_name']
        ]);
        exit();
    }
    
    // Mettre à jour la position de la séance
    $stmt = $pdo->prepare("
        UPDATE training_sessions 
        SET week_number = ?, day_of_week = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $result = $stmt->execute([$week, $day, $session_id]);
    
    if ($result) {
        // Log de l'action (optionnel)
        error_log("Session {$session_id} placed at week {$week}, day {$day} by coach {$_SESSION['user_id']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Séance placée avec succès',
            'session_id' => $session_id,
            'week' => $week,
            'day' => $day
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors du placement']);
    }
    
} catch (PDOException $e) {
    error_log('Erreur placement séance: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>