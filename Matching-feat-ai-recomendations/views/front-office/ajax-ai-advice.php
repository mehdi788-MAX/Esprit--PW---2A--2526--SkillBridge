<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/AiRecommendationService.php';

header('Content-Type: application/json');

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? '';
$deadline = $_POST['deadline'] ?? '';

if (empty($title) && empty($description)) {
    echo json_encode([
        'analysis' => '',
        'price_message' => '',
        'deadline_message' => '',
        'brief_suggestions' => []
    ]);
    exit;
}

$aiAnalysis = analyzeDemandWithAi($title, $description, $price, $deadline);

$advice = [
    'analysis' => $aiAnalysis['summary'] ?? '',
    'price_message' => $aiAnalysis['price_advice'] ?? '',
    'deadline_message' => $aiAnalysis['deadline_advice'] ?? '',
    'brief_suggestions' => $aiAnalysis['brief_suggestions'] ?? []
];

echo json_encode($advice);
exit;
