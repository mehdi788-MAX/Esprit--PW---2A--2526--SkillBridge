<?php

/**
 * Co-pilote SkillBridge — Modération anti-désintermédiation.
 * Détecte les tentatives de partage de contact externe (email, téléphone,
 * messageries tierces) afin que la conversation reste sur la plateforme.
 *
 * N'utilise aucune API externe : règles regex pures, exécution offline.
 */
class Moderator {

    /**
     * Analyse un texte et renvoie les éventuels indices détectés.
     * Format de retour :
     *   [
     *     'flagged' => bool,
     *     'reasons' => string[]   // codes : email | phone | whatsapp | telegram | external_url
     *   ]
     */
    public static function analyze($text) {
        $reasons = [];
        if (!is_string($text) || trim($text) === '') {
            return ['flagged' => false, 'reasons' => []];
        }

        // Normalisation pour réduire les évasions simples (espaces, leet)
        $normalized = mb_strtolower($text, 'UTF-8');
        $translit   = @iconv('UTF-8', 'ASCII//TRANSLIT', $normalized);
        if ($translit === false) $translit = $normalized;

        // Email
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $translit)) {
            $reasons[] = 'email';
        }

        // Téléphone : 8+ chiffres consécutifs (avec espaces, points, tirets, parenthèses tolérés)
        $digitsOnly = preg_replace('/\D+/', '', $translit);
        if (strlen($digitsOnly) >= 8 && preg_match('/(?:\+?\d[\s.\-()]?){8,}/', $translit)) {
            $reasons[] = 'phone';
        }

        // Messageries tierces
        if (preg_match('/\b(whats?app|wa\.me)\b/i', $translit)) {
            $reasons[] = 'whatsapp';
        }
        if (preg_match('/\b(telegram|t\.me)\b/i', $translit)) {
            $reasons[] = 'telegram';
        }

        // Liens externes (hors skillbridge)
        if (preg_match_all('#https?://([a-z0-9.\-]+)#i', $translit, $matches)) {
            foreach ($matches[1] as $host) {
                if (!preg_match('/(skillbridge|localhost)/i', $host)) {
                    $reasons[] = 'external_url';
                    break;
                }
            }
        }

        $reasons = array_values(array_unique($reasons));
        return [
            'flagged' => !empty($reasons),
            'reasons' => $reasons,
            'message' => self::messageFor($reasons),
        ];
    }

    private static function messageFor(array $reasons) {
        if (empty($reasons)) return '';
        $labels = [
            'email'        => 'une adresse email',
            'phone'        => 'un numéro de téléphone',
            'whatsapp'     => 'une référence à WhatsApp',
            'telegram'     => 'une référence à Telegram',
            'external_url' => 'un lien externe',
        ];
        $found = [];
        foreach ($reasons as $r) {
            if (isset($labels[$r])) $found[] = $labels[$r];
        }
        $list = implode(', ', $found);
        return "Notre Co-pilote a détecté $list. SkillBridge encourage les échanges à rester sur la plateforme pour votre protection.";
    }
}
