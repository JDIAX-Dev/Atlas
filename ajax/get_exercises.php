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

