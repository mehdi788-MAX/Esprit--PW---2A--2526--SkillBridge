<?php

declare(strict_types=1);

/**
 * AiRecommendationService — moteur d'analyse / recommandation pour SkillBridge.
 *
 * - analyzeDemand()                : retour live du formulaire « publier une demande ».
 * - recommendDemandesForFreelancer(): top N demandes alignees avec un profil freelance.
 * - isAvailable()                  : ping rapide d'Ollama (1.5 s).
 *
 * Toutes les methodes sont statiques et tolerent une panne d'Ollama : le formulaire
 * doit rester fonctionnel meme reseau coupe (verdicts pris du dataset CSV en pur PHP).
 */
final class AiRecommendationService
{
    /** Cache du CSV : on relit pas le fichier sur chaque appel d'une meme requete. */
    private static ?array $datasetCache = null;

    // ---------------------------------------------------------------------
    // API publique
    // ---------------------------------------------------------------------

    /** Ping de l'API Ollama : reachable + modele charge. Timeout 1.5 s pour ne pas bloquer le formulaire. */
    public static function isAvailable(): bool
    {
        $ch = curl_init(self::ollamaTagsUrl());
        if ($ch === false) {
            return false;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_TIMEOUT_MS        => 1500,
            CURLOPT_CONNECTTIMEOUT_MS => 1000,
            CURLOPT_NOSIGNAL          => 1,
        ]);
        $response = curl_exec($ch);
        $http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is a no-op since PHP 8.0; resource frees on scope exit.

        if (!is_string($response) || $http !== 200) {
            return false;
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !is_array($decoded['models'] ?? null)) {
            return false;
        }

