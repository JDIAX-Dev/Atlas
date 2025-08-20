<?php
// program_planning.php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté et est un coach
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID du programme
if (!isset($_GET['program_id'])) {
    header('Location: dashboard_coach.php');
    exit();
}

$program_id = (int)$_GET['program_id'];

// Récupérer les données du programme et vérifier qu'il appartient à ce coach
$stmt = $pdo->prepare("
    SELECT 
        tp.id,
        tp.title,
        tp.description,
        tp.duration_weeks,
        tp.sessions_per_week,
        tp.program_type,
        tp.squat_goal,
        tp.bench_goal,
        tp.deadlift_goal,
        tp.status,
        u.first_name,
        u.last_name
    FROM training_programs tp
    JOIN users u ON u.id = tp.athlete_id
    WHERE tp.id = ? AND tp.coach_id = ?
");
$stmt->execute([$program_id, $_SESSION['user_id']]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    header('Location: dashboard_coach.php');
    exit();
}

$athlete_name = $program['first_name'] . ' ' . $program['last_name'];
$total_sessions = $program['duration_weeks'] * $program['sessions_per_week'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planification - <?= htmlspecialchars($program['title']) ?> - Atlas</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .planning-container {
            margin: 0 2rem 2rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .planning-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .create-session-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .create-session-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .schedule-grid {
            display: flex;
            flex-direction: column;
        }

        .week-row {
            display: flex;
            border-bottom: 1px solid #e9ecef;
        }

        .week-row:last-child {
            border-bottom: none;
        }

        .week-label {
            min-width: 120px;
            background: #f8f9fa;
            padding: 1rem;
            border-right: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #666;
        }

        .week-days {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .day-slot {
            min-height: 100px;
            border-right: 1px solid #e9ecef;
            border-bottom: 1px solid rgba(233, 236, 239, 0.3);
            padding: 0.5rem;
            position: relative;
            transition: background-color 0.2s ease;
        }

        .day-slot:last-child {
            border-right: none;
        }

        .day-slot:hover {
            background: #f8f9fa;
        }

        .day-slot.drop-zone {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        .day-header {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .session-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem;
            border-radius: 6px;
            cursor: move;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .session-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .session-card.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        .session-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .session-details {
            font-size: 0.7rem;
            opacity: 0.9;
        }

        .sessions-library {
            position: fixed;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            width: 300px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-height: 60vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .library-header {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #e9ecef;
        }

        .library-content {
            padding: 1rem;
        }

        .library-session {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: move;
            transition: all 0.2s ease;
        }

        .library-session:hover {
            border-color: #667eea;
            background: #e3f2fd;
        }

        .library-session.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .program-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .info-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 2rem;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            opacity: 0.7;
        }

        .exercises-section {
            margin-top: 2rem;
        }

        .exercise-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .exercise-details {
            flex: 1;
        }

        .remove-exercise {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-exercise-btn {
            width: 100%;
            padding: 1rem;
            border: 2px dashed #dee2e6;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
        }

        .add-exercise-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .program-info {
                grid-template-columns: 1fr;
            }
            
            .sessions-library {
                position: relative;
                right: auto;
                top: auto;
                transform: none;
                width: 100%;
                margin: 2rem;
                margin-top: 0;
            }
            
            .week-days {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div>
                <h1>Atlas</h1>
                <p>Planification - <?= htmlspecialchars($program['title']) ?></p>
            </div>
            <div class="user-info">
                <a href="dashboard_coach.php" class="logout-btn">← Retour</a>
            </div>
        </div>

        <!-- En-tête du programme -->
        <div class="dashboard-card" style="margin: 2rem;">
            <h2><?= htmlspecialchars($program['title']) ?></h2>
            <p style="color: #666; margin-top: 0.5rem;">Pour <?= htmlspecialchars($athlete_name) ?></p>
            
            <div class="program-info">
                <div class="info-card">
                    <div class="info-value"><?= $program['duration_weeks'] ?></div>
                    <div class="info-label">Semaines</div>
                </div>
                <div class="info-card">
                    <div class="info-value"><?= $program['sessions_per_week'] ?></div>
                    <div class="info-label">Séances/semaine</div>
                </div>
                <div class="info-card">
                    <div class="info-value"><?= $total_sessions ?></div>
                    <div class="info-label">Total séances</div>
                </div>
            </div>
        </div>

        <!-- Interface de planification -->
        <div class="planning-container">
            <div class="planning-header">
                <h3>📅 Emploi du temps</h3>
                <button class="create-session-btn" onclick="openSessionModal()">
                    ➕ Créer des séances
                </button>
            </div>

            <div class="schedule-grid">
                <div class="week-row" style="border-bottom: 2px solid #dee2e6;">
                    <div class="week-label" style="background: #667eea; color: white;">Semaine</div>
                    <div class="week-days">
                        <div class="day-header">Lun</div>
                        <div class="day-header">Mar</div>
                        <div class="day-header">Mer</div>
                        <div class="day-header">Jeu</div>
                        <div class="day-header">Ven</div>
                        <div class="day-header">Sam</div>
                        <div class="day-header">Dim</div>
                    </div>
                </div>
                
                <div id="weeks-container">
                    <!-- Les semaines seront générées par JavaScript -->
                </div>
            </div>
        </div>

        <!-- Bibliothèque de séances -->
        <div class="sessions-library">
            <div class="library-header">
                <h4>📚 Séances créées</h4>
                <p style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">Glissez les séances dans le planning</p>
            </div>
            <div class="library-content" id="library-content">
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>Aucune séance créée</p>
                    <small>Créez des séances pour les organiser dans le planning</small>
                </div>
            </div>
        </div>

        <!-- Modal de création de séance -->
        <div id="session-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>➕ Créer des séances</h3>
                    <span class="close" onclick="closeSessionModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="sessions-count">Nombre de séances à créer</label>
                        <select id="sessions-count" onchange="updateSessionForms()">
                            <option value="1">1 séance</option>
                            <option value="2">2 séances</option>
                            <option value="3" selected>3 séances</option>
                            <option value="4">4 séances</option>
                            <option value="5">5 séances</option>
                            <option value="6">6 séances</option>
                            <option value="7">7 séances</option>
                        </select>
                    </div>

                    <div id="sessions-forms">
                        <!-- Les formulaires de séance seront générés ici -->
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button class="btn btn-secondary" onclick="closeSessionModal()">Annuler</button>
                        <button class="btn btn-primary" onclick="createSessions()">Créer les séances</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Données du programme récupérées depuis PHP
        let programData = {
            id: <?= $program_id ?>,
            duration: <?= $program['duration_weeks'] ?>,
            sessions_per_week: <?= $program['sessions_per_week'] ?>,
            title: <?= json_encode($program['title']) ?>,
            athlete_name: <?= json_encode($athlete_name) ?>
        };

        let sessionsLibrary = [];

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            generateWeeksGrid();
            loadSessions();
        });

        function generateWeeksGrid() {
            const container = document.getElementById('weeks-container');
            container.innerHTML = '';
            
            for (let week = 1; week <= programData.duration; week++) {
                const weekRow = document.createElement('div');
                weekRow.className = 'week-row';
                weekRow.innerHTML = `
                    <div class="week-label">Semaine ${week}</div>
                    <div class="week-days">
                        ${Array.from({length: 7}, (_, day) => `
                            <div class="day-slot" data-week="${week}" data-day="${day + 1}" 
                                 ondrop="drop(event)" ondragover="allowDrop(event)">
                            </div>
                        `).join('')}
                    </div>
                `;
                container.appendChild(weekRow);
            }
        }

        function openSessionModal() {
            document.getElementById('session-modal').style.display = 'block';
            updateSessionForms();
        }

        function closeSessionModal() {
            document.getElementById('session-modal').style.display = 'none';
        }

        function updateSessionForms() {
            const count = parseInt(document.getElementById('sessions-count').value);
            const container = document.getElementById('sessions-forms');
            container.innerHTML = '';

            for (let i = 1; i <= count; i++) {
                const sessionForm = document.createElement('div');
                sessionForm.style.marginBottom = '2rem';
                sessionForm.style.padding = '1.5rem';
                sessionForm.style.background = '#f8f9fa';
                sessionForm.style.borderRadius = '8px';
                sessionForm.innerHTML = `
                    <h4 style="margin-bottom: 1rem; color: #333;">Séance ${i}</h4>
                    
                    <div class="form-group">
                        <label for="session-name-${i}">Nom de la séance</label>
                        <input type="text" id="session-name-${i}" placeholder="Ex: Séance Haut du Corps" value="Séance ${i}">
                    </div>

                    <div class="exercises-section">
                        <h5 style="margin-bottom: 1rem;">Exercices</h5>
                        <div id="exercises-list-${i}">
                            <!-- Les exercices seront ajoutés ici -->
                        </div>
                        <button type="button" class="add-exercise-btn" onclick="addExercise(${i})">
                            ➕ Ajouter un exercice
                        </button>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="session-notes-${i}">Notes pour la séance</label>
                        <textarea id="session-notes-${i}" placeholder="Instructions particulières, conseils..." rows="3"></textarea>
                    </div>
                `;
                container.appendChild(sessionForm);

                // Ajouter un exercice par défaut
                addExercise(i);
            }
        }

        function addExercise(sessionIndex) {
            const exercisesList = document.getElementById(`exercises-list-${sessionIndex}`);
            const exerciseIndex = exercisesList.children.length;
            
            const exerciseDiv = document.createElement('div');
            exerciseDiv.className = 'exercise-item';
            exerciseDiv.innerHTML = `
                <div class="exercise-details" style="flex: 1;">
                    <div class="form-row" style="margin-bottom: 0.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <select id="exercise-${sessionIndex}-${exerciseIndex}" style="width: 100%;">
                                <option value="">Choisir un exercice...</option>
                                <optgroup label="Squat">
                                    <option value="1">Squat</option>
                                    <option value="2">Squat Front</option>
                                    <option value="3">Squat Box</option>
                                </optgroup>
                                <optgroup label="Développé">
                                    <option value="4">Développé Couché</option>
                                    <option value="5">Développé Incliné</option>
                                    <option value="6">Développé Haltères</option>
                                </optgroup>
                                <optgroup label="Soulevé de Terre">
                                    <option value="7">Soulevé de Terre</option>
                                    <option value="8">Soulevé Sumo</option>
                                    <option value="9">Soulevé Roumain</option>
                                </optgroup>
                                <optgroup label="Accessoires">
                                    <option value="10">Rowing Barre</option>
                                    <option value="11">Tractions</option>
                                    <option value="12">Dips</option>
                                    <option value="13">Extensions Triceps</option>
                                    <option value="14">Curls Biceps</option>
                                    <option value="15">Élévations Latérales</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <input type="number" id="sets-${sessionIndex}-${exerciseIndex}" placeholder="Séries" min="1" max="10" value="3">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <input type="text" id="reps-${sessionIndex}-${exerciseIndex}" placeholder="Répétitions (ex: 8-10)" value="8-10">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <select id="difficulty-${sessionIndex}-${exerciseIndex}">
                                <option value="3">Facile (3/10)</option>
                                <option value="5" selected>Modéré (5/10)</option>
                                <option value="7">Difficile (7/10)</option>
                                <option value="9">Très difficile (9/10)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button class="remove-exercise" onclick="this.parentElement.remove()">×</button>
            `;
            
            exercisesList.appendChild(exerciseDiv);
        }

        function createSessions() {
            const count = parseInt(document.getElementById('sessions-count').value);
            const newSessions = [];

            for (let i = 1; i <= count; i++) {
                const sessionName = document.getElementById(`session-name-${i}`).value || `Séance ${i}`;
                const sessionNotes = document.getElementById(`session-notes-${i}`).value;
                const exercisesList = document.getElementById(`exercises-list-${i}`);
                
                const exercises = [];
                for (let j = 0; j < exercisesList.children.length; j++) {
                    const exerciseSelect = document.getElementById(`exercise-${i}-${j}`);
                    const sets = document.getElementById(`sets-${i}-${j}`).value;
                    const reps = document.getElementById(`reps-${i}-${j}`).value;
                    const difficulty = document.getElementById(`difficulty-${i}-${j}`).value;
                    
                    if (exerciseSelect && exerciseSelect.value) {
                        exercises.push({
                            exercise_id: exerciseSelect.value,
                            exercise_name: exerciseSelect.options[exerciseSelect.selectedIndex].text,
                            sets: sets,
                            reps: reps,
                            difficulty: difficulty
                        });
                    }
                }

                if (exercises.length > 0) {
                    const session = {
                        id: Date.now() + i, // ID temporaire
                        name: sessionName,
                        notes: sessionNotes,
                        exercises: exercises,
                        exerciseCount: exercises.length
                    };
                    
                    newSessions.push(session);
                    sessionsLibrary.push(session);
                }
            }

            updateLibraryDisplay();
            closeSessionModal();
            
            if (newSessions.length > 0) {
                alert(`${newSessions.length} séance(s) créée(s) avec succès !`);
            }
        }

        function updateLibraryDisplay() {
            const container = document.getElementById('library-content');
            
            if (sessionsLibrary.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <p>Aucune séance créée</p>
                        <small>Créez des séances pour les organiser dans le planning</small>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            sessionsLibrary.forEach(session => {
                const sessionDiv = document.createElement('div');
                sessionDiv.className = 'library-session';
                sessionDiv.draggable = true;
                sessionDiv.ondragstart = (e) => dragStart(e, session);
                sessionDiv.innerHTML = `
                    <div class="session-name">${session.name}</div>
                    <div class="session-details">${session.exerciseCount} exercice(s)</div>
                    ${session.notes ? `<div style="font-size: 0.7rem; margin-top: 0.25rem; opacity: 0.8;">${session.notes}</div>` : ''}
                `;
                container.appendChild(sessionDiv);
            });
        }

        function loadSessions() {
            // TODO: Charger les séances existantes depuis la base de données
            updateLibraryDisplay();
        }

        // Gestion du drag and drop
        function dragStart(event, session) {
            event.dataTransfer.setData("text/plain", JSON.stringify(session));
            event.target.classList.add('dragging');
        }

        function allowDrop(event) {
            event.preventDefault();
            event.target.classList.add('drop-zone');
        }

        function drop(event) {
            event.preventDefault();
            event.target.classList.remove('drop-zone');
            
            const sessionData = JSON.parse(event.dataTransfer.getData("text/plain"));
            const week = event.target.dataset.week;
            const day = event.target.dataset.day;
            
            // Créer la carte de séance dans le planning
            const sessionCard = document.createElement('div');
            sessionCard.className = 'session-card';
            sessionCard.innerHTML = `
                <div class="session-name">${sessionData.name}</div>
                <div class="session-details">${sessionData.exerciseCount} exercices</div>
            `;
            
            // Ajouter les événements pour pouvoir redéplacer
            sessionCard.draggable = true;
            sessionCard.ondragstart = (e) => dragStart(e, sessionData);
            sessionCard.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });
            
            // Ajouter au slot
            event.target.appendChild(sessionCard);
            
            // TODO: Sauvegarder en base de données
            console.log(`Séance "${sessionData.name}" placée en semaine ${week}, jour ${day}`);
        }

        // Nettoyer les classes CSS après drag
        document.addEventListener('dragend', function() {
            document.querySelectorAll('.dragging').forEach(el => {
                el.classList.remove('dragging');
            });
            document.querySelectorAll('.drop-zone').forEach(el => {
                el.classList.remove('drop-zone');
            });
        });

        // Fermer modal en cliquant à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('session-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>