<?php
/**
 * Enregistre une proposition liee a une demande.
 */
require_once __DIR__ . '/../../config.php';

/**
 * @param array{demande_id:int,freelancer_name:string,message:string,price:float|int|string} $input
 */
function addprop(PDO $pdo, array $input): void
{
    $demandeId = (int) ($input['demande_id'] ?? 0);
    if ($demandeId < 1) {
        throw new InvalidArgumentException('Identifiant de demande invalide.');
    }

    $check = $pdo->prepare('SELECT id FROM demandes WHERE id = ?');
    $check->execute([$demandeId]);
    if (!$check->fetchColumn()) {
        throw new InvalidArgumentException("Cette demande n'existe pas.");
    }

    $freelancerName = trim((string) ($input['freelancer_name'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));
    $price = $input['price'] ?? '';

    if ($freelancerName === '' || $message === '' || $price === '' || !is_numeric($price)) {
        throw new InvalidArgumentException('Veuillez remplir tous les champs correctement.');
    }

    $params = [
        ':demande_id' => $demandeId,
        ':freelancer_name' => $freelancerName,
        ':message' => $message,
        ':price' => $price,
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO propositions (demande_id, freelancer_name, message, price, created_at)
         VALUES (:demande_id, :freelancer_name, :message, :price, NOW())'
    );
    $stmt->execute($params);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addprop.html');
    exit;
}

try {
    $pdo = db_connect();
    addprop($pdo, [
        'demande_id' => (int) ($_POST['demande_id'] ?? 0),
        'freelancer_name' => $_POST['freelancer_name'] ?? '',
        'message' => $_POST['message'] ?? '',
        'price' => $_POST['price'] ?? '',
    ]);
    header('Location: addprop.html?ok=1');
    exit;
} catch (InvalidArgumentException $e) {
    header('Location: addprop.html?err=' . rawurlencode($e->getMessage()));
    exit;
} catch (PDOException $e) {
    header('Location: addprop.html?err=' . rawurlencode(db_error_message($e)));
    exit;
}