        $expected = self::ollamaModel();
        foreach ($decoded['models'] as $m) {
            $name = (string) ($m['name'] ?? $m['model'] ?? '');
            if ($name === $expected || str_starts_with($name, $expected)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Analyse live d'une demande. Echec Ollama -> fallback texte ; verdicts toujours
     * calcules en PHP a partir du CSV de reference.
     *
     * @return array{summary: string, price_advice: string, deadline_advice: string, brief_suggestions: array<int, string>}
     */
    public static function analyzeDemand(
        string $title,
        string $description,
        string|float|int $price = '',
        string $deadline = ''
    ): array {
        $title       = trim($title);
        $description = trim($description);
        $priceStr    = trim((string) $price);
        $deadlineYmd = self::normalizeClientDeadline((string) $deadline);

        $datasetEval = self::evaluateWithDataset(
            $title,
            $description,
            $priceStr,
            $deadlineYmd !== '' ? $deadlineYmd : trim((string) $deadline)
        );

        $aiResult = self::askSummaryOnly($title, $description);
        $summary  = trim((string) ($aiResult['summary'] ?? ''));
        if ($summary === '') {
            $summary = self::fallbackSummary($title, $description);
        }
        $suggestions = $aiResult['brief_suggestions'] ?? [];
        if (!is_array($suggestions) || $suggestions === []) {
            $suggestions = self::fallbackSuggestions();
        }

        $priceAdvice    = self::formatPriceAdviceLine($datasetEval);
        $deadlineAdvice = self::formatDeadlineAdviceLine($datasetEval);
        if ($deadlineYmd !== '') {
            $deadlineAdvice = self::postCorrectDeadlineAdvice($deadlineYmd, $deadlineAdvice);
        }

        return [
            'summary'           => $summary,
            'price_advice'      => $priceAdvice,
            'deadline_advice'   => $deadlineAdvice,
            'brief_suggestions' => array_slice(array_values(array_map('strval', $suggestions)), 0, 3),
        ];
    }

    /**
     * Top N demandes pour un profil freelance. Ranking 100% PHP/dataset, pas d'Ollama.
     *
     * @param array{id?: int, competences?: string, bio?: string} $freelancer
     * @param array<int, array<string, mixed>>                    $candidateDemandes
     * @return array<int, array<string, mixed>>
     */
    public static function recommendDemandesForFreelancer(
        array $freelancer,
        array $candidateDemandes,
        int $limit = 3
    ): array {
        $competences = trim((string) ($freelancer['competences'] ?? ''));
        $bio         = trim((string) ($freelancer['bio'] ?? ''));
        if (self::profileStem($competences, $bio) === '' || $candidateDemandes === []) {
            return [];
        }

        $dataset = self::loadDataset();
        $scored  = [];
        $seen    = [];
        foreach ($candidateDemandes as $demande) {
            $id = (int) ($demande['id'] ?? 0);
            if ($id < 1 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $sc = self::scoreDemandeForProfile($competences, $bio, $demande, $dataset);
            if ($sc['score'] <= 0) {
                continue;
            }
            $row                  = $demande;
            $row['_match_score']  = $sc['score'];
            $row['_match_reason'] = $sc['reason'];
            $scored[]             = $row;
        }

        usort($scored, static fn(array $a, array $b): int => ((int) $b['_match_score']) <=> ((int) $a['_match_score']));
        return array_slice($scored, 0, max(1, $limit));
    }

    /**
     * Coach IA pour le freelancer en train de rédiger sa proposition.
     *
     * Combine :
     *   - les verdicts dataset (prix annoncé / délai) sur la demande,
     *   - un score de matching entre le profil et la demande,
     *   - 3 conseils courts pour structurer le pitch.
     *
     * @param array $demande     ['title','description','price','deadline']
     * @param array $freelancer  ['competences','bio'] — peuvent être vides
     * @return array {
     *   summary:      string,    // synthèse du projet (réutilise analyzeDemand)
     *   price_advice: string,    // verdict prix vs benchmark
     *   deadline_advice: string,
     *   match_score:  int,       // 0..100 — match profil vs demande
     *   match_reason: string,    // explication courte
     *   pitch_tips:   string[],  // 3 conseils tactiques pour la proposition
     * }
     */
    public static function analyzeProposition(array $demande, array $freelancer): array
    {
        $title       = (string) ($demande['title']       ?? '');
        $description = (string) ($demande['description'] ?? '');
        $price       = $demande['price']    ?? '';
        $deadline    = $demande['deadline'] ?? '';

        $base = self::analyzeDemand($title, $description, $price, $deadline);

        // Match score : on réutilise le scoring RAG (1 candidat).
        $matchScore  = 0;
        $matchReason = '';
        $compStr = trim((string) ($freelancer['competences'] ?? ''));
        $bioStr  = trim((string) ($freelancer['bio'] ?? ''));
        if (self::profileStem($compStr, $bioStr) !== '') {
            $sc = self::scoreDemandeForProfile($compStr, $bioStr, $demande, self::loadDataset());
            $matchScore  = (int) $sc['score'];
            $matchReason = (string) $sc['reason'];
        }

        return [
            'summary'         => (string) ($base['summary']         ?? ''),
            'price_advice'    => (string) ($base['price_advice']    ?? ''),
            'deadline_advice' => (string) ($base['deadline_advice'] ?? ''),
            'match_score'     => $matchScore,
            'match_reason'    => $matchReason,
            // On réutilise les "brief_suggestions" comme conseils côté pitch.
            // Le LLM produit déjà des angles d'amélioration utiles à un freelancer
            // (livrables, contraintes, public cible) qui font de bons hooks de pitch.
            'pitch_tips'      => array_values((array) ($base['brief_suggestions'] ?? [])),
        ];
    }

    // ---------------------------------------------------------------------
    // Ollama
    // ---------------------------------------------------------------------

    private static function ollamaChatUrl(): string
    {
        $url = getenv('OLLAMA_API_URL');
        return is_string($url) && $url !== '' ? $url : 'http://127.0.0.1:11434/api/chat';
    }

    /** Derive /api/tags depuis OLLAMA_API_URL pour partager la config (host/port). */
    private static function ollamaTagsUrl(): string
    {
        if (preg_match('#^(https?://[^/]+)#i', self::ollamaChatUrl(), $m)) {
            return $m[1] . '/api/tags';
        }
        return 'http://127.0.0.1:11434/api/tags';
    }

    private static function ollamaModel(): string
    {
        $model = getenv('OLLAMA_MODEL');
        return is_string($model) && $model !== '' ? $model : 'qwen3:0.6b';
    }

    /**
     * Appel Ollama /api/chat. Retourne "" sur n'importe quelle erreur — ne lance jamais.
     *
     * Quirks :
     *  - qwen3 emet <think>...</think> par defaut ; on force think:false ET on strip
     *    tout residu eventuel pour ne pas polluer le JSON renvoye.
     *  - num_predict (= maxTokens) doit etre passe via "options" pour Ollama.
     */
    private static function callOllama(
        string $systemPrompt,
        string $userPrompt,
        int $timeoutSeconds = 60,
        int $maxTokens = 512,
        bool $enableThinking = false
    ): string {
        $payload = [
            'model'    => self::ollamaModel(),
            'stream'   => false,
            'think'    => $enableThinking,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'options'  => ['num_predict' => $maxTokens, 'temperature' => 0.2],
        ];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return '';
        }
        $ch = curl_init(self::ollamaChatUrl());
        if ($ch === false) {
            return '';
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => max(5, $timeoutSeconds),
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_NOSIGNAL       => 1,
        ]);
        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is a no-op since PHP 8.0.

        if (!is_string($raw) || $http < 200 || $http >= 300) {
            return '';
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return '';
        }
        $content = (string) ($decoded['message']['content'] ?? $decoded['response'] ?? '');
        $content = preg_replace('#<think>.*?</think>#is', '', $content) ?? $content;
        return trim($content);
    }

    private static function aiExtractJson(string $text): ?array
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

    // ---------------------------------------------------------------------
    // Texte / dates / complexite
    // ---------------------------------------------------------------------

    /** Minuscules + suppression accents : le CSV est en ASCII. */
    private static function normalizeTextForMatch(string $text): string
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

    private static function normalizeClientDeadline(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? "{$m[1]}-{$m[2]}-{$m[3]}" : '';
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m)) {
            $d = (int) $m[1]; $mo = (int) $m[2]; $y = (int) $m[3];
            return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : '';
        }
        return '';
    }

    private static function buildDeadlineAdviceFromYmd(string $deadlineYmd, string $todayYmd): string
    {
        $d1 = DateTimeImmutable::createFromFormat('!Y-m-d', $deadlineYmd);
        $d0 = DateTimeImmutable::createFromFormat('!Y-m-d', $todayYmd);
        if (!$d1 instanceof DateTimeImmutable || !$d0 instanceof DateTimeImmutable) {
            return "Date limite enregistree : {$deadlineYmd}.";
        }
        $display = $d1->format('d/m/Y');
        $days    = (int) floor(($d1->getTimestamp() - $d0->getTimestamp()) / 86400);
        if ($days < 0)  return "Date limite {$display} : cette echeance est passee par rapport a aujourd'hui ({$todayYmd}). Proposez une nouvelle date.";
        if ($days === 0) return "Date limite {$display} : livraison prevue aujourd'hui — delai tres serre, priorisez les livrables minimum.";
        if ($days <= 7)  return "Date limite {$display} : il reste {$days} jour(s) — delai court ; detaillez les livrables et validations attendues.";
        if ($days <= 21) return "Date limite {$display} : environ {$days} jours — raisonnable si le perimetre du projet est bien fige.";
        return "Date limite {$display} : environ {$days} jours — marge confortable si le brief reste stable.";
    }

    /** Si le LLM hallucine « pas de date » alors qu'on en a une, on regenere du PHP. */
    private static function postCorrectDeadlineAdvice(string $deadlineYmd, string $advice): string
    {
        if ($deadlineYmd === '') {
            return $advice;
        }
        if (preg_match('/\b(manque|absente|non\s*renseign|pas\s+indiqu|sans\s+date)\b/u', mb_strtolower($advice, 'UTF-8'))) {
            return self::buildDeadlineAdviceFromYmd($deadlineYmd, date('Y-m-d'));
        }
        return $advice;
    }

    private static function estimateComplexite(string $title, string $description): string
    {
        $norm = self::normalizeTextForMatch($title . ' ' . $description);

        foreach (['intelligence artificielle', 'machine learning', 'formation en ligne', 'api rest', 'e-commerce', 'plateforme'] as $p) {
            if (str_contains($norm, $p)) return 'eleve';
        }
        foreach (['erp', 'marketplace', 'application', 'dashboard', 'automatisation', 'ecommerce', 'refonte', 'scraper', 'scraping', 'zapier', 'n8n', 'ml'] as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $norm)) return 'eleve';
        }
        foreach (['landing page', 'charte graphique', 'business plan', 'identite visuelle', 'lettre de motivation'] as $p) {
            if (str_contains($norm, $p)) return 'moyen';
        }
        foreach (['montage', 'video', 'youtube', 'wordpress', 'seo', 'chatbot', 'script', 'integration', 'audit', 'podcast', 'motion', 'linkedin', 'redaction', 'article', 'publication', 'rapport', 'animation', 'cv'] as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $norm)) return 'moyen';
        }

        $tokens = preg_split('/\s+/u', trim($norm), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return count($tokens) < 40 ? 'faible' : 'moyen';
    }

    /**
     * Si « faible » par defaut (brief court), on retombe sur la complexite majoritaire
     * du CSV pour eviter un verdict trop genereux.
     *
     * @param array<int, array<string, string>> $dataset
     */
    private static function aiEstimateEffortLevel(string $title, string $description, array $dataset): string
    {
        $c = self::estimateComplexite($title, $description);
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

    // ---------------------------------------------------------------------
    // Dataset CSV
    // ---------------------------------------------------------------------

    /** @return array<int, array<string, string>> */
    private static function loadDataset(): array
    {
        if (self::$datasetCache !== null) {
            return self::$datasetCache;
        }
        $path = __DIR__ . '/../data/skillbridge_benchmark.csv';
        if (!is_readable($path)) {
            return self::$datasetCache = [];
        }
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return self::$datasetCache = [];
        }
        // PHP 8.4+ : $escape par défaut deprecated → on force la chaîne vide.
        $header = fgetcsv($fh, 0, ',', '"', '');
        if (!is_array($header)) {
            fclose($fh);
            return self::$datasetCache = [];
        }
        $header = array_map(static fn($h) => mb_strtolower(trim((string) $h)), $header);

        $rows = [];
        while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
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
        return self::$datasetCache = $rows;
    }

    /**
     * Bucket selon la complexite : inclut une marche adjacente (moyen -> faible+moyen)
     * pour ne pas exclure des services simples quand le texte declenche a tort « moyen ».
     *
     * @param array<int, array<string, string>> $dataset
     * @return array<int, array<string, string>>
     */
    private static function benchmarkBucketForComplexity(string $complexite, array $dataset): array
    {
        if ($dataset === []) {
            return [];
        }
        $c = trim($complexite);
        $allowed = match ($c) {
            'faible' => ['faible'],
            'moyen'  => ['faible', 'moyen'],
            'eleve'  => ['moyen', 'eleve'],
            default  => [$c],
        };
        return array_values(array_filter(
            $dataset,
            static fn(array $r): bool => in_array((string) ($r['complexite'] ?? ''), $allowed, true)
        ));
    }

    /**
     * Penalise un service du CSV qui contient un marqueur (ex. « animation ») absent
     * du brief client. Sinon « Animation logo » remontait pour un brief « logo simple ».
     */
    private static function serviceHayMismatchPenalty(string $hay, string $serviceLabel): int
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
            if (str_contains($svc, $m) && !str_contains($hay, $m)) {
                $penalty -= 45;
            }
        }
        foreach (preg_split('/[^\p{L}\p{N}]+/u', $svc, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
            if (mb_strlen((string) $w, 'UTF-8') >= 5 && !str_contains($hay, (string) $w)) {
                $penalty -= 18;
            }
        }
        return $penalty;
    }

    /**
     * Selectionne les lignes CSV qui matchent la demande (base du verdict prix/delai).
     * Le titre compte double dans le hay : sans description, le matching restait faible.
     *
     * @param array<int, array<string, string>> $dataset
     * @return array<int, array<string, string>>
     */
    private static function rowsForDemandBenchmark(
        string $title,
        string $description,
        string $complexite,
        float $budget,
        array $dataset
    ): array {
        if ($dataset === []) {
            return [];
        }
        $tNorm    = self::normalizeTextForMatch($title);
        $dNorm    = self::normalizeTextForMatch($description);
        $hay      = mb_strtolower(trim($tNorm . ' ' . $dNorm . ' ' . $tNorm), 'UTF-8');
        $hayEmpty = trim(mb_strtolower(trim($tNorm . ' ' . $dNorm), 'UTF-8')) === '';

        $bucket = self::benchmarkBucketForComplexity($complexite, $dataset);
        if ($bucket === []) {
            $bucket = $dataset;
        }
        $byService = [];
        foreach ($bucket as $r) {
            $svc = trim((string) ($r['service'] ?? ''));
            if ($svc !== '') {
                $byService[$svc][] = $r;
            }
        }

        $titleTokens = $hayEmpty ? [] : array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $hay, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            static fn(string $w): bool => mb_strlen($w, 'UTF-8') >= 2
        ));

        $rank = [];
        foreach ($byService as $svc => $rows) {
            $lex = 0;
            if (!$hayEmpty) {
                $lex      = self::scoreDatasetRowAgainstHay(['service' => $svc], $hay);
                $svcNorm  = self::normalizeTextForMatch($svc);
                foreach ($titleTokens as $tok) {
                    if (str_contains($svcNorm, $tok)) {
                        $lex += 15;
                    }
                }
                foreach (preg_split('/[^\p{L}\p{N}]+/u', $svcNorm, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $sw) {
                    if (mb_strlen((string) $sw, 'UTF-8') >= 5 && str_contains($hay, (string) $sw)) {
                        $lex += 10;
                    }
                }
                $lex += self::serviceHayMismatchPenalty($hay, $svc);
            }
            $sum = 0.0; $n = 0; $svcBudgets = [];
            foreach ($rows as $r) {
                $b = (float) ($r['budget_dt'] ?? 0);
                if ($b > 0) {
                    $svcBudgets[] = $b;
                    $sum         += abs($b - $budget);
                    ++$n;
                }
            }
            $dist     = $n > 0 ? $sum / $n : INF;
            $minS     = $svcBudgets !== [] ? min($svcBudgets) : 0.0;
            $maxS     = $svcBudgets !== [] ? max($svcBudgets) : 0.0;
            $inBand   = ($budget > 0.0 && $minS > 0.0 && $budget >= $minS && $budget <= $maxS) ? 1 : 0;
            $bandDist = 0.0;
            if ($inBand === 0 && $budget > 0.0 && $minS > 0.0) {
                $bandDist = $budget < $minS ? (float) ($minS - $budget) : ($budget > $maxS ? (float) ($budget - $maxS) : 0.0);
            }
            $rank[] = ['svc' => $svc, 'lex' => $lex, 'dist' => $dist, 'in_band' => $inBand, 'band_dist' => $bandDist];
        }
        if ($rank === []) {
            return [];
        }

        usort($rank, static function (array $a, array $b): int {
            if ($a['in_band'] !== $b['in_band']) return $b['in_band'] <=> $a['in_band'];
            if ($a['in_band'] === 1) {
                if ($a['lex'] !== $b['lex']) return $b['lex'] <=> $a['lex'];
                return $a['dist'] <=> $b['dist'];
            }
            // Hors fourchette : le texte prime, sinon un petit prix matchait au mauvais service
            // (ex. 9 DT plus proche de 30 que de 80 -> « Correction texte » au lieu de « Logo simple »).
            if ($a['lex'] !== $b['lex']) return $b['lex'] <=> $a['lex'];
            if ($a['band_dist'] !== $b['band_dist']) return $a['band_dist'] <=> $b['band_dist'];
            return $a['dist'] <=> $b['dist'];
        });

        $bestSvc = (string) ($rank[0]['svc'] ?? '');
        return $bestSvc !== '' ? $byService[$bestSvc] : [];
    }

    // ---------------------------------------------------------------------
    // Verdicts prix / delai
    // ---------------------------------------------------------------------

    /**
     * @param array<int, array<string, string>> $rows
     * @return array{verdict_budget: string, verdict_deadline: string, conseil_budget: string, conseil_deadline: string}
     */
    private static function evaluateFromDataset(float $budget, int $deadlineDays, array $rows): array
    {
        if ($rows === []) {
            return [
                'verdict_budget'   => 'inconnu',
                'verdict_deadline' => 'inconnu',
                'conseil_budget'   => 'Dataset introuvable. Verifiez le fichier : data/skillbridge_benchmark.csv',
                'conseil_deadline' => 'Dataset introuvable. Impossible de comparer le delai.',
            ];
        }

        $budgets = array_values(array_filter(
            array_map(static fn($r) => (float) ($r['budget_dt'] ?? 0), $rows),
            static fn($x) => $x > 0
        ));
        $minB = $budgets !== [] ? min($budgets) : 0;
        $maxB = $budgets !== [] ? max($budgets) : 0;

        $refSvc   = trim((string) ($rows[0]['service'] ?? ''));
        $refLabel = $refSvc !== '' ? " Reference dataset : « {$refSvc} »." : '';
        $bDisp    = abs($budget - round($budget)) < 1e-6
            ? (string) (int) round($budget)
            : rtrim(rtrim(number_format($budget, 2, '.', ''), '0'), '.');

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
        $minJ = $joursEstimes !== [] ? min($joursEstimes) : 1;

        if ($deadlineDays <= 0) {
            $vd = 'impossible';
            $cd = 'La date limite est deja passee ou invalide. Choisissez une date future.';
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

        return ['verdict_budget' => $vb, 'verdict_deadline' => $vd, 'conseil_budget' => $cb, 'conseil_deadline' => $cd];
    }

    /** @return array{verdict_budget: string, verdict_deadline: string, conseil_budget: string, conseil_deadline: string} */
    private static function evaluateWithDataset(string $title, string $description, string $price, string $deadline): array
    {
        $dataset = self::loadDataset();
        if ($dataset === []) {
            return [
                'verdict_budget'   => 'correct',
                'verdict_deadline' => 'raisonnable',
                'conseil_budget'   => 'Dataset introuvable ou vide : data/skillbridge_benchmark.csv',
                'conseil_deadline' => 'Dataset introuvable : impossible de comparer le delai.',
            ];
        }

        $deadlineYmd  = self::normalizeClientDeadline($deadline);
        $today        = new DateTimeImmutable('today');
        $deadlineDays = 0;
        if ($deadlineYmd !== '') {
            $dEnd = DateTimeImmutable::createFromFormat('!Y-m-d', $deadlineYmd);
            if ($dEnd instanceof DateTimeImmutable) {
                $deadlineDays = (int) floor(($dEnd->getTimestamp() - $today->getTimestamp()) / 86400);
            }
        }

        $budget = (float) str_replace(',', '.', trim($price));
        if (!is_finite($budget)) {
            $budget = 0.0;
        }

        $complexite = self::aiEstimateEffortLevel($title, $description, $dataset);
        $rows       = self::rowsForDemandBenchmark($title, $description, $complexite, $budget, $dataset);
        return self::evaluateFromDataset($budget, $deadlineDays, $rows);
    }

    private static function formatPriceAdviceLine(array $ev): string
    {
        return 'Verdict budget : ' . ($ev['verdict_budget'] ?? 'correct') . '. ' . trim((string) ($ev['conseil_budget'] ?? ''));
    }

    private static function formatDeadlineAdviceLine(array $ev): string
    {
        return 'Verdict delai : ' . ($ev['verdict_deadline'] ?? 'raisonnable') . '. ' . trim((string) ($ev['conseil_deadline'] ?? ''));
    }

    // ---------------------------------------------------------------------
    // LLM : resume + suggestions
    // ---------------------------------------------------------------------

    private static function fallbackSummary(string $title, string $description): string
    {
        $t = trim($title);
        if ($t !== '') {
            return 'Besoin resume autour de : ' . mb_substr($t, 0, 120) . '.';
        }
        return 'Precisez le titre et la description pour un resume plus utile.';
    }

    /** @return array<int, string> */
    private static function fallbackSuggestions(): array
    {
        return [
            'Precisez les livrables attendus (formats, nombre de versions).',
            'Indiquez le public cible et le ton souhaite.',
            'Ajoutez des contraintes techniques ou de branding connues.',
        ];
    }

    /** @return array{summary: string, brief_suggestions: array<int, string>} */
    private static function askSummaryOnly(string $title, string $description): array
    {
        $shortDesc = mb_substr(trim($description), 0, 200);
        // Le summary ne doit jamais mentionner de chiffre : prix/delai sont calcules
        // deterministe (dataset) et le LLM tend a halluciner sinon.
        $system = 'Tu es un conseiller freelance. Reponds UNIQUEMENT en JSON valide, sans markdown. '
            . 'Ne mentionne jamais de prix, de budget, de delai ou de duree dans le summary.';
        $user = "Titre: {$title}\nDescription: {$shortDesc}\n"
            . 'Ecris un summary de 1-2 phrases qui decrit UNIQUEMENT le besoin metier. '
            . 'INTERDIT : ne mentionne aucun chiffre, aucun prix, aucun delai, aucune duree. '
            . "Donne 3 conseils concrets pour ameliorer le brief.\n"
            . 'Retourne: {"summary":"...","brief_suggestions":["...","...","..."]}';

        $raw  = self::callOllama($system, $user, 45, 384, false);
        $json = $raw !== '' ? self::aiExtractJson($raw) : null;

        if (is_array($json)) {
            $sug = array_slice(
                array_values(array_filter(array_map('strval', $json['brief_suggestions'] ?? []))),
                0,
                3
            );
            return [
                'summary'           => trim((string) ($json['summary'] ?? '')),
                'brief_suggestions' => $sug !== [] ? $sug : self::fallbackSuggestions(),
            ];
        }
        return ['summary' => self::fallbackSummary($title, $description), 'brief_suggestions' => self::fallbackSuggestions()];
    }

    // ---------------------------------------------------------------------
    // RAG : matching freelance ↔ demande
    // ---------------------------------------------------------------------

    private static function profileStem(string $competences, string $bio): string
    {
        return mb_strtolower(self::normalizeTextForMatch(trim($competences) . ' ' . trim($bio)), 'UTF-8');
    }

    /** Score lexical d'une ligne CSV contre un texte ; libelle complet > tokens isoles. */
    private static function scoreDatasetRowAgainstHay(array $row, string $haystack): int
    {
        $score   = 0;
        $service = mb_strtolower((string) ($row['service'] ?? ''), 'UTF-8');
        if ($service !== '' && str_contains($haystack, $service)) {
            $score += 22;
        }
        foreach (preg_split('/[^\p{L}\p{N}]+/u', $service, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
            if (mb_strlen((string) $w) >= 3 && str_contains($haystack, (string) $w)) {
                $score += 4;
            }
        }
        return $score;
    }

    /**
     * Pont heuristique : profil dev + demande type site/app/WordPress.
     * Evite d'exiger le mot exact « PHP » dans le brief client.
     */
    private static function devProfileDemandeBridge(string $profileStem, string $dHay): int
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
            if (str_contains($profileStem, $m)) { $hasDev = true; break; }
        }
        if (!$hasDev) {
            return 0;
        }
        foreach ($demandWebSignals as $m) {
            if (str_contains($dHay, $m)) {
                return 14;
            }
        }
        return 0;
    }

    /** Tokens du profil retrouves dans la demande, + bonus pont dev-web. */
    private static function profileDemandeTokenOverlap(string $competences, string $bio, array $demande): int
    {
        $profileStem = self::profileStem($competences, $bio);
        if ($profileStem === '') {
            return 0;
        }
        $dHay = mb_strtolower(self::normalizeTextForMatch(
            trim((string) ($demande['title'] ?? '')) . ' ' . trim((string) ($demande['description'] ?? ''))
        ), 'UTF-8');
        if ($dHay === '') {
            return 0;
        }
        // Sigles 3 lettres : whitelist tech sinon trop de faux positifs (« CV », « le », etc.).
        $shortTech = ['php', 'sql', 'css', 'seo', 'ux', 'ui', 'api', 'web', 'git', 'aws', 'gcp', 'vue', 'rss', 'pdf', 'xml', 'svg', 'cdn', 'sdk', 'ide', 'cli', 'nlp', 'gpu', 'cpu'];
        $score = 0;
        foreach (preg_split('/[^\p{L}\p{N}]+/u', $profileStem, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
            $w   = (string) $w;
            $len = mb_strlen($w, 'UTF-8');
            if ($len >= 4 && str_contains($dHay, $w)) {
                $score += 10;
            } elseif ($len === 3 && in_array($w, $shortTech, true) && str_contains($dHay, $w)) {
                $score += 8;
            }
        }
        return $score + self::devProfileDemandeBridge($profileStem, $dHay);
    }

    private static function demandeTouchesServiceRow(array $demande, array $datasetRow): bool
    {
        $blob = mb_strtolower(((string) ($demande['title'] ?? '')) . ' ' . ((string) ($demande['description'] ?? '')), 'UTF-8');
        $svc  = mb_strtolower(trim((string) ($datasetRow['service'] ?? '')), 'UTF-8');
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
     * Score combine d'une demande pour un profil + raison textuelle.
     *
     * Combine 3 signaux :
     *  1. token overlap profil ↔ demande (avec pont dev-web)
     *  2. ancrage dataset : la demande matche-t-elle une ligne du benchmark ?
     *  3. profil contre cette ligne (le service correspond aux competences)
     *
     * @param array<int, array<string, string>> $dataset
     * @return array{score: int, reason: string}
     */
    private static function scoreDemandeForProfile(string $competences, string $bio, array $demande, array $dataset): array
    {
        $profileStem = self::profileStem($competences, $bio);
        if ($profileStem === '') {
            return ['score' => 0, 'reason' => ''];
        }

        $demHay = mb_strtolower(self::normalizeTextForMatch(
            ((string) ($demande['title'] ?? '')) . ' ' . ((string) ($demande['description'] ?? ''))
        ), 'UTF-8');

        $overlap = self::profileDemandeTokenOverlap($competences, $bio, $demande);

        $bestRow   = null;
        $bestRowSc = 0;
        $hayCombined = $profileStem . ' ' . $demHay;
        foreach ($dataset as $r) {
            $sc = self::scoreDatasetRowAgainstHay($r, $hayCombined);
            if ($sc > $bestRowSc) {
                $bestRowSc = $sc;
                $bestRow   = $r;
            }
        }

        $rowTouchesDemande = $bestRow !== null && self::demandeTouchesServiceRow($demande, $bestRow);
        $profVsRow         = $bestRow !== null ? self::scoreDatasetRowAgainstHay($bestRow, $profileStem) : 0;

        $raw = $overlap * 3 + ($rowTouchesDemande ? $bestRowSc * 2 : 0) + $profVsRow;

        $complexiteDemande = self::estimateComplexite(
            (string) ($demande['title'] ?? ''),
            (string) ($demande['description'] ?? '')
        );
        if ($bestRow !== null && (string) ($bestRow['complexite'] ?? '') === $complexiteDemande) {
            $raw += 8;
        }

        // Plafonne a 100 pour exposer un score lisible cote UI.
        $score = (int) max(0, min(100, $raw));

        $parts = [];
        if ($overlap >= 10) {
            $parts[] = 'Mots cles du profil retrouves dans le brief';
        }
        if ($bestRow !== null && $rowTouchesDemande) {
            $svcLabel = trim((string) ($bestRow['service'] ?? ''));
            if ($svcLabel !== '') {
                $parts[] = "service benchmark « {$svcLabel} »";
            }
        }
        if ($bestRow !== null && (string) ($bestRow['complexite'] ?? '') === $complexiteDemande && $complexiteDemande !== '') {
            $parts[] = "complexite {$complexiteDemande} alignee";
        }

        $reason = $parts !== []
            ? ucfirst(implode(' + ', $parts)) . '.'
            : 'Projet aligne avec le referentiel interne.';

        return ['score' => $score, 'reason' => $reason];
    }
}
