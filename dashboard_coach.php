<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté et est un coach
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    header('Location: login.php');
    exit();
}

$user_name = $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'];

// Récupérer les athlètes récemment consultés (simulation pour l'instant)
$recent_athletes = [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atlas - Coach</title>
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
                <span>👨‍🏫 Coach</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
        
        <!-- Navigation par onglets -->
        <nav class="nav-tabs">
            <ul>
                <li><a href="#" onclick="showTab('dashboard')" class="active">📊 Tableau de bord</a></li>
                <li><a href="#" onclick="showTab('discussions')">💬 Discussions</a></li>
                <li><a href="#" onclick="showTab('resources')">📚 Ressources</a></li>
                <li><a href="#" onclick="showTab('athletes')">👥 Athlètes</a></li>
                <li><a href="#" onclick="showTab('profile')">⚙️ Profil</a></li>
            </ul>
        </nav>
        
        <!-- Contenu des onglets -->
        <div class="tab-content">
            <!-- Onglet Tableau de bord -->
            <div id="dashboard" class="tab-pane active">
                <h2>📊 Tableau de bord</h2>
                
                <div class="dashboard-grid">
                    <!-- Athlètes récents -->
                    <div class="dashboard-card">
                        <h3>👥 Athlètes consultés récemment</h3>
                        <div class="recent-athletes">
                            <div style="text-align: center; padding: 2rem; color: #666;">
                                <p>Aucun athlète consulté récemment</p>
                                <small>Les athlètes que vous consultez apparaîtront ici</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- To-do List -->
                    <div class="dashboard-card">
                        <h3>📝 Ma to-do list</h3>
                        <div id="todos-list"></div>
                        <div class="add-todo-form">
                            <input type="text" id="new-todo" class="add-todo-input" placeholder="Nouvelle tâche...">
                            <button onclick="addTodo()" class="add-todo-btn">Ajouter</button>
                        </div>
                    </div>
                </div>
                
                <!-- Liens favoris -->
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
                                    <option value="athlete">Athlète</option>
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
            
            <!-- Onglet Discussions -->
            <div id="discussions" class="tab-pane">
                <h2>💬 Discussions</h2>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <h3>🚧 En cours de développement</h3>
                    <p>Le système de discussions sera disponible prochainement</p>
                </div>
            </div>
            
            <!-- Onglet Ressources -->
            <div id="resources" class="tab-pane">
                <h2>📚 Ressources</h2>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <h3>🚧 En cours de développement</h3>
                    <p>La gestion des ressources sera disponible prochainement</p>
                </div>
            </div>
            
            <!-- Onglet Athlètes -->
            <div id="athletes" class="tab-pane">
                <h2>👥 Mes athlètes</h2>
                
                <!-- Section d'ajout d'athlète -->
                <div class="add-athlete-section">
                    <h3>➕ Ajouter un athlète</h3>
                    <p>Recherchez un athlète par son email pour l'ajouter à votre liste</p>
                    <div class="add-athlete-form">
                        <input type="email" id="athlete-search" class="search-input" placeholder="Email de l'athlète...">
                        <button onclick="searchAthlete()" class="btn">Rechercher</button>
                    </div>
                    <div id="search-results" class="search-results" style="display: none;"></div>
                </div>
                
                <!-- Liste des athlètes -->
                <div id="athletes-list">
                    <div class="athletes-grid" id="athletes-grid">
                        <!-- Les athlètes seront chargés ici via JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Onglet Profil -->
            <div id="profile" class="tab-pane">
                <h2>⚙️ Profil</h2>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <h3>🚧 En cours de développement</h3>
                    <p>Les paramètres du profil seront disponibles prochainement</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal pour le profil athlète -->
    <div id="athlete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-athlete-name">Profil Athlète</h3>
                <span class="close" onclick="closeAthleteModal()">&times;</span>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>