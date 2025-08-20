// Navigation par onglets
function showTab(tabName) {
    // Masquer tous les onglets
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabPanes.forEach(pane => pane.classList.remove('active'));
    
    // Désactiver tous les liens de navigation
    const navLinks = document.querySelectorAll('.nav-tabs a');
    navLinks.forEach(link => link.classList.remove('active'));
    
    // Afficher l'onglet sélectionné
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Activer le lien de navigation
    const selectedLink = document.querySelector(`[onclick="showTab('${tabName}')"]`);
    if (selectedLink) {
        selectedLink.classList.add('active');
    }
    
    // Charger les données spécifiques à l'onglet
    if (tabName === 'athletes' && document.getElementById('athletes-grid')) {
        loadAthletes();
    }
}

// Fonction pour ajouter une tâche
async function addTodo() {
    const input = document.getElementById('new-todo');
    const task = input.value.trim();
    
    if (!task) {
        alert('Veuillez saisir une tâche');
        return;
    }
    
    try {
        const response = await fetch('ajax/add_todo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `task=${encodeURIComponent(task)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            loadTodos();
        } else {
            alert('Erreur lors de l\'ajout de la tâche');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    }
}

// Fonction pour charger les tâches
async function loadTodos() {
    try {
        const response = await fetch('ajax/get_todos.php');
        const todos = await response.json();
        
        const container = document.getElementById('todos-list');
        container.innerHTML = '';
        
        todos.forEach(todo => {
            const todoItem = document.createElement('div');
            todoItem.className = `todo-item ${todo.is_completed ? 'completed' : ''}`;
            todoItem.innerHTML = `
                <input type="checkbox" class="todo-checkbox" 
                       ${todo.is_completed ? 'checked' : ''} 
                       onchange="toggleTodo(${todo.id})">
                <p class="todo-text">${todo.task}</p>
                <button class="todo-delete" onclick="deleteTodo(${todo.id})">×</button>
            `;
            container.appendChild(todoItem);
        });
    } catch (error) {
        console.error('Erreur lors du chargement des tâches:', error);
    }
}

// Fonction pour basculer l'état d'une tâche
async function toggleTodo(id) {
    try {
        const response = await fetch('ajax/toggle_todo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        });
        
        const result = await response.json();
        if (!result.success) {
            alert('Erreur lors de la mise à jour');
            loadTodos(); // Recharger pour revenir à l'état précédent
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Fonction pour supprimer une tâche
async function deleteTodo(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
        return;
    }
    
    try {
        const response = await fetch('ajax/delete_todo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        });
        
        const result = await response.json();
        if (result.success) {
            loadTodos();
        } else {
            alert('Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Fonction pour ajouter un favori
async function addFavorite() {
    const title = document.getElementById('fav-title').value.trim();
    const url = document.getElementById('fav-url').value.trim();
    const type = document.getElementById('fav-type').value;
    
    if (!title || !url) {
        alert('Veuillez remplir le titre et l\'URL');
        return;
    }
    
    try {
        const response = await fetch('ajax/add_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `title=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}&type=${type}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('fav-title').value = '';
            document.getElementById('fav-url').value = '';
            loadFavorites();
        } else {
            alert('Erreur lors de l\'ajout du favori');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    }
}

// Fonction pour charger les favoris
async function loadFavorites() {
    try {
        const response = await fetch('ajax/get_favorites.php');
        const favorites = await response.json();
        
        const container = document.getElementById('favorites-grid');
        container.innerHTML = '';
        
        favorites.forEach(fav => {
            const favItem = document.createElement('div');
            favItem.className = 'favorite-item';
            favItem.innerHTML = `
                <div class="favorite-title">${fav.title}</div>
                <a href="${fav.url}" class="favorite-url" target="_blank">${fav.url.substring(0, 50)}${fav.url.length > 50 ? '...' : ''}</a>
                <div class="favorite-type">${fav.type}</div>
                <button class="favorite-delete" onclick="deleteFavorite(${fav.id})">×</button>
            `;
            container.appendChild(favItem);
        });
    } catch (error) {
        console.error('Erreur lors du chargement des favoris:', error);
    }
}

// Fonction pour supprimer un favori
async function deleteFavorite(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce favori ?')) {
        return;
    }
    
    try {
        const response = await fetch('ajax/delete_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        });
        
        const result = await response.json();
        if (result.success) {
            loadFavorites();
        } else {
            alert('Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Permettre d'ajouter une tâche avec Entrée
document.addEventListener('DOMContentLoaded', function() {
    const todoInput = document.getElementById('new-todo');
    if (todoInput) {
        todoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addTodo();
            }
        });
    }
    
    
    // Charger les données au démarrage
    if (document.getElementById('todos-list')) {
        loadTodos();
        loadFavorites();
    }
    
    // Charger les athlètes si on est sur l'onglet correspondant
    if (document.getElementById('athletes-grid')) {
        loadAthletes();
    }
});

// === GESTION DES ATHLÈTES ===

// Fonction pour charger la liste des athlètes
async function loadAthletes() {
    try {
        const response = await fetch('ajax/get_athletes.php');
        const athletes = await response.json();
        
        const container = document.getElementById('athletes-grid');
        
        if (athletes.length === 0) {
            container.innerHTML = `
                <div class="no-athletes">
                    <div class="no-athletes-icon">👥</div>
                    <h3>Aucun athlète</h3>
                    <p>Ajoutez des athlètes pour commencer le coaching</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = '';
        
        athletes.forEach(athlete => {
            const athleteCard = document.createElement('div');
            athleteCard.className = 'athlete-card';
            
            const initials = (athlete.first_name.charAt(0) + athlete.last_name.charAt(0)).toUpperCase();
            const programStatus = athlete.active_program ? 
                `<span class="program-status status-${athlete.active_program.status}">${athlete.active_program.status}</span>` : 
                '<span style="color: #999;">Aucun programme</span>';
            
            athleteCard.innerHTML = `
                <div class="athlete-header">
                    <div class="athlete-avatar">${initials}</div>
                    <div class="athlete-info">
                        <h4>${athlete.first_name} ${athlete.last_name}</h4>
                        <p>${athlete.email}</p>
                    </div>
                </div>
                
                <div class="athlete-stats">
                    <div class="stat-row">
                        <span class="stat-label">Programme actuel:</span>
                        <span class="stat-value">${programStatus}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Ajouté le:</span>
                        <span class="stat-value">${new Date(athlete.created_at).toLocaleDateString('fr-FR')}</span>
                    </div>
                </div>
                
                <div class="athlete-actions">
                    <button class="btn-small btn-primary" onclick="viewAthleteProfile(${athlete.id})">
                        Voir profil
                    </button>
                    <button class="btn-small btn-secondary" onclick="removeAthlete(${athlete.id})">
                        Retirer
                    </button>
                </div>
            `;
            
            container.appendChild(athleteCard);
        });
    } catch (error) {
        console.error('Erreur lors du chargement des athlètes:', error);
    }
}

// Fonction pour rechercher un athlète
async function searchAthlete() {
    const email = document.getElementById('athlete-search').value.trim();
    const resultsContainer = document.getElementById('search-results');
    
    if (!email) {
        alert('Veuillez saisir un email');
        return;
    }
    
    try {
        const response = await fetch('ajax/search_athlete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}`
        });
        
        const result = await response.json();
        
        if (result.found && !result.already_added) {
            resultsContainer.innerHTML = `
                <div class="search-result-item" onclick="addAthlete(${result.athlete.id})">
                    <strong>${result.athlete.first_name} ${result.athlete.last_name}</strong><br>
                    <small>${result.athlete.email}</small>
                    <div style="float: right; color: #667eea;">Cliquer pour ajouter</div>
                </div>
            `;
            resultsContainer.style.display = 'block';
        } else if (result.already_added) {
            resultsContainer.innerHTML = `
                <div class="search-result-item" style="color: #856404;">
                    Cet athlète est déjà dans votre liste
                </div>
            `;
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.innerHTML = `
                <div class="search-result-item" style="color: #721c24;">
                    Aucun athlète trouvé avec cet email
                </div>
            `;
            resultsContainer.style.display = 'block';
        }
    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
        alert('Erreur de connexion');
    }
}

// Fonction pour ajouter un athlète
async function addAthlete(athleteId) {
    try {
        const response = await fetch('ajax/add_athlete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `athlete_id=${athleteId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('athlete-search').value = '';
            document.getElementById('search-results').style.display = 'none';
            loadAthletes();
        } else {
            alert('Erreur lors de l\'ajout de l\'athlète');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    }
}

// Fonction pour retirer un athlète
async function removeAthlete(athleteId) {
    if (!confirm('Êtes-vous sûr de vouloir retirer cet athlète de votre liste ?')) {
        return;
    }
    
    try {
        const response = await fetch('ajax/remove_athlete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `athlete_id=${athleteId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadAthletes();
        } else {
            alert('Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    }
}

// Fonction pour voir le profil d'un athlète
async function viewAthleteProfile(athleteId) {
    try {
        const response = await fetch(`ajax/get_athlete_profile.php?id=${athleteId}`);
        const athlete = await response.json();
        
        if (athlete.error) {
            alert('Erreur lors du chargement du profil');
            return;
        }
        
        document.getElementById('modal-athlete-name').textContent = 
            `${athlete.first_name} ${athlete.last_name}`;
        
        const modalBody = document.getElementById('modal-body');
        modalBody.innerHTML = `
            <div style="margin-bottom: 2rem;">
                <h4>Informations personnelles</h4>
                <p><strong>Email:</strong> ${athlete.email}</p>
                <p><strong>Membre depuis:</strong> ${new Date(athlete.created_at).toLocaleDateString('fr-FR')}</p>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h4>Programme actuel</h4>
                ${athlete.active_program ? `
                    <div class="dashboard-card">
                        <h5>${athlete.active_program.title}</h5>
                        <p>${athlete.active_program.description || 'Aucune description'}</p>
                        <div class="stat-row">
                            <span class="stat-label">Durée:</span>
                            <span class="stat-value">${athlete.active_program.duration_weeks} semaines</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Séances/semaine:</span>
                            <span class="stat-value">${athlete.active_program.sessions_per_week}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Type:</span>
                            <span class="stat-value">${athlete.active_program.program_type}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Statut:</span>
                            <span class="program-status status-${athlete.active_program.status}">
                                ${athlete.active_program.status}
                            </span>
                        </div>
                    </div>
                    <button class="btn" onclick="editProgram(${athlete.active_program.id})">
                        Modifier le programme
                    </button>
                ` : `
                    <p style="color: #666; font-style: italic;">Aucun programme assigné</p>
                    <button class="btn" onclick="createProgram(${athlete.id})">
                        Créer un programme
                    </button>
                `}
            </div>
        `;
        
        document.getElementById('athlete-modal').style.display = 'block';
    } catch (error) {
        console.error('Erreur lors du chargement du profil:', error);
        alert('Erreur de connexion');
    }
}

// Fonction pour fermer le modal
function closeAthleteModal() {
    document.getElementById('athlete-modal').style.display = 'none';
}

// Fonction pour créer un programme (placeholder)
function createProgram(athleteId) {
    alert('Fonctionnalité de création de programme en cours de développement');
    closeAthleteModal();
}

// Fonction pour modifier un programme (placeholder)
function editProgram(programId) {
    alert('Fonctionnalité de modification de programme en cours de développement');
    closeAthleteModal();
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('athlete-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Fonction pour créer un programme - redirige vers la page de création
function createProgram(athleteId) {
    window.location.href = `create_program.php?athlete_id=${athleteId}`;
}

// Fonction pour modifier un programme (placeholder pour l'instant)
function editProgram(programId) {
    window.location.href = `program_planning.php?program_id=${programId}`;
}

// Remplacer la fonction createSessions() dans program_planning.php

async function createSessions() {
    const count = parseInt(document.getElementById('sessions-count').value);
    const newSessions = [];

    // Collecter les données des séances
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
                    sets: parseInt(sets),
                    reps: reps,
                    difficulty: parseInt(difficulty),
                    notes: ''
                });
            }
        }

        if (exercises.length > 0) {
            newSessions.push({
                name: sessionName,
                notes: sessionNotes,
                exercises: exercises
            });
        }
    }

    if (newSessions.length === 0) {
        alert('Veuillez ajouter au moins un exercice à chaque séance');
        return;
    }

    try {
        // Désactiver le bouton pendant la création
        const createBtn = document.querySelector('.btn-primary');
        const originalText = createBtn.textContent;
        createBtn.disabled = true;
        createBtn.textContent = '⏳ Création...';

        const response = await fetch('ajax/create_sessions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                program_id: programData.id,
                sessions: newSessions
            })
        });

        const result = await response.json();

        if (result.success) {
            // Ajouter les séances créées à la bibliothèque
            result.sessions.forEach(session => {
                sessionsLibrary.push(session);
            });

            updateLibraryDisplay();
            closeSessionModal();
            alert(`${result.sessions.length} séance(s) créée(s) avec succès !`);
        } else {
            alert('Erreur lors de la création des séances: ' + (result.error || 'Erreur inconnue'));
        }

        // Réactiver le bouton
        createBtn.disabled = false;
        createBtn.textContent = originalText;

    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
        
        // Réactiver le bouton en cas d'erreur
        const createBtn = document.querySelector('.btn-primary');
        createBtn.disabled = false;
        createBtn.textContent = 'Créer les séances';
    }
}

// Remplacer la fonction loadSessions()
async function loadSessions() {
    try {
        const response = await fetch(`ajax/get_sessions.php?program_id=${programData.id}`);
        const result = await response.json();

        if (result.success) {
            // Charger les séances de la bibliothèque
            sessionsLibrary = result.library_sessions || [];
            
            // Placer les séances déjà positionnées dans le planning
            if (result.placed_sessions) {
                result.placed_sessions.forEach(session => {
                    const daySlot = document.querySelector(`[data-week="${session.week}"][data-day="${session.day}"]`);
                    if (daySlot) {
                        const sessionCard = createSessionCard(session);
                        daySlot.appendChild(sessionCard);
                    }
                });
            }

            updateLibraryDisplay();
        } else {
            console.error('Erreur lors du chargement des séances:', result.error);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des séances:', error);
    }
}

// Nouvelle fonction pour créer une carte de séance
function createSessionCard(sessionData) {
    const sessionCard = document.createElement('div');
    sessionCard.className = 'session-card';
    sessionCard.innerHTML = `
        <div class="session-name">${sessionData.name}</div>
        <div class="session-details">${sessionData.exerciseCount} exercice(s)</div>
    `;
    
    // Ajouter les événements drag and drop
    sessionCard.draggable = true;
    sessionCard.ondragstart = (e) => dragStart(e, sessionData);
    sessionCard.addEventListener('dragend', function() {
        this.classList.remove('dragging');
    });
    
    return sessionCard;
}

// Mettre à jour la fonction drop pour sauvegarder en BDD
async function drop(event) {
    event.preventDefault();
    event.target.classList.remove('drop-zone');
    
    const sessionData = JSON.parse(event.dataTransfer.getData("text/plain"));
    const week = parseInt(event.target.dataset.week);
    const day = parseInt(event.target.dataset.day);
    
    // Créer la carte de séance dans le planning
    const sessionCard = createSessionCard(sessionData);
    event.target.appendChild(sessionCard);
    
    // Sauvegarder la position en base de données
    try {
        const response = await fetch('ajax/place_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionData.id,
                week: week,
                day: day
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            console.error('Erreur lors de la sauvegarde:', result.error);
            // Optionnel: supprimer la carte en cas d'erreur
            sessionCard.remove();
            alert('Erreur lors de la sauvegarde de la position');
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        alert('Erreur de connexion lors de la sauvegarde');
    }
}

// Charger les exercices disponibles dynamiquement
async function loadExercises() {
    try {
        const response = await fetch('ajax/get_exercises.php');
        const result = await response.json();
        
        if (result.success) {
            return result.grouped;
        }
        return {};
    } catch (error) {
        console.error('Erreur lors du chargement des exercices:', error);
        return {};
    }
}

// Mettre à jour la fonction addExercise pour utiliser les vrais exercices
async function addExercise(sessionIndex) {
    const exercisesList = document.getElementById(`exercises-list-${sessionIndex}`);
    const exerciseIndex = exercisesList.children.length;
    
    // Charger les exercices si pas déjà fait
    if (!window.exercisesData) {
        window.exercisesData = await loadExercises();
    }
    
    // Créer les options d'exercices
    let exerciseOptions = '<option value="">Choisir un exercice...</option>';
    
    const categoryLabels = {
        'squat': 'Squat',
        'bench': 'Développé',
        'deadlift': 'Soulevé de Terre',
        'accessory': 'Accessoires'
    };
    
    Object.keys(window.exercisesData).forEach(category => {
        const categoryLabel = categoryLabels[category] || category;
        exerciseOptions += `<optgroup label="${categoryLabel}">`;
        
        window.exercisesData[category].forEach(exercise => {
            exerciseOptions += `<option value="${exercise.id}">${exercise.name}</option>`;
        });
        
        exerciseOptions += '</optgroup>';
    });
    
    const exerciseDiv = document.createElement('div');
    exerciseDiv.className = 'exercise-item';
    exerciseDiv.innerHTML = `
        <div class="exercise-details" style="flex: 1;">
            <div class="form-row" style="margin-bottom: 0.5rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <select id="exercise-${sessionIndex}-${exerciseIndex}" style="width: 100%;">
                        ${exerciseOptions}
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