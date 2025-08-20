<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.created_at,
            car.created_at as relation_created_at,
            tp.id as program_id,
            tp.title as program_title,
            tp.description as program_description,
            tp.duration_weeks,
            tp.sessions_per_week,
            tp.program_type,
            tp.status as program_status
        FROM coach_athlete_relations car
        JOIN users u ON car.athlete_id = u.id
        LEFT JOIN training_programs tp ON tp.athlete_id = u.id AND tp.status = 'active'
        WHERE car.coach_id = ?
        ORDER BY car.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $athletes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    $result = [];
    foreach ($athletes as $athlete) {
        $athleteData = [
            'id' => $athlete['id'],
            'first_name' => $athlete['first_name'],
            'last_name' => $athlete['last_name'],
            'email' => $athlete['email'],
            'created_at' => $athlete['relation_created_at'],
            'active_program' => null
        ];
        
        if ($athlete['program_id']) {
            $athleteData['active_program'] = [
                'id' => $athlete['program_id'],
                'title' => $athlete['program_title'],
                'description' => $athlete['program_description'],
                'duration_weeks' => $athlete['duration_weeks'],
                'sessions_per_week' => $athlete['sessions_per_week'],
                'program_type' => $athlete['program_type'],
                'status' => $athlete['program_status']
            ];
        }
        
        $result[] = $athleteData;
    }
    
    echo json_encode($result);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>