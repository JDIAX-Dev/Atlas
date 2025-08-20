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
        $stmt = $pdo->prepare("
            INSERT INTO training_sessions (program_id, week_number, day_of_week, session_name, notes) 
            VALUES (?, 0, 0, ?, ?)
        ");
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
                $stmt = $pdo->prepare("
                    INSERT INTO session_exercises 
                    (session_id, exercise_id, exercise_order, sets_count, reps, difficulty, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $session_id,
                    (int)$exercise['exercise_id'],
                    $order++,
                    (int)$exercise['sets'],
                    $exercise['reps'],
                    (int)$exercise['difficulty'],
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
    
    // Récupérer toutes les séances du programme
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
        GROUP BY ts.id
        ORDER BY ts.week_number, ts.day_of_week, ts.session_name
    ");
    $stmt->execute([$program_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Séparer les séances placées et celles en library
    $library_sessions = [];
    $placed_sessions = [];
    
    foreach ($sessions as $session) {
        $session_data = [
            'id' => (int)$session['id'],
            'name' => $session['session_name'],
            'notes' => $session['notes'],
            'exerciseCount' => (int)$session['exercise_count'],
            'week' => (int)$session['week_number'],
            'day' => (int)$session['day_of_week']
        ];
        
        if ($session['week_number'] == 0 && $session['day_of_week'] == 0) {
            $library_sessions[] = $session_data;
        } else {
            $placed_sessions[] = $session_data;
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
    
    // Mettre à jour la position de la séance
    $stmt = $pdo->prepare("
        UPDATE training_sessions 
        SET week_number = ?, day_of_week = ? 
        WHERE id = ?
    ");
    $stmt->execute([$week, $day, $session_id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log('Erreur placement séance: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>

<?php
// ajax/get_exercises.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, category, description FROM exercises ORDER BY category, name");
    $stmt->execute();
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Grouper par catégorie
    $grouped = [];
    foreach ($exercises as $exercise) {
        $category = $exercise['category'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $exercise;
    }
    
    echo json_encode([
        'success' => true,
        'exercises' => $exercises,
        'grouped' => $grouped
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur récupération exercices: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>