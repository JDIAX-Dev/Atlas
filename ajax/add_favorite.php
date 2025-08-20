<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$title = trim($_POST['title'] ?? '');
$url = trim($_POST['url'] ?? '');
$type = $_POST['type'] ?? 'other';

if (empty($title) || empty($url)) {
    echo json_encode(['success' => false, 'error' => 'Titre et URL requis']);
    exit();
}

// Valider l'URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'URL invalide']);
    exit();
}

// Valider le type
$allowed_types = ['athlete', 'resource', 'program', 'other'];
if (!in_array($type, $allowed_types)) {
    $type = 'other';
}

try {
    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, title, url, type) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$_SESSION['user_id'], $title, $url, $type]);
    
    echo json_encode(['success' => $result]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>