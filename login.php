<?php
session_start();
require_once 'config/database.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'coach') {
        header('Location: dashboard_coach.php');
    } else {
        header('Location: dashboard_athlete.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, password, user_type FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_last_name'] = $user['last_name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirection selon le type d'utilisateur
            if ($user['user_type'] === 'coach') {
                header('Location: dashboard_coach.php');
            } else {
                header('Location: dashboard_athlete.php');
            }
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Atlas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Atlas</h1>
            <p>Coaching Force Athlétique</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Se connecter</button>
        </form>
        
        <div class="link">
            <a href="register.php">Pas encore de compte ? S'inscrire</a>
        </div>
    </div>
</body>
</html>