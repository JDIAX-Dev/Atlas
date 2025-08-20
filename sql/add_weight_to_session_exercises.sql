-- Ajout de la colonne de poids pour les exercices de séance
ALTER TABLE session_exercises
    ADD COLUMN weight_kg DECIMAL(5,2) NULL;

