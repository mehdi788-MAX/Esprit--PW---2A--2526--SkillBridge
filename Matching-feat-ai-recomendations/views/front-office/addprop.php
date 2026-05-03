<?php
/**
 * Enregistre une proposition liee a une demande.
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/proposition_validation.php';
ensure_session_started();
require_freelancer();

/**
 * @param array{demande_id:int,freelancer_name:string,message:string,price:float|int|string} $input
 */
function addprop(PDO $pdo, array $input): void
{
    $validationErrors = validate_proposition_form_input($input, true);
    if ($validationErrors !== []) {
        throw new InvalidArgumentException(implode(' ', $validationErrors));
    }

    $demandeId = (int) ($input['demande_id'] ?? 0);

    $check = $pdo->prepare('SELECT id FROM demandes WHERE id = ?');
    $check->execute([$demandeId]);
    if (!$check->fetchColumn()) {
        throw new InvalidArgumentException("Cette demande n'existe pas.");
    }

    $freelancerName = trim((string) ($input['freelancer_name'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));
    $price = $input['price'] ?? '';

    ensure_propositions_user_id_column($pdo);

    $params = [
        ':demande_id' => $demandeId,
        ':freelancer_name' => $freelancerName,
        ':message' => $message,
        ':price' => $price,
        ':user_id' => current_user_id(),
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO propositions (demande_id, freelancer_name, message, price, user_id, created_at)
         VALUES (:demande_id, :freelancer_name, :message, :price, :user_id, NOW())'
    );
    $stmt->execute($params);
}

/**
 * @param array<string, mixed> $input
 */
function build_addprop_redirect(array $input, string $extra = ''): string
{
    $query = array_filter([
        'demande_id' => isset($input['demande_id']) ? (int) $input['demande_id'] : '',
        'freelancer_name' => trim((string) ($input['freelancer_name'] ?? '')),
        'price' => trim((string) ($input['price'] ?? '')),
        'message' => trim((string) ($input['message'] ?? '')),
    ], static fn($value) => $value !== '');

    if ($extra !== '') {
        parse_str($extra, $extraQuery);
        $query = array_merge($query, $extraQuery);
    }

    $url = front_url('addprop-form.php');
    $queryString = http_build_query($query);

    return $queryString === '' ? $url : $url . '?' . $queryString;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . front_url('addprop-form.php'));
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
    header('Location: ' . build_addprop_redirect($_POST, 'ok=1'));
    exit;
} catch (InvalidArgumentException $e) {
    header('Location: ' . build_addprop_redirect($_POST, 'err=' . rawurlencode($e->getMessage())));
    exit;
} catch (PDOException $e) {
    header('Location: ' . build_addprop_redirect($_POST, 'err=' . rawurlencode(db_error_message($e))));
    exit;
}
