<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// ---------------------------------------------------------------------------
// JSON / nettoyage
// ---------------------------------------------------------------------------

function aiCleanLine(string $line): string
{
    return trim(preg_replace('/^[-*\d.\)\s]+/', '', $line) ?? '');
}

function aiExtractJson(string $text): ?array
{
    $text = trim($text);
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
    $text = preg_replace('/\s*```$/', '', $text) ?? $text;
    $text = trim($text);

    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    if (preg_match('/\{.*\}/s', $text, $m)) {
        $decoded = json_decode($m[0], true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    if (preg_match('/\[.*\]/s', $text, $m)) {
        $decoded = json_decode($m[0], true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

// ---------------------------------------------------------------------------
// Ollama
// ---------------------------------------------------------------------------

function callOllamaStructured(
    string $systemPrompt,
    string $userPrompt,
    int $timeoutSeconds = 60,
    int $maxTokens = 512,
    bool $enableThinking = false
): string {
    $payload = json_encode(
        ['system' => $systemPrompt, 'user' => $userPrompt],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    return callOllama($payload ?: '', $timeoutSeconds, $maxTokens, $enableThinking);
}

// ---------------------------------------------------------------------------
// Dates
// ---------------------------------------------------------------------------

function ai_normalize_client_deadline(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $m[1] . '-' . $m[2] . '-' . $m[3] : '';
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m)) {
        $d = (int) $m[1];
        $mo = (int) $m[2];
        $y = (int) $m[3];

        return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : '';
    }

    return '';
}

function ai_build_deadline_advice_from_ymd(string $deadlineYmd, string $todayYmd): string
{
    $d1 = DateTimeImmutable::createFromFormat('!Y-m-d', $deadlineYmd);
    $d0 = DateTimeImmutable::createFromFormat('!Y-m-d', $todayYmd);
    if (!$d1 instanceof DateTimeImmutable || !$d0 instanceof DateTimeImmutable) {
        return "Date limite enregistree : {$deadlineYmd}.";
    }
    $display = $d1->format('d/m/Y');
    $days = (int) floor(($d1->getTimestamp() - $d0->getTimestamp()) / 86400);
    if ($days < 0) {
        return "Date limite {$display} : cette echeance est passee par rapport a aujourd'hui ({$todayYmd}). Proposez une nouvelle date.";
    }
    if ($days === 0) {
        return "Date limite {$display} : livraison prevue aujourd'hui — delai tres serre, priorisez les livrables minimum.";
    }
    if ($days <= 7) {
        return "Date limite {$display} : il reste {$days} jour(s) — delai court ; detaillez les livrables et validations attendues.";
    }
    if ($days <= 21) {
        return "Date limite {$display} : environ {$days} jours — raisonnable si le perimetre du projet est bien fige.";
    }

    return "Date limite {$display} : environ {$days} jours — marge confortable si le brief reste stable.";
}

function ai_post_correct_deadline_advice(string $deadlineYmd, string $advice): string
{
    if ($deadlineYmd === '') {
        return $advice;
    }
    $lower = mb_strtolower($advice, 'UTF-8');
    if (preg_match('/\b(manque|absente|non\s*renseign|pas\s+indiqu|sans\s+date)\b/u', $lower)) {
        return ai_build_deadline_advice_from_ymd($deadlineYmd, date('Y-m-d'));
    }

    return $advice;
}

function ai_post_correct_summary_when_deadline_filled(string $deadlineYmd, string $summary, string $title, string $description): string
{
    return $summary;
}

// ---------------------------------------------------------------------------
// Texte / complexite
// ---------------------------------------------------------------------------

function skillbridge_normalize_text_for_match(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($text, Normalizer::FORM_D);
        if (is_string($n) && $n !== '') {
            $text = $n;
        }
    }
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($ascii) && $ascii !== '') {
            $text = $ascii;
        }
    }

    return preg_replace('/\s+/u', ' ', $text) ?? $text;
}

function estimateComplexite(string $title, string $description): string
{
    $norm = skillbridge_normalize_text_for_match($title . ' ' . $description);

    $highPhrases = [
        'intelligence artificielle', 'machine learning', 'formation en ligne',
        'api rest', 'e-commerce', 'plateforme',
    ];
    foreach ($highPhrases as $p) {
        if ($p !== '' && str_contains($norm, $p)) {
            return 'eleve';
        }
    }

    $highWords = [
        'erp', 'marketplace', 'application', 'dashboard', 'automatisation',
        'ecommerce', 'refonte', 'scraper', 'scraping', 'zapier', 'n8n', 'ml',
    ];
    foreach ($highWords as $w) {
        if ($w !== '' && preg_match('/\b' . preg_quote($w, '/') . '\b/u', $norm)) {
            return 'eleve';
        }
    }

    $mediumPhrases = [
        'landing page', 'charte graphique', 'business plan', 'identite visuelle',
        'lettre de motivation',
    ];
    foreach ($mediumPhrases as $p) {
        if ($p !== '' && str_contains($norm, $p)) {
            return 'moyen';
        }
    }

    $mediumWords = [
        'montage', 'video', 'youtube', 'wordpress', 'seo', 'chatbot', 'script',
        'integration', 'audit', 'podcast', 'motion', 'linkedin', 'redaction',
        'article', 'publication', 'rapport', 'animation', 'cv',
    ];
    foreach ($mediumWords as $w) {
        if ($w !== '' && preg_match('/\b' . preg_quote($w, '/') . '\b/u', $norm)) {
            return 'moyen';
        }
    }

    $tokens = preg_split('/\s+/u', trim($norm), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($tokens) < 40) {
        return 'faible';
    }

    return 'moyen';
}

/**
 * @param array<int, array<string, string>> $dataset
 */
function aiEstimateEffortLevel(string $title, string $description, array $dataset): string
{
    $c = estimateComplexite($title, $description);
    if ($c !== 'faible') {
        return $c;
    }

    $counts = [];
    foreach ($dataset as $r) {
        $rowC = (string) ($r['complexite'] ?? '');
        if ($rowC !== '') {
            $counts[$rowC] = ($counts[$rowC] ?? 0) + 1;
        }
    }
    if ($counts !== []) {
        arsort($counts);

        return (string) array_key_first($counts);
    }

    return 'moyen';
}

// ---------------------------------------------------------------------------
// Dataset (SkillBridge CSV)
// ---------------------------------------------------------------------------

function loadDataset(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $path = __DIR__ . '/../data/dataset_skillbridge_100.csv';
    if (!is_readable($path)) {
        return $cache = [];
    }

    $fh = fopen($path, 'rb');
    if ($fh === false) {
        return $cache = [];
    }

    $header = fgetcsv($fh);
    if (!is_array($header)) {
        fclose($fh);

        return $cache = [];
    }
    $header = array_map(static fn($h) => mb_strtolower(trim((string) $h)), $header);

    $rows = [];
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) < count($header)) {
            continue;
        }
        $assoc = [];
        foreach ($header as $i => $key) {
            $assoc[$key] = trim((string) ($row[$i] ?? ''));
        }
        $rows[] = $assoc;
    }
    fclose($fh);

    return $cache = $rows;
}

