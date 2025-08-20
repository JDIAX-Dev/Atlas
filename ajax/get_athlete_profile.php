<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID manquant']);
    exit();
}

$athlete_id = (int)$_GET['id'];

try {
    // Vérifier que l'athlète appartient bien à ce coach
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.created_at
        FROM users u
        JOIN coach_athlete_relations car ON car.athlete_id = u.id
        WHERE u.id = ? AND car.coach_id = ?
    ");
    $stmt->execute([$athlete_id, $_SESSION['user_id']]);
    $athlete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$athlete) {
        echo json_encode(['error' => 'Athlète non trouvé']);
        exit();
    }
    
    // Récupérer le programme actif s'il existe
    $stmt = $pdo->prepare("
        SELECT id, title, description, duration_weeks, sessions_per_week, 
               program_type, squat_goal, bench_goal, deadlift_goal, status, created_at
        FROM training_programs 
        WHERE athlete_id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$athlete_id]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $athlete['active_program'] = $program ?: null;
    
    echo json_encode($athlete);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur base de données']);
}
?>