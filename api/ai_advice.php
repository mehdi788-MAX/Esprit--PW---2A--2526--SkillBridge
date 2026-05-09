<?php
// =====================================================
// SkillBridge — AI advice (live form panel)
// -----------------------------------------------------
// Endpoint AJAX appelé par add_demande.php / edit_demande.php
// pendant que le client rédige son brief.
//
//   POST  api/ai_advice.php
//     title       (string)
//     description (string)
//     price       (string|number, optionnel)
//     deadline    (YYYY-MM-DD ou DD/MM/YYYY, optionnel)
//
// Réponse JSON :
//   {
//     "available":      bool,            // Ollama joignable + modèle chargé
//     "summary":        string,          // synthèse IA du besoin
//     "price_advice":   string,          // verdict prix vs benchmark dataset
//     "deadline_advice":string,          // verdict délai vs complexité
//     "suggestions":    string[]         // 3 suggestions courtes
//   }
//
// Le endpoint ne dépend pas d'Ollama : si Qwen est down, le service
// retombe sur des verdicts dataset + summary fallback. La clé
// `available` permet à l'UI d'afficher un badge « IA hors ligne ».
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/AiRecommendationService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// L'IA ne renvoie aucune donnée privée. On exige tout de même une session
// active pour éviter d'utiliser ce endpoint comme proxy LLM gratuit.
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'auth_required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$title       = trim((string)($_POST['title']       ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$price       = trim((string)($_POST['price']       ?? ''));
$deadline    = trim((string)($_POST['deadline']    ?? ''));

// Court-circuite : pas la peine de solliciter le LLM avec un brief vide.
if ($title === '' && $description === '') {
    echo json_encode([
        'available'        => AiRecommendationService::isAvailable(),
        'summary'          => '',
        'price_advice'     => '',
        'deadline_advice'  => '',
        'suggestions'      => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = AiRecommendationService::analyzeDemand($title, $description, $price, $deadline);

echo json_encode([
    'available'       => AiRecommendationService::isAvailable(),
    'summary'         => $result['summary']         ?? '',
    'price_advice'    => $result['price_advice']    ?? '',
    'deadline_advice' => $result['deadline_advice'] ?? '',
    'suggestions'     => $result['brief_suggestions'] ?? [],
], JSON_UNESCAPED_UNICODE);
