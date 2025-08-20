<?php
session_start();

// Rediriger vers le bon dashboard si déjà connecté
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'coach') {
        header('Location: dashboard_coach.php');
    } else {
        header('Location: dashboard_athlete.php');
    }
    exit();
}

// Sinon rediriger vers la page de connexion
header('Location: login.php');
exit();
?>