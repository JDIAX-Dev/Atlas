<?php
// create_program.php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté et est un coach
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coach') {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de l'athlète
if (!isset($_GET['athlete_id'])) {
    header('Location: dashboard_coach.php');
    exit();
}

$athlete_id = (int)$_GET['athlete_id'];

// Vérifier que l'athlète appartient bien à ce coach
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.email
    FROM users u
    JOIN coach_athlete_relations car ON car.athlete_id = u.id
    WHERE u.id = ? AND car.coach_id = ?
");
$stmt->execute([$athlete_id, $_SESSION['user_id']]);
$athlete = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$athlete) {
    header('Location: dashboard_coach.php');
    exit();
}

$athlete_name = $athlete['first_name'] . ' ' . $athlete['last_name'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Programme - Atlas</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .program-wizard {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .wizard-progress {
            background: rgba(255, 255, 255, 0.2);
            height: 4px;
            border-radius: 2px;
            margin-top: 1rem;
            overflow: hidden;
        }

        .progress-bar {
            background: white;
            height: 100%;
            width: 33%;
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .wizard-content {
            padding: 2rem;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-title {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .goals-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .goal-input {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .goal-input label {
            min-width: 120px;
            margin-bottom: 0;
        }

        .goal-input input {
            flex: 1;
        }

        .goal-input .unit {
            color: #666;
            font-weight: 500;
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .summary-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            font-weight: 500;
            color: #666;
        }

        .summary-value {
            color: #333;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div>
                <h1>Atlas</h1>
                <p>Création de programme pour <?= htmlspecialchars($athlete_name) ?></p>
            </div>
            <div class="user-info">
                <a href="dashboard_coach.php" class="logout-btn">← Retour</a>
            </div>
        </div>

        <div class="program-wizard">
            <div class="wizard-header">
                <h1>🏋️ Créer un Programme d'Entraînement</h1>
                <p>Pour <?= htmlspecialchars($athlete_name) ?></p>
                <div class="wizard-progress">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
            </div>

            <div class="wizard-content">
                <!-- Étape 1: Informations générales -->
                <div class="step active" data-step="1">
                    <h2 class="step-title">📋 Informations générales</h2>
                    
                    <div class="form-group">
                        <label for="program-title">Nom du programme *</label>
                        <input type="text" id="program-title" placeholder="Ex: Programme Force Débutant" required>
                    </div>

                    <div class="form-group">
                        <label for="program-description">Description</label>
                        <textarea id="program-description" placeholder="Décrivez les objectifs et le contenu du programme..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration-weeks">Durée (semaines) *</label>
                            <select id="duration-weeks" required>
                                <option value="">Choisir...</option>
                                <option value="4">4 semaines</option>
                                <option value="6">6 semaines</option>
                                <option value="8">8 semaines</option>
                                <option value="12">12 semaines</option>
                                <option value="16">16 semaines</option>
                                <option value="20">20 semaines</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="sessions-per-week">Séances par semaine *</label>
                            <select id="sessions-per-week" required>
                                <option value="">Choisir...</option>
                                <option value="2">2 séances</option>
                                <option value="3">3 séances</option>
                                <option value="4">4 séances</option>
                                <option value="5">5 séances</option>
                                <option value="6">6 séances</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Étape 2: Type et objectifs -->
                <div class="step" data-step="2">
                    <h2 class="step-title">🎯 Type et objectifs</h2>
                    
                    <div class="form-group">
                        <label for="program-type">Type de programme *</label>
                        <select id="program-type" required>
                            <option value="">Choisir...</option>
                            <option value="force">Force</option>
                            <option value="volume">Volume/Hypertrophie</option>
                            <option value="technique">Technique</option>
                            <option value="prep_competition">Préparation Compétition</option>
                            <option value="rehab">Réhabilitation</option>
                            <option value="decouverte">Découverte</option>
                            <option value="initiation">Initiation</option>
                        </select>
                    </div>

                    <div class="goals-section">
                        <h4>Objectifs de performances</h4>
                        <p style="color: #666; margin-bottom: 1rem;">Définissez les objectifs de charges pour les mouvements principaux (optionnel)</p>
                        
                        <div class="goal-input">
                            <label for="squat-goal">Squat:</label>
                            <input type="number" id="squat-goal" step="0.5" placeholder="120">
                            <span class="unit">kg</span>
                        </div>

                        <div class="goal-input">
                            <label for="bench-goal">Développé Couché:</label>
                            <input type="number" id="bench-goal" step="0.5" placeholder="80">
                            <span class="unit">kg</span>
                        </div>

                        <div class="goal-input">
                            <label for="deadlift-goal">Soulevé de Terre:</label>
                            <input type="number" id="deadlift-goal" step="0.5" placeholder="150">
                            <span class="unit">kg</span>
                        </div>
                    </div>
                </div>

                <!-- Étape 3: Résumé -->
                <div class="step" data-step="3">
                    <h2 class="step-title">📊 Résumé du programme</h2>
                    
                    <div class="summary-card">
                        <div class="summary-row">
                            <span class="summary-label">Nom du programme:</span>
                            <span class="summary-value" id="summary-title">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Durée:</span>
                            <span class="summary-value" id="summary-duration">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Séances par semaine:</span>
                            <span class="summary-value" id="summary-sessions">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total de séances:</span>
                            <span class="summary-value" id="summary-total">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Type:</span>
                            <span class="summary-value" id="summary-type">-</span>
                        </div>
                    </div>

                    <div class="summary-card" id="goals-summary" style="display: none;">
                        <h4 style="margin-bottom: 1rem;">🎯 Objectifs de performances</h4>
                        <div id="goals-list"></div>
                    </div>

                    <div class="alert alert-success">
                        <strong>✅ Prêt à créer !</strong> Une fois le programme créé, vous pourrez planifier les séances semaine par semaine.
                    </div>
                </div>
            </div>

            <div class="wizard-actions">
                <button class="btn btn-secondary" id="prev-btn" onclick="previousStep()" style="display: none;">
                    ← Précédent
                </button>
                <div></div>
                <button class="btn btn-primary" id="next-btn" onclick="nextStep()">
                    Suivant →
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;
        let programData = {};
        const athleteId = <?= $athlete_id ?>;

        function updateProgress() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
        }

        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
            
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            
            prevBtn.style.display = step > 1 ? 'block' : 'none';
            
            if (step === totalSteps) {
                nextBtn.textContent = '🚀 Créer le programme';
            } else {
                nextBtn.textContent = 'Suivant →';
            }
            
            updateProgress();
        }

        function validateStep(step) {
            switch(step) {
                case 1:
                    const title = document.getElementById('program-title').value.trim();
                    const duration = document.getElementById('duration-weeks').value;
                    const sessions = document.getElementById('sessions-per-week').value;
                    
                    if (!title || !duration || !sessions) {
                        alert('Veuillez remplir tous les champs obligatoires');
                        return false;
                    }
                    
                    programData.title = title;
                    programData.description = document.getElementById('program-description').value.trim();
                    programData.duration = parseInt(duration);
                    programData.sessions = parseInt(sessions);
                    return true;
                    
                case 2:
                    const type = document.getElementById('program-type').value;
                    
                    if (!type) {
                        alert('Veuillez sélectionner un type de programme');
                        return false;
                    }
                    
                    programData.type = type;
                    programData.squatGoal = document.getElementById('squat-goal').value || null;
                    programData.benchGoal = document.getElementById('bench-goal').value || null;
                    programData.deadliftGoal = document.getElementById('deadlift-goal').value || null;
                    
                    updateSummary();
                    return true;
                    
                case 3:
                    return createProgram();
            }
            return true;
        }

        function updateSummary() {
            document.getElementById('summary-title').textContent = programData.title;
            document.getElementById('summary-duration').textContent = programData.duration + ' semaines';
            document.getElementById('summary-sessions').textContent = programData.sessions + ' par semaine';
            document.getElementById('summary-total').textContent = (programData.duration * programData.sessions) + ' séances';
            
            const types = {
                'force': 'Force',
                'volume': 'Volume/Hypertrophie',
                'technique': 'Technique',
                'prep_competition': 'Préparation Compétition',
                'rehab': 'Réhabilitation',
                'decouverte': 'Découverte',
                'initiation': 'Initiation'
            };
            document.getElementById('summary-type').textContent = types[programData.type];
            
            const hasGoals = programData.squatGoal || programData.benchGoal || programData.deadliftGoal;
            const goalsSection = document.getElementById('goals-summary');
            
            if (hasGoals) {
                let goalsList = '';
                if (programData.squatGoal) goalsList += `<div class="summary-row"><span class="summary-label">Squat:</span><span class="summary-value">${programData.squatGoal} kg</span></div>`;
                if (programData.benchGoal) goalsList += `<div class="summary-row"><span class="summary-label">Développé Couché:</span><span class="summary-value">${programData.benchGoal} kg</span></div>`;
                if (programData.deadliftGoal) goalsList += `<div class="summary-row"><span class="summary-label">Soulevé de Terre:</span><span class="summary-value">${programData.deadliftGoal} kg</span></div>`;
                
                document.getElementById('goals-list').innerHTML = goalsList;
                goalsSection.style.display = 'block';
            } else {
                goalsSection.style.display = 'none';
            }
        }

        function nextStep() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        }

        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

        async function createProgram() {
            try {
                const nextBtn = document.getElementById('next-btn');
                nextBtn.disabled = true;
                nextBtn.textContent = '⏳ Création...';
                
                const response = await fetch('ajax/create_program.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        athlete_id: athleteId,
                        title: programData.title,
                        description: programData.description,
                        duration_weeks: programData.duration,
                        sessions_per_week: programData.sessions,
                        program_type: programData.type,
                        squat_goal: programData.squatGoal,
                        bench_goal: programData.benchGoal,
                        deadlift_goal: programData.deadliftGoal
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Programme créé avec succès !');
                    window.location.href = `program_planning.php?program_id=${result.program_id}`;
                    return true;
                } else {
                    alert('Erreur lors de la création du programme: ' + result.error);
                    nextBtn.disabled = false;
                    nextBtn.textContent = '🚀 Créer le programme';
                    return false;
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
                const nextBtn = document.getElementById('next-btn');
                nextBtn.disabled = false;
                nextBtn.textContent = '🚀 Créer le programme';
                return false;
            }
        }

        showStep(1);
    </script>
</body>
</html>