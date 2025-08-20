<?php
// ajax/get_sessions.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['program_id'])) {
    echo json_encode(['success' => false, 'error' => 'Program ID manquant']);
    exit();
}

$program_id = (int)$_GET['program_id'];

try {
    // Vérifier que le programme appartient bien à ce coach
    $stmt = $pdo->prepare("SELECT id FROM training_programs WHERE id = ? AND coach_id = ?");
    $stmt->execute([$program_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Programme non trouvé']);
        exit();
    }
    
    // Récupérer toutes les séances du programme avec leurs exercices
    $stmt = $pdo->prepare("
        SELECT 
            ts.id,
            ts.session_name,
            ts.notes,
            ts.week_number,
            ts.day_of_week,
            COUNT(se.id) as exercise_count
        FROM training_sessions ts
        LEFT JOIN session_exercises se ON se.session_id = ts.id
        WHERE ts.program_id = ?
        GROUP BY ts.id, ts.session_name, ts.notes, ts.week_number, ts.day_of_week
        ORDER BY ts.week_number, ts.day_of_week, ts.session_name
    ");
    $stmt->execute([$program_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque séance, récupérer les détails des exercices
    $detailed_sessions = [];
    foreach ($sessions as $session) {
        $stmt = $pdo->prepare("
            SELECT 
                se.exercise_id,
                se.sets_count,
                se.reps,
                se.weight,
                se.difficulty,
                se.notes as exercise_notes,
                e.name as exercise_name,
                se.exercise_order
            FROM session_exercises se
            JOIN exercises e ON e.id = se.exercise_id
            WHERE se.session_id = ?
            ORDER BY se.exercise_order
        ");
        $stmt->execute([$session['id']]);
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les exercices pour l'affichage
        $formatted_exercises = [];
        foreach ($exercises as $exercise) {
            $formatted_exercises[] = [
                'exercise_id' => $exercise['exercise_id'],
                'exercise_name' => $exercise['exercise_name'],
                'sets_count' => (int)$exercise['sets_count'],
                'reps' => $exercise['reps'],
                'weight' => $exercise['weight'] ? (float)$exercise['weight'] : null,
                'difficulty' => (int)$exercise['difficulty'],
                'notes' => $exercise['exercise_notes'],
                'order' => (int)$exercise['exercise_order']
            ];
        }
        
        $session_data = [
            'id' => (int)$session['id'],
            'name' => $session['session_name'],
            'notes' => $session['notes'],
            'exerciseCount' => (int)$session['exercise_count'],
            'week' => (int)$session['week_number'],
            'day' => (int)$session['day_of_week'],
            'exercises' => $formatted_exercises
        ];
        
        $detailed_sessions[] = $session_data;
    }
    
    // Séparer les séances placées et celles en library
    $library_sessions = [];
    $placed_sessions = [];
    
    foreach ($detailed_sessions as $session) {
        if ($session['week'] == 0 && $session['day'] == 0) {
            $library_sessions[] = $session;
        } else {
            $placed_sessions[] = $session;
        }
    }
    
    echo json_encode([
        'success' => true,
        'library_sessions' => $library_sessions,
        'placed_sessions' => $placed_sessions
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur récupération séances: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>