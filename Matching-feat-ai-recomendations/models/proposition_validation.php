<?php

/**
 * Controle de saisie commun creation / edition de proposition (aligne sur les regles metier addprop).
 *
 * @param array<string, mixed> $input
 * @return list<string>
 */
function validate_proposition_form_input(array $input, bool $requireDemandeId = true): array
{
    $errors = [];

    if ($requireDemandeId) {
        $demandeIdRaw = trim((string) ($input['demande_id'] ?? ''));
        if ($demandeIdRaw === '' || !ctype_digit($demandeIdRaw) || (int) $demandeIdRaw < 1) {
            $errors[] = 'Veuillez choisir une demande valide.';
        }
    }

    $freelancerName = trim((string) ($input['freelancer_name'] ?? ''));
    if ($freelancerName === '') {
        $errors[] = 'Le nom affiche est obligatoire.';
    } elseif (mb_strlen($freelancerName) < 3) {
        $errors[] = 'Le nom affiche doit contenir au moins 3 caracteres.';
    } elseif (mb_strlen($freelancerName) > 120) {
        $errors[] = 'Le nom affiche ne doit pas depasser 120 caracteres.';
    }

    $price = trim((string) ($input['price'] ?? ''));
    if ($price === '') {
        $errors[] = 'Le prix propose est obligatoire.';
    } elseif (!is_numeric($price)) {
        $errors[] = 'Le prix propose doit etre un nombre valide.';
    } elseif ((float) $price < 1) {
        $errors[] = 'Le prix propose doit etre superieur ou egal a 1 DT.';
    }

    $message = trim((string) ($input['message'] ?? ''));
    if ($message === '') {
        $errors[] = 'Le message est obligatoire.';
    } elseif (mb_strlen($message) < 15) {
        $errors[] = 'Le message doit contenir au moins 15 caracteres.';
    }

    return $errors;
}
