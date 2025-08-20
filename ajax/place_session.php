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

try {
    // Vérifier que la séance appartient à un programme du coach
    $stmt = $pdo->prepare(
        "SELECT ts.id FROM training_sessions ts
         JOIN training_programs tp ON tp.id = ts.program_id
         WHERE ts.id = ? AND tp.coach_id = ?"
    );
    $stmt->execute([$session_id, $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Séance non trouvée']);
        exit();
    }

    // Mettre à jour la position de la séance
    $stmt = $pdo->prepare(
        "UPDATE training_sessions
         SET week_number = ?, day_of_week = ?
         WHERE id = ?"
    );
    $stmt->execute([$week, $day, $session_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erreur placement séance: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}

?>

