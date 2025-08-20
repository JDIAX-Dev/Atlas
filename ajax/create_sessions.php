<?php
// ajax/create_sessions.php
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

if (!$input || !isset($input['program_id']) || !isset($input['sessions'])) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

$program_id = (int)$input['program_id'];
$sessions = $input['sessions'];

try {
    // Vérifier que le programme appartient bien à ce coach
    $stmt = $pdo->prepare("SELECT id FROM training_programs WHERE id = ? AND coach_id = ?");
    $stmt->execute([$program_id, $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Programme non trouvé']);
        exit();
    }

    $pdo->beginTransaction();

    $created_sessions = [];

    foreach ($sessions as $session_data) {
        // Créer la séance (sans week/day pour l'instant - sera dans la library)
        $stmt = $pdo->prepare(
            "INSERT INTO training_sessions (program_id, week_number, day_of_week, session_name, notes)
             VALUES (?, 0, 0, ?, ?)"
        );
        $stmt->execute([
            $program_id,
            $session_data['name'],
            $session_data['notes'] ?: null
        ]);

        $session_id = $pdo->lastInsertId();

        // Ajouter les exercices de la séance
        if (isset($session_data['exercises']) && is_array($session_data['exercises'])) {
            $order = 1;
            foreach ($session_data['exercises'] as $exercise) {
                $stmt = $pdo->prepare(
                    "INSERT INTO session_exercises
                    (session_id, exercise_id, exercise_order, sets_count, reps, difficulty, weight_kg, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $session_id,
                    (int)$exercise['exercise_id'],
                    $order++,
                    (int)$exercise['sets'],
                    $exercise['reps'],
                    (int)$exercise['difficulty'],
                    isset($exercise['weight']) ? $exercise['weight'] : null,
                    $exercise['notes'] ?: null
                ]);
            }
        }

        $created_sessions[] = [
            'id' => $session_id,
            'name' => $session_data['name'],
            'notes' => $session_data['notes'],
            'exerciseCount' => count($session_data['exercises'])
        ];
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'sessions' => $created_sessions,
        'message' => count($created_sessions) . ' séance(s) créée(s)'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erreur création séances: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}

?>

