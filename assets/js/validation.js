// assets/js/validation.js

// -------------------------------------------------------
// Valider le formulaire test (ajout ET modification)
// -------------------------------------------------------
function validerFormTest() {
    var title         = document.getElementById('title').value.trim();
    var category_id   = document.getElementById('category_id').value;
    var duration      = document.getElementById('duration').value.trim();
    var level         = document.getElementById('level').value;
    var average_score = document.getElementById('average_score').value.trim();

    effacerErreurs();
    var valide = true;

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


    if (category_id === '' || category_id === '0') {
        afficherErreur('error_category', 'Veuillez choisir une catégorie.');
        valide = false;
    }


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


    var niveauxValides = ['Débutant', 'Moyen', 'Avancé'];
    if (level === '' || niveauxValides.indexOf(level) === -1) {
        afficherErreur('error_level', 'Veuillez choisir un niveau valide.');
        valide = false;
    }


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
// Valider le formulaire catégorie (create OU edit)
// -------------------------------------------------------
function validerFormCat(mode) {
    var inputId  = (mode === 'create') ? 'cat_name_create' : 'cat_name_edit';
    var errorId  = (mode === 'create') ? 'error_cat_name_create' : 'error_cat_name_edit';
    var name     = document.getElementById(inputId).value.trim();

    effacerErreurs();
    var valide = true;

    if (name === '') {
        afficherErreur(errorId, 'Le nom de la catégorie est obligatoire.');
        valide = false;
    } else if (name.length < 2) {
        afficherErreur(errorId, 'Le nom doit contenir au moins 2 caractères.');
        valide = false;
    } else if (name.length > 100) {
        afficherErreur(errorId, 'Le nom ne peut pas dépasser 100 caractères.');
        valide = false;
    }

    return valide;
}

// -------------------------------------------------------
// Gestion des modals catégorie
// -------------------------------------------------------
function ouvrirModalCreerCat() {
    document.getElementById('cat_name_create').value = '';
    effacerErreurs();
    document.getElementById('modalCreerCat').classList.add('open');
}

function ouvrirModalModifierCat(id, name) {
    document.getElementById('cat_id_edit').value   = id;
    document.getElementById('cat_name_edit').value = name;
    effacerErreurs();
    document.getElementById('modalModifierCat').classList.add('open');
}

function fermerModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

// Fermer le modal en cliquant en dehors
document.addEventListener('click', function(e) {
    var modals = document.querySelectorAll('.modal-overlay.open');
    modals.forEach(function(modal) {
        if (e.target === modal) {
            modal.classList.remove('open');
        }
    });
});

// -------------------------------------------------------
// Afficher un message d'erreur
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
// Confirmer la suppression d'un test
// -------------------------------------------------------
function confirmerSuppression(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce test ?')) {
        window.location.href = 'index.php?action=delete&id=' + id;
    }
}
// -------------------------------------------------------
// Confirmer la suppression d'une catégorie
// -------------------------------------------------------
function confirmerSuppressionCat(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Les tests associés seront également supprimés.')) {
        window.location.href = 'index.php?action=cat_delete&id=' + id;
    }
}
