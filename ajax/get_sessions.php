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

    // Récupérer toutes les séances et leurs exercices
    $stmt = $pdo->prepare(
        "SELECT
            ts.id AS session_id,
            ts.session_name,
            ts.notes AS session_notes,
            ts.week_number,
            ts.day_of_week,
            se.exercise_id,
            se.exercise_order,
            se.sets_count,
            se.reps,
            se.difficulty,
            se.weight_kg,
            se.notes AS exercise_notes,
            e.name AS exercise_name
        FROM training_sessions ts
        LEFT JOIN session_exercises se ON se.session_id = ts.id
        LEFT JOIN exercises e ON e.id = se.exercise_id
        WHERE ts.program_id = ?
        ORDER BY ts.week_number, ts.day_of_week, ts.session_name, se.exercise_order"
    );
    $stmt->execute([$program_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sessions = [];
    foreach ($rows as $row) {
        $id = (int)$row['session_id'];
        if (!isset($sessions[$id])) {
            $sessions[$id] = [
                'id' => $id,
                'name' => $row['session_name'],
                'notes' => $row['session_notes'],
                'week' => (int)$row['week_number'],
                'day' => (int)$row['day_of_week'],
                'exercises' => []
            ];
        }

        if ($row['exercise_id']) {
            $sessions[$id]['exercises'][] = [
                'exercise_id' => (int)$row['exercise_id'],
                'exercise_name' => $row['exercise_name'],
                'sets' => (int)$row['sets_count'],
                'reps' => $row['reps'],
                'difficulty' => (int)$row['difficulty'],
                'weight' => $row['weight_kg'] !== null ? (float)$row['weight_kg'] : null,
                'notes' => $row['exercise_notes']
            ];
        }
    }

    $library_sessions = [];
    $placed_sessions = [];
    foreach ($sessions as $session) {
        $session['exerciseCount'] = count($session['exercises']);
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