/**
 * @param array<int, array<string, string>> $dataset
 * @return array<int, array<string, string>>
 */
function findClosestRows(string $complexite, float $budget, int $days, array $dataset, int $limit = 4, string $title = ''): array
{
    if ($dataset === []) {
        return [];
    }

    $titleNorm = skillbridge_normalize_text_for_match($title);
    $titleTokens = array_values(array_filter(
        preg_split('/[^\p{L}\p{N}]+/u', $titleNorm, -1, PREG_SPLIT_NO_EMPTY) ?: [],
        static fn(string $w): bool => mb_strlen($w, 'UTF-8') >= 3
    ));

    $baseScore = static function (array $r) use ($budget, $days): float {
        return (float) (abs((float) ($r['budget_dt'] ?? 0) - $budget) + abs((int) ($r['deadline_jours'] ?? 0) - $days));
    };

    $titleBonus = static function (array $r) use ($titleTokens): float {
        if ($titleTokens === []) {
            return 0.0;
        }
        $n = 0;
        $svc = skillbridge_normalize_text_for_match((string) ($r['service'] ?? ''));
        foreach ($titleTokens as $tok) {
            if ($tok !== '' && str_contains($svc, $tok)) {
                ++$n;
            }
        }

        return $n > 0 ? -200.0 * (float) $n : 0.0;
    };

    $scoreFn = static function (array $r) use ($baseScore, $titleBonus): float {
        return $baseScore($r) + $titleBonus($r);
    };

    $cmp = static function (array $a, array $b) use ($scoreFn): int {
        return $scoreFn($a) <=> $scoreFn($b);
    };

    $exact = array_values(array_filter(
        $dataset,
        static fn(array $r): bool => (string) ($r['complexite'] ?? '') === $complexite
    ));

    if (count($exact) >= 2) {
        usort($exact, $cmp);

        return array_slice($exact, 0, $limit);
    }

    $all = $dataset;
    usort($all, $cmp);

    return array_slice($all, 0, $limit);
}

/**
 * Lignes CSV a considerer pour le benchmark selon la complexite estimee.
 * Inclut une marche adjacente (ex. moyen -> faible + moyen) pour eviter d exclure
 * des services simples (ex. Logo simple) quand le texte declenche a tort « moyen ».
 *
 * @param array<int, array<string, string>> $dataset
 * @return array<int, array<string, string>>
 */
function skillbridge_benchmark_bucket_for_complexity(string $complexite, array $dataset): array
{
    if ($dataset === []) {
        return [];
    }

    $c = trim($complexite);
    if ($c === 'faible') {
        return array_values(array_filter(
            $dataset,
            static fn(array $r): bool => (string) ($r['complexite'] ?? '') === 'faible'
        ));
    }
    if ($c === 'moyen') {
        return array_values(array_filter(
            $dataset,
            static fn(array $r): bool => in_array((string) ($r['complexite'] ?? ''), ['faible', 'moyen'], true)
        ));
    }
    if ($c === 'eleve') {
        return array_values(array_filter(
            $dataset,
            static fn(array $r): bool => in_array((string) ($r['complexite'] ?? ''), ['moyen', 'eleve'], true)
        ));
    }

    return array_values(array_filter(
        $dataset,
        static fn(array $r): bool => (string) ($r['complexite'] ?? '') === $c
    ));
}

