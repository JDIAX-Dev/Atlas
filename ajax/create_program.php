<?php
// ajax/create_program.php
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

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit();
}

$athlete_id = (int)$input['athlete_id'];
$title = trim($input['title']);
$description = trim($input['description']) ?: null;
$duration_weeks = (int)$input['duration_weeks'];
$sessions_per_week = (int)$input['sessions_per_week'];
$program_type = $input['program_type'];
$squat_goal = !empty($input['squat_goal']) ? (float)$input['squat_goal'] : null;
$bench_goal = !empty($input['bench_goal']) ? (float)$input['bench_goal'] : null;
$deadlift_goal = !empty($input['deadlift_goal']) ? (float)$input['deadlift_goal'] : null;

// Validation
if (empty($title) || $duration_weeks < 1 || $sessions_per_week < 1 || empty($program_type)) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes ou invalides']);
    exit();
}

try {
    // Vérifier que l'athlète appartient bien à ce coach
    $stmt = $pdo->prepare("
        SELECT 1 FROM coach_athlete_relations 
        WHERE coach_id = ? AND athlete_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $athlete_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Athlète non trouvé']);
        exit();
    }
    
    // Désactiver les anciens programmes actifs de cet athlète
    $stmt = $pdo->prepare("
        UPDATE training_programs 
        SET status = 'completed' 
        WHERE athlete_id = ? AND status = 'active'
    ");
    $stmt->execute([$athlete_id]);
    
    // Créer le nouveau programme
    $stmt = $pdo->prepare("
        INSERT INTO training_programs (
            coach_id,
            athlete_id,
            title,
            description,
            duration_weeks,
            sessions_per_week,
            program_type,
            squat_goal,
            bench_goal,
            deadlift_goal,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $athlete_id,
        $title,
        $description,
        $duration_weeks,
        $sessions_per_week,
        $program_type,
        $squat_goal,
        $bench_goal,
        $deadlift_goal
    ]);
    
    $program_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'program_id' => $program_id,
        'message' => 'Programme créé avec succès'
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur création programme: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>