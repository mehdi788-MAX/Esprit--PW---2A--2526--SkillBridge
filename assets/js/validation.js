// assets/js/validation.js

// -------------------------------------------------------
// Valider le formulaire d'ajout ET de modification
// -------------------------------------------------------
function validerFormTest() {
    // Récupérer les valeurs des champs
    var title         = document.getElementById('title').value.trim();
    var category_id   = document.getElementById('category_id').value;
    var duration      = document.getElementById('duration').value.trim();
    var level         = document.getElementById('level').value;
    var average_score = document.getElementById('average_score').value.trim();

    // Vider les anciens messages d'erreur
    effacerErreurs();

    var valide = true;

    // --- Vérification du titre ---
    if (title === '') {
        afficherErreur('error_title', 'Le titre est obligatoire.');
        valide = false;
    } else if (title.length < 3) {
        afficherErreur('error_title', 'Le titre doit contenir au moins 3 caractères.');
        valide = false;
    } else if (title.length > 150) {
        afficherErreur('error_title', 'Le titre ne peut pas dépasser 150 caractères.');
        valide = false;
    }

    // --- Vérification de la catégorie ---
    if (category_id === '' || category_id === '0') {
        afficherErreur('error_category', 'Veuillez choisir une catégorie.');
        valide = false;
    }

    // --- Vérification de la durée ---
    if (duration === '') {
        afficherErreur('error_duration', 'La durée est obligatoire.');
        valide = false;
    } else if (isNaN(duration) || parseInt(duration) <= 0) {
        afficherErreur('error_duration', 'La durée doit être un nombre positif.');
        valide = false;
    } else if (parseInt(duration) > 300) {
        afficherErreur('error_duration', 'La durée ne peut pas dépasser 300 minutes.');
        valide = false;
    }

    // --- Vérification du niveau ---
    var niveauxValides = ['Débutant', 'Moyen', 'Avancé'];
    if (level === '' || niveauxValides.indexOf(level) === -1) {
        afficherErreur('error_level', 'Veuillez choisir un niveau valide.');
        valide = false;
    }

    // --- Vérification du score moyen ---
    if (average_score === '') {
        afficherErreur('error_score', 'Le score moyen est obligatoire.');
        valide = false;
    } else if (isNaN(average_score) || parseFloat(average_score) < 0 || parseFloat(average_score) > 100) {
        afficherErreur('error_score', 'Le score doit être un nombre entre 0 et 100.');
        valide = false;
    }

    return valide;
}

// -------------------------------------------------------
// Afficher un message d'erreur sous un champ
// -------------------------------------------------------
function afficherErreur(elementId, message) {
    var el = document.getElementById(elementId);
    if (el) {
        el.textContent = message;
        el.style.display = 'block';
    }
}

// -------------------------------------------------------
// Effacer tous les messages d'erreur
// -------------------------------------------------------
function effacerErreurs() {
    var erreurs = document.querySelectorAll('.erreur-msg');
    erreurs.forEach(function(el) {
        el.textContent = '';
        el.style.display = 'none';
    });
}

// -------------------------------------------------------
// Confirmer la suppression
// -------------------------------------------------------
function confirmerSuppression(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce test ?')) {
        window.location.href = 'index.php?action=delete&id=' + id;
    }
}