/**
 * Penalise une ligne de service du CSV si elle contient des mots marqueurs absents du brief client
 * (ex. « Animation logo » sans le mot « animation » dans le titre/description).
 */
function skillbridge_service_hay_mismatch_penalty(string $hay, string $serviceLabel): int
{
    $svc = mb_strtolower(trim($serviceLabel), 'UTF-8');
    if ($svc === '' || $hay === '') {
        return 0;
    }

    $markers = [
        'animation', 'motion', 'after effect', 'e-commerce', 'ecommerce', 'marketplace',
        'scraping', 'scraper', 'dashboard', 'wordpress', 'zapier', 'n8n', 'podcast',
        'linkedin', 'instagram', 'youtube', 'seo', 'python', 'api', 'machine learning',
        'formation', 'erp', 'chatbot', 'montage video', 'montage',
    ];
    $penalty = 0;
    foreach ($markers as $m) {
        if ($m !== '' && str_contains($svc, $m) && !str_contains($hay, $m)) {
            $penalty -= 45;
        }
    }

    foreach (preg_split('/[^\p{L}\p{N}]+/u', $svc, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
        $w = (string) $w;
        if (mb_strlen($w, 'UTF-8') < 5) {
            continue;
        }
        if (!str_contains($hay, $w)) {
            $penalty -= 18;
        }
    }

    return $penalty;
}

/**
 * @param array<int, array<string, string>> $dataset
 * @return array<int, array<string, string>>
 */
function skillbridge_rows_for_demand_benchmark(string $title, string $description, string $complexite, float $budget, array $dataset): array
{
    if ($dataset === []) {
        return [];
    }

    // Titre compte double : sans description le matching restait faible et le benchmark pouvait
    // basculer tardivement quand le texte long arrivait.
    $tNorm = skillbridge_normalize_text_for_match($title);
    $dNorm = skillbridge_normalize_text_for_match($description);
    $hay = mb_strtolower(trim($tNorm . ' ' . $dNorm . ' ' . $tNorm), 'UTF-8');
    $hayEmpty = trim(mb_strtolower(trim($tNorm . ' ' . $dNorm), 'UTF-8')) === '';

    $bucket = skillbridge_benchmark_bucket_for_complexity($complexite, $dataset);
    if ($bucket === []) {
        $bucket = $dataset;
    }

    $byService = [];
    foreach ($bucket as $r) {
        $svc = trim((string) ($r['service'] ?? ''));
        if ($svc === '') {
            continue;
        }
        $byService[$svc][] = $r;
    }

    $titleTokens = $hayEmpty ? [] : array_values(array_filter(
        preg_split('/[^\p{L}\p{N}]+/u', $hay, -1, PREG_SPLIT_NO_EMPTY) ?: [],
        static fn(string $w): bool => mb_strlen($w, 'UTF-8') >= 2
    ));

    $rank = [];
    foreach ($byService as $svc => $rows) {
        $lex = 0;
        if (!$hayEmpty) {
            $probe = [
                'service' => $svc,
                'complexite' => $complexite,
                'verdict_budget' => 'correct',
                'verdict_deadline' => 'raisonnable',
            ];
            $lex = rag_score_dataset_row($probe, $hay);
            $svcNorm = skillbridge_normalize_text_for_match($svc);
            foreach ($titleTokens as $tok) {
                if ($tok !== '' && str_contains($svcNorm, $tok)) {
                    $lex += 15;
                }
            }
            foreach (preg_split('/[^\p{L}\p{N}]+/u', $svcNorm, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $sw) {
                if (mb_strlen((string) $sw, 'UTF-8') >= 5 && str_contains($hay, (string) $sw)) {
                    $lex += 10;
                }
            }
            $lex += skillbridge_service_hay_mismatch_penalty($hay, $svc);
        }
        $sum = 0.0;
        $n = 0;
        $svcBudgets = [];
        foreach ($rows as $r) {
            $b = (float) ($r['budget_dt'] ?? 0);
            if ($b > 0) {
                $svcBudgets[] = $b;
                $sum += abs($b - $budget);
                ++$n;
            }
        }
        $dist = $n > 0 ? $sum / $n : INF;
        $minS = $svcBudgets !== [] ? min($svcBudgets) : 0.0;
        $maxS = $svcBudgets !== [] ? max($svcBudgets) : 0.0;
        $inBand = $budget > 0.0 && $minS > 0.0 && $budget >= $minS && $budget <= $maxS ? 1 : 0;
        $bandDist = 0.0;
        if ($inBand === 0 && $budget > 0.0 && $minS > 0.0) {
            if ($budget < $minS) {
                $bandDist = (float) ($minS - $budget);
            } elseif ($budget > $maxS) {
                $bandDist = (float) ($budget - $maxS);
            }
        }
        $rank[] = [
            'svc' => $svc,
            'lex' => $lex,
            'dist' => $dist,
            'in_band' => $inBand,
            'band_dist' => $bandDist,
        ];
    }

    if ($rank === []) {
        return [];
    }

    usort($rank, static function (array $a, array $b): int {
        if (($a['in_band'] ?? 0) !== ($b['in_band'] ?? 0)) {
            return ($b['in_band'] ?? 0) <=> ($a['in_band'] ?? 0);
        }
        if (($a['in_band'] ?? 0) === 1 && ($b['in_band'] ?? 0) === 1) {
            if ($a['lex'] !== $b['lex']) {
                return $b['lex'] <=> $a['lex'];
            }

            return $a['dist'] <=> $b['dist'];
        }
        // Hors fourchette (ou budget 0) : le texte prime, sinon un petit prix etait associe
        // au mauvais service (ex. 9 DT plus proche de 30 que de 80 -> « Correction texte » au lieu de « Logo simple »).
        if ($a['lex'] !== $b['lex']) {
            return $b['lex'] <=> $a['lex'];
        }
        if (($a['band_dist'] ?? 0.0) !== ($b['band_dist'] ?? 0.0)) {
            return ($a['band_dist'] ?? 0.0) <=> ($b['band_dist'] ?? 0.0);
        }

        return $a['dist'] <=> $b['dist'];
    });

    $bestSvc = (string) ($rank[0]['svc'] ?? '');

    return $bestSvc !== '' ? $byService[$bestSvc] : [];
}

// ---------------------------------------------------------------------------
// Evaluation budget / delai (PHP + CSV)
// ---------------------------------------------------------------------------

function evaluateFromDataset(float $budget, int $deadlineDays, array $rows): array
{
    if ($rows === []) {
        $p = __DIR__ . '/../data/dataset_skillbridge_100.csv';

        return [
            'verdict_budget' => 'inconnu',
            'verdict_deadline' => 'inconnu',
            'conseil_budget' => 'Dataset introuvable. Verifiez le fichier : ' . $p,
            'conseil_deadline' => 'Dataset introuvable. Impossible de comparer le delai.',
        ];
    }

    $budgets = array_values(array_filter(
        array_map(static fn($r) => (float) ($r['budget_dt'] ?? 0), $rows),
        static fn($x) => $x > 0
    ));
    sort($budgets);
    $minB = !empty($budgets) ? min($budgets) : 0;
    $maxB = !empty($budgets) ? max($budgets) : 0;

    $refSvc = trim((string) ($rows[0]['service'] ?? ''));
    $refLabel = $refSvc !== '' ? " Reference dataset : « {$refSvc} »." : '';
    $bDisp = abs($budget - round($budget)) < 1e-6 ? (string) (int) round($budget) : rtrim(rtrim(number_format($budget, 2, '.', ''), '0'), '.');

    if ($budget <= 0) {
        $vb = 'inconnu';
        $cb = "Indiquez un budget en DT. Les projets similaires se situent entre {$minB} et {$maxB} DT.{$refLabel}";
    } elseif ($budget < $minB) {
        $vb = 'trop_bas';
        $cb = "Budget trop bas : {$bDisp} DT est sous la fourchette observee ({$minB}–{$maxB} DT) pour cette ligne de benchmark.{$refLabel}";
    } elseif ($budget <= $maxB) {
        $vb = 'correct';
        $cb = "Budget correct : {$bDisp} DT se situe dans la plage observee ({$minB}–{$maxB} DT) pour cette ligne de benchmark.{$refLabel}";
    } else {
        $vb = 'confortable';
        $cb = "Budget confortable : {$bDisp} DT depasse la fourchette haute ({$minB}–{$maxB} DT) pour cette ligne de benchmark.{$refLabel}";
    }

    $joursEstimes = array_values(array_filter(
        array_map(static fn($r) => (int) ($r['jours_estimes'] ?? 0), $rows),
        static fn($x) => $x > 0
    ));
    $minJ = !empty($joursEstimes) ? min($joursEstimes) : 1;

    if ($deadlineDays <= 0) {
        $vd = 'impossible';
        $cd = "La date limite est deja passee ou invalide. Choisissez une date future.";
    } elseif ($deadlineDays < $minJ) {
        $vd = 'impossible';
        $cd = "Delai impossible : {$deadlineDays} jour(s) disponible(s) alors que ce type de service necessite au minimum {$minJ} jour(s).";
    } elseif ($deadlineDays < (int) round($minJ * 1.3)) {
        $vd = 'serre';
        $cd = "Delai serre : {$deadlineDays} jour(s) pour un service qui demande environ {$minJ} jour(s).";
    } elseif ($deadlineDays < $minJ * 2) {
        $vd = 'raisonnable';
        $cd = "Delai raisonnable : {$deadlineDays} jour(s) pour environ {$minJ} jour(s) de travail.";
    } else {
        $vd = 'large';
        $cd = "Delai confortable : {$deadlineDays} jour(s) pour environ {$minJ} jour(s) de travail.";
    }

    return [
        'verdict_budget' => $vb,
        'verdict_deadline' => $vd,
        'conseil_budget' => $cb,
        'conseil_deadline' => $cd,
    ];
}

function evaluateWithDataset(string $title, string $description, $price, $deadline): array
{
    $dataset = loadDataset();

    if ($dataset === []) {
        return [
            'verdict_budget' => 'correct',
            'verdict_deadline' => 'raisonnable',
            'conseil_budget' => 'Dataset introuvable ou vide : data/dataset_skillbridge_100.csv',
            'conseil_deadline' => 'Dataset introuvable : impossible de comparer le delai.',
        ];
    }

    $deadlineYmd = ai_normalize_client_deadline((string) $deadline);
    $today = new DateTimeImmutable('today');
    $deadlineDays = 0;
    if ($deadlineYmd !== '') {
        $dEnd = DateTimeImmutable::createFromFormat('!Y-m-d', $deadlineYmd);
        if ($dEnd instanceof DateTimeImmutable) {
            $deadlineDays = (int) floor(($dEnd->getTimestamp() - $today->getTimestamp()) / 86400);
        }
    }

    $budget = (float) str_replace(',', '.', trim((string) $price));
    if (!is_finite($budget)) {
        $budget = 0.0;
    }

    $complexite = aiEstimateEffortLevel($title, $description, $dataset);
    $rows = skillbridge_rows_for_demand_benchmark($title, $description, $complexite, $budget, $dataset);

    return evaluateFromDataset($budget, $deadlineDays, $rows);
}

function skillbridge_format_price_advice_line(array $ev): string
{
    $v = $ev['verdict_budget'] ?? 'correct';
    $c = trim((string) ($ev['conseil_budget'] ?? ''));

    return 'Verdict budget : ' . $v . '. ' . $c;
}

function skillbridge_format_deadline_advice_line(array $ev): string
{
    $v = $ev['verdict_deadline'] ?? 'raisonnable';
    $c = trim((string) ($ev['conseil_deadline'] ?? ''));

    return 'Verdict delai : ' . $v . '. ' . $c;
}

// ---------------------------------------------------------------------------
// Qwen : resume + suggestions (sans chiffres inventes dans le summary)
// ---------------------------------------------------------------------------

function aiFallbackSummary(string $title, string $description): string
{
    $t = trim($title);
    if ($t !== '') {
        return 'Besoin resume autour de : ' . mb_substr($t, 0, 120) . '.';
    }

    return 'Precisez le titre et la description pour un resume plus utile.';
}

function aiFallbackSuggestions(): array
{
    return [
        'Precisez les livrables attendus (formats, nombre de versions).',
        'Indiquez le public cible et le ton souhaite.',
        'Ajoutez des contraintes techniques ou de branding connues.',
    ];
}

function askSummaryOnly(string $title, string $description): array
{
    $shortDesc = mb_substr(trim($description), 0, 200);

    $system = 'Tu es un conseiller freelance. Reponds UNIQUEMENT en JSON valide, sans markdown. '
        . 'Ne mentionne jamais de prix, de budget, de delai ou de duree dans le summary.';

    $user = "Titre: {$title}\nDescription: {$shortDesc}\n"
        . "Ecris un summary de 1-2 phrases qui decrit UNIQUEMENT le besoin metier. "
        . "INTERDIT : ne mentionne aucun chiffre, aucun prix, aucun delai, aucune duree. "
        . "Donne 3 conseils concrets pour ameliorer le brief.\n"
        . 'Retourne: {"summary":"...","brief_suggestions":["...","...","..."]}';

    $raw = callOllamaStructured($system, $user, 45, 384, false);
    $json = aiExtractJson($raw);

    if (is_array($json)) {
        $suggestions = array_slice(
            array_values(array_filter(array_map('strval', $json['brief_suggestions'] ?? []))),
            0,
            3
        );

        return [
            'summary' => trim((string) ($json['summary'] ?? '')),
            'brief_suggestions' => $suggestions !== [] ? $suggestions : aiFallbackSuggestions(),
        ];
    }

    return [
        'summary' => aiFallbackSummary($title, $description),
        'brief_suggestions' => aiFallbackSuggestions(),
    ];
}

function analyzeDemandWithAi(
    string $title,
    string $description,
    $price = '',
    $deadline = '',
    string $prevSummary = '',
    string $prevPriceAdvice = '',
    string $prevDeadlineAdvice = ''
): array {
    $price = trim((string) $price);
    $deadlineYmd = ai_normalize_client_deadline((string) $deadline);

    $datasetEval = evaluateWithDataset($title, $description, $price, $deadlineYmd !== '' ? $deadlineYmd : trim((string) $deadline));

    $aiResult = askSummaryOnly($title, $description);
    $sum = ai_post_correct_summary_when_deadline_filled(
        $deadlineYmd,
        trim((string) ($aiResult['summary'] ?? '')),
        $title,
        $description
    );
    $suggestions = $aiResult['brief_suggestions'] ?? aiFallbackSuggestions();
    if (!is_array($suggestions) || $suggestions === []) {
        $suggestions = aiFallbackSuggestions();
    }

    $deadlineAdvice = skillbridge_format_deadline_advice_line($datasetEval);
    if ($deadlineYmd !== '') {
        $deadlineAdvice = ai_post_correct_deadline_advice($deadlineYmd, $deadlineAdvice);
    }

    return [
        'summary' => $sum !== '' ? $sum : aiFallbackSummary($title, $description),
        'price_advice' => skillbridge_format_price_advice_line($datasetEval),
        'deadline_advice' => $deadlineAdvice,
        'brief_suggestions' => array_slice(array_values(array_map('strval', $suggestions)), 0, 3),
    ];
}

// ---------------------------------------------------------------------------
// RAG recommandations (dataset + Qwen)
// ---------------------------------------------------------------------------

function rag_score_dataset_row(array $row, string $haystack): int
{
    $score = 0;
    $service = mb_strtolower($row['service'] ?? '', 'UTF-8');
    if ($service !== '' && str_contains($haystack, $service)) {
        $score += 22;
    }
    foreach (preg_split('/[^\p{L}\p{N}]+/u', $service, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
        if (mb_strlen($w) < 3) {
            continue;
        }
        if (str_contains($haystack, $w)) {
            $score += 4;
        }
    }

    return $score;
}

function rag_profile_stem(string $competences, string $bio): string
{
    $s = skillbridge_normalize_text_for_match(trim($competences) . ' ' . trim($bio));

    return mb_strtolower($s, 'UTF-8');
}

/**
 * Pont heuristique : profil oriente dev / web et demande type site, app, WordPress, etc.
 * (evite d exiger le mot exact « PHP » dans le texte client.)
 */
function rag_dev_profile_demande_bridge(string $profileStem, string $dHay): int
{
    if ($profileStem === '' || $dHay === '') {
        return 0;
    }

    $devSignals = [
        'developpeur', 'developpeuse', 'fullstack', 'full-stack', 'programmeur', 'programmeuse',
        'freelance', 'php', 'javascript', 'typescript', 'mysql', 'mariadb', 'postgresql', 'react',
        'node', 'laravel', 'symfony', 'django', 'vue', 'angular', 'java', 'python', 'spring',
        'html', 'css', 'sql', 'api', 'backend', 'frontend', 'web', 'devops', 'docker', 'kubernetes',
    ];
    $demandWebSignals = [
        'site', 'siteweb', 'web', 'application', 'wordpress', 'woocommerce', 'laravel', 'symfony',
        'api', 'ecommerce', 'e-commerce', 'boutique', 'plateforme', 'dashboard', 'blog', 'intranet',
        'integration', 'scraping', 'donnees', 'backend', 'frontend', 'saas', 'mobile', 'app',
        'refonte', 'maintenance', 'hebergement', 'serveur', 'base',
    ];

    $hasDev = false;
    foreach ($devSignals as $m) {
        if ($m !== '' && str_contains($profileStem, $m)) {
            $hasDev = true;
            break;
        }
    }
    if (!$hasDev) {
        return 0;
    }

    foreach ($demandWebSignals as $m) {
        if ($m !== '' && str_contains($dHay, $m)) {
            return 14;
        }
    }

    return 0;
}

/**
 * Mots du profil (competences + bio) retrouves dans le titre/description de la demande.
 */
function rag_profile_demande_token_overlap(string $competences, string $bio, array $demande): int
{
    $profileStem = rag_profile_stem($competences, $bio);
    if ($profileStem === '') {
        return 0;
    }
    $demStem = skillbridge_normalize_text_for_match(
        trim((string) ($demande['title'] ?? '')) . ' ' . trim((string) ($demande['description'] ?? ''))
    );
    $dHay = mb_strtolower($demStem, 'UTF-8');
    if ($dHay === '') {
        return 0;
    }
    $score = 0;
    $shortTech = [
        'php', 'sql', 'css', 'seo', 'ux', 'ui', 'api', 'web', 'git', 'aws', 'gcp', 'vue', 'rss',
        'pdf', 'xml', 'svg', 'cdn', 'sdk', 'ide', 'cli', 'nlp', 'gpu', 'cpu',
    ];
    foreach (preg_split('/[^\p{L}\p{N}]+/u', $profileStem, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
        $w = (string) $w;
        $len = mb_strlen($w, 'UTF-8');
        if ($len >= 4 && str_contains($dHay, $w)) {
            $score += 10;
        } elseif ($len === 3 && in_array($w, $shortTech, true) && str_contains($dHay, $w)) {
            $score += 8;
        }
    }

    return $score + rag_dev_profile_demande_bridge($profileStem, $dHay);
}

function rag_demande_touches_service_row(array $demande, array $datasetRow): bool
{
    $blob = mb_strtolower(($demande['title'] ?? '') . ' ' . ($demande['description'] ?? ''), 'UTF-8');
    $svc = mb_strtolower(trim((string) ($datasetRow['service'] ?? '')), 'UTF-8');
    if ($svc === '' || $blob === '') {
        return false;
    }
    if (str_contains($blob, $svc)) {
        return true;
    }
    foreach (preg_split('/[^\p{L}\p{N}]+/u', $svc, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $tok) {
        if (mb_strlen((string) $tok) >= 4 && str_contains($blob, (string) $tok)) {
            return true;
        }
    }

    return false;
}

/**
 * @param array<int, array<string, mixed>> $candidates
 * @return array<int, array<string, mixed>>
 */
function rag_candidates_grounded_in_dataset(string $competences, string $bio, array $candidates): array
{
    $rows = loadDataset();
    if ($rows === []) {
        return [];
    }

    $profileStem = rag_profile_stem($competences, $bio);
    if ($profileStem === '') {
        return [];
    }

    $minHayScore = 8;
    $minProfileDemandOverlap = 8;
    $minProfileDatasetScore = 4;

    $out = [];
    foreach ($candidates as $d) {
        $hay = mb_strtolower(
            $profileStem . ' ' . skillbridge_normalize_text_for_match(
                ($d['title'] ?? '') . ' ' . ($d['description'] ?? '') . ' ' . ($d['price'] ?? '')
            ),
            'UTF-8'
        );
        $bestRow = null;
        $bestScore = 0;
        foreach ($rows as $r) {
            $sc = rag_score_dataset_row($r, $hay);
            if ($sc > $bestScore) {
                $bestScore = $sc;
                $bestRow = $r;
            }
        }
        if ($bestRow === null || $bestScore < $minHayScore) {
            continue;
        }
        if (!rag_demande_touches_service_row($d, $bestRow)) {
            continue;
        }
        $profVsRow = rag_score_dataset_row($bestRow, $profileStem);
        $overlapPd = rag_profile_demande_token_overlap($competences, $bio, $d);

        if ($overlapPd < $minProfileDemandOverlap) {
            $relaxed = ($profVsRow >= 10 && $bestScore >= 12);
            if (!$relaxed) {
                continue;
            }
        }
        if ($profVsRow < $minProfileDatasetScore && $overlapPd < 14) {
            continue;
        }
        $d['_dataset_row'] = $bestRow;
        $d['_dataset_score'] = $bestScore;
        $out[] = $d;
    }
    usort($out, static fn($a, $b) => ((int) ($b['_dataset_score'] ?? 0)) <=> ((int) ($a['_dataset_score'] ?? 0)));

    return array_slice($out, 0, 10);
}

/**
 * @param array<int, array<string, mixed>> $candidates
 * @return array{has_relevant_evidence: bool, user_dataset_section: string, top_score: int}
 */
function rag_build_recommendation_context(string $competences, string $bio, array $candidates): array
{
    $rows = loadDataset();
    if ($rows === []) {
        return [
            'has_relevant_evidence' => false,
            'user_dataset_section' => "REFERENCE DATASET : fichier data/dataset_skillbridge_100.csv absent ou vide.\n",
            'top_score' => 0,
        ];
    }

    $projText = '';
    foreach ($candidates as $d) {
        $projText .= ' ' . ($d['title'] ?? '') . ' ' . ($d['description'] ?? '');
    }
    $hay = mb_strtolower($competences . ' ' . $bio . ' ' . $projText, 'UTF-8');

    $scored = [];
    foreach ($rows as $r) {
        $scored[] = ['row' => $r, 'score' => rag_score_dataset_row($r, $hay)];
    }
    usort($scored, static fn($a, $b) => $b['score'] <=> $a['score']);

    $topScore = (int) ($scored[0]['score'] ?? 0);
    $topRows = array_slice($scored, 0, 12);
    $lines = [];
    $lines[] = 'Colonnes : complexite | service | budget_dt | jours_estimes | deadline_jours | verdict_budget | verdict_deadline';
    foreach ($topRows as $item) {
        if ($item['score'] < 1) {
            break;
        }
        $x = $item['row'];
        $lines[] = sprintf(
            '%s | %s | %s DT | %sj travail | delai client max %sj | %s | %s',
            $x['complexite'] ?? '',
            $x['service'] ?? '',
            $x['budget_dt'] ?? '',
            $x['jours_estimes'] ?? '',
            $x['deadline_jours'] ?? '',
            $x['verdict_budget'] ?? '',
            $x['verdict_deadline'] ?? ''
        );
    }

    $section = "REFERENCE DATASET (extrait utile) :\n"
        . (count($lines) > 1 ? implode("\n", $lines) : '(aucune ligne assez proche)')
        . "\n";

    return [
        'has_relevant_evidence' => $topScore >= 6,
        'user_dataset_section' => $section,
        'top_score' => $topScore,
    ];
}

/**
 * @param array<int, array<string, mixed>> $ranked
 * @return array<int, array<string, mixed>>
 */
function skillbridge_dedupe_ranked_recommendations(array $ranked): array
{
    $seen = [];
    $out = [];
    foreach ($ranked as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id < 1 || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $out[] = $row;
    }

    return $out;
}

function getRecommendations($user_id): array
{
    global $pdo;

    $userId = (int) $user_id;
    if ($userId < 1 || !$pdo instanceof PDO) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT competences, bio FROM profils WHERE utilisateur_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $competences = trim((string) ($profile['competences'] ?? ''));
    $bio = trim((string) ($profile['bio'] ?? ''));

    if (rag_profile_stem($competences, $bio) === '') {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT id, title, description, price, deadline, created_at
         FROM demandes
         WHERE deadline >= :today
         ORDER BY created_at DESC
         LIMIT 20'
    );
    $stmt->execute([':today' => date('Y-m-d')]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($candidates === []) {
        return [];
    }

    $grounded = rag_candidates_grounded_in_dataset($competences, $bio, $candidates);
    if ($grounded === []) {
        return [];
    }

    $demandeSummaries = [];
    foreach ($grounded as $d) {
        $demandeSummaries[] = [
            'id' => (int) $d['id'],
            'title' => mb_substr((string) $d['title'], 0, 80),
            'description' => mb_substr((string) $d['description'], 0, 180),
            'price' => $d['price'],
            'deadline' => $d['deadline'],
        ];
    }

    $demandesJson = json_encode($demandeSummaries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $validIds = json_encode(array_column($grounded, 'id'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $rag = rag_build_recommendation_context($competences, $bio, $grounded);
    $ds = $rag['user_dataset_section'];

    $system = 'Tu es un moteur de recommandation pour SkillBridge (Tunisie). '
        . 'Reponds UNIQUEMENT avec un tableau JSON valide, sans markdown. '
        . 'Format : [{"id":42,"reason":"..."}] max 5 elements. Ids uniquement parmi la liste fournie. Raisons en francais.';

    $user = "PROFIL :\nCompetences : {$competences}\nBio : {$bio}\n\n{$ds}\n"
        . "PROJETS (JSON) :\n{$demandesJson}\n\n"
        . "Selectionne au plus 5 projets pertinents pour ce profil. Ids autorises : {$validIds}.";

    $raw = callOllamaStructured($system, $user, 60, 400, false);
    $json = aiExtractJson($raw);

    $byId = array_column($grounded, null, 'id');
    $ranked = [];
    $seenIds = [];

    if (is_array($json)) {
        $items = isset($json['recommendations']) && is_array($json['recommendations']) ? $json['recommendations'] : $json;
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $id = (int) ($item['id'] ?? 0);
                $reason = trim((string) ($item['reason'] ?? ''));
                if ($id > 0 && isset($byId[$id]) && $reason !== '' && !isset($seenIds[$id])) {
                    $row = $byId[$id];
                    $row['ai_reason'] = $reason;
                    unset($row['_dataset_row'], $row['_dataset_score']);
                    $ranked[] = $row;
                    $seenIds[$id] = true;
                }
                if (count($ranked) >= 5) {
                    break;
                }
            }
        }
    }

    $autoFilled = 0;
    $maxAutoFill = 2;
    if (count($ranked) < 5) {
        foreach ($grounded as $row) {
            if (count($ranked) >= 5 || $autoFilled >= $maxAutoFill) {
                break;
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1 || isset($seenIds[$id])) {
                continue;
            }
            $br = $row['_dataset_row'] ?? null;
            if (is_array($br)) {
                $row['ai_reason'] = 'Dataset : ' . ($br['service'] ?? '') . ' — budget ' . ($br['verdict_budget'] ?? '') . ', delai ' . ($br['verdict_deadline'] ?? '') . '.';
            } else {
                $row['ai_reason'] = 'Projet aligne avec le referentiel interne.';
            }
            unset($row['_dataset_row'], $row['_dataset_score']);
            $ranked[] = $row;
            $seenIds[$id] = true;
            ++$autoFilled;
        }
    }

    return array_slice(skillbridge_dedupe_ranked_recommendations($ranked), 0, 5);
}

// ---------------------------------------------------------------------------
// Proposition
// ---------------------------------------------------------------------------

function generateProposalDraft(array $demande, array $freelancerProfile = []): string
{
    $system = 'Tu es un assistant de redaction pour freelancers sur SkillBridge. '
        . 'Reponds directement avec le texte de la proposition, sans introduction.';

    $demandeJson = json_encode($demande, JSON_UNESCAPED_UNICODE);
    $profileJson = json_encode($freelancerProfile, JSON_UNESCAPED_UNICODE);

    $user = "Redige une proposition freelance pour ce projet.\n\nPROJET :\n{$demandeJson}\n\nPROFIL :\n{$profileJson}\n\n"
        . '5 a 8 phrases, ton professionnel.';

    $response = callOllamaStructured($system, $user, 60, 450, false);

    return $response !== ''
        ? $response
        : "Bonjour, je peux vous accompagner sur ce projet. Je propose de clarifier les livrables puis livrer une premiere version avec retours integres.";
}

function scoreProposalMatch(array $demande, array $proposition): string
{
    $system = 'Tu es un evaluateur de matching freelance-client sur SkillBridge. Reponds en francais, synthese courte.';

    $demandeJson = json_encode($demande, JSON_UNESCAPED_UNICODE);
    $propositionJson = json_encode($proposition, JSON_UNESCAPED_UNICODE);

    $user = "Evalue le matching entre cette demande et cette proposition.\n\nDEMANDE :\n{$demandeJson}\n\nPROPOSITION :\n{$propositionJson}";

    $response = callOllamaStructured($system, $user, 60, 400, false);

    return $response !== ''
        ? $response
        : 'Comparez la proposition au besoin sur le perimetre, les livrables et le budget.';
}
