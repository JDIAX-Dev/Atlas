<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté et est un athlète
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'athlete') {
    header('Location: login.php');
    exit();
}

$user_name = $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atlas - Athlète</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div>
                <h1>Atlas</h1>
                <p>Bienvenue, <?= htmlspecialchars($user_name) ?></p>
            </div>
            <div class="user-info">
                <span>💪 Athlète</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
        
        <!-- Navigation par onglets (version athlète) -->
        <nav class="nav-tabs">
            <ul>
                <li><a href="#" onclick="showTab('dashboard')" class="active">📊 Tableau de bord</a></li>
                <li><a href="#" onclick="showTab('discussions')">💬 Discussion</a></li>
                <li><a href="#" onclick="showTab('resources')">📚 Ressources</a></li>
                <li><a href="#" onclick="showTab('profile')">⚙️ Profil</a></li>
            </ul>
        </nav>
        
        <!-- Contenu des onglets -->
        <div class="tab-content">
            <!-- Onglet Tableau de bord -->
            <div id="dashboard" class="tab-pane active">
                <h2>📊 Mon tableau de bord</h2>
                
                <div class="dashboard-grid">
                    <!-- Programme actuel -->
                    <div class="dashboard-card">
                        <h3>🏋️ Mon programme actuel</h3>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <p>Aucun programme assigné</p>
                            <small>Votre coach vous assignera un programme prochainement</small>
                        </div>
                    </div>
                    
                    <!-- Prochaine séance -->
                    <div class="dashboard-card">
                        <h3>⏰ Prochaine séance</h3>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <p>Aucune séance planifiée</p>
                            <small>Les séances apparaîtront une fois votre programme créé</small>
                        </div>
                    </div>
                </div>
                
                <!-- Mes favoris -->
                <div class="dashboard-card">
                    <h3>⭐ Mes liens favoris</h3>
                    
                    <!-- Formulaire d'ajout -->
                    <div class="add-favorite-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fav-title">Titre</label>
                                <input type="text" id="fav-title" placeholder="Nom du favori">
                            </div>
                            <div class="form-group">
                                <label for="fav-type">Type</label>
                                <select id="fav-type">
                                    <option value="resource">Ressource</option>
                                    <option value="program">Programme</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="fav-url">URL</label>
                            <input type="url" id="fav-url" placeholder="https://...">
                        </div>
                        <button onclick="addFavorite()" class="btn">Ajouter aux favoris</button>
                    </div>
                    
                    <!-- Liste des favoris -->
                    <div id="favorites-grid" class="favorites-grid"></div>
                </div>
            </div>
            
            <!-- Onglet Discussion -->
            <div id="discussions" class="tab-pane">
                <h2>💬 Discussion avec mon coach</h2>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <h3>🚧 En cours de développement</h3>
                    <p>Le système de discussion sera disponible prochainement</p>
                </div>
            </div>
            
            <!-- Onglet Ressources -->
            <div id="resources" class="tab-pane">
                <h2>📚 Ressources de mon coach</h2>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <h3>🚧 En cours de développement</h3>
                    <p>Les ressources partagées par votre coach apparaîtront ici</p>
                </div>
            </div>
            
            <!-- Onglet Profil -->
            <div id="profile" class="tab-pane">
                <h2>⚙️ Mon profil</h2>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <h3>🚧 En cours de développement</h3>
                    <p>Les paramètres du profil seront disponibles prochainement</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>