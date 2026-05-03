<?php
/**
 * API JSON du module Chat — alimente le polling temps réel
 * et les actions AJAX (envoi, devis, typing, notifications).
 *
 * Toutes les réponses sont au format JSON, validation côté serveur.
 */

declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Permet aux longues requêtes de ne pas verrouiller la session
session_write_close();

require_once __DIR__ . '/../controller/ChatController.php';

function respond(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $msg, int $status = 400, array $extra = []): void {
    respond(array_merge(['success' => false, 'error' => $msg], $extra), $status);
}

/**
 * Identifie l'utilisateur courant. Utilise la session si dispo, sinon
 * fallback `as_user` (pour la démo où login n'est pas encore branché
 * sur les pages chat).
 */
function currentUserId(): int {
    if (!empty($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (!empty($_GET['as_user'])) return (int)$_GET['as_user'];
    if (!empty($_POST['as_user'])) return (int)$_POST['as_user'];
    return 0;
}

function actorName(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare("SELECT prenom, nom FROM utilisateurs WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return '';
    return trim((string)$row['prenom'] . ' ' . (string)$row['nom']);
}

function otherParticipant(PDO $pdo, int $convId, int $userId): int {
    $stmt = $pdo->prepare("SELECT user1_id, user2_id FROM conversations WHERE id_conversation = :id");
    $stmt->execute([':id' => $convId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return 0;
    return ((int)$row['user1_id'] === $userId) ? (int)$row['user2_id'] : (int)$row['user1_id'];
}

function ensureMember(PDO $pdo, int $convId, int $userId): array {
    $stmt = $pdo->prepare("SELECT user1_id, user2_id FROM conversations WHERE id_conversation = :id");
    $stmt->execute([':id' => $convId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) fail("Conversation introuvable.", 404);
    if ((int)$row['user1_id'] !== $userId && (int)$row['user2_id'] !== $userId) {
        fail("Accès refusé à cette conversation.", 403);
    }
    return $row;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$controller = new ChatController();
global $pdo;
$me = currentUserId();
if ($me <= 0) fail("Utilisateur non identifié.", 401);

switch ($action) {

    // -------------------------------------------------
    // POLL : messages + notifications + typing + unread
    // -------------------------------------------------
    case 'poll': {
        $convId      = (int)($_GET['conv'] ?? 0);
        $sinceNotif  = (int)($_GET['since_notif'] ?? 0);

        $messages   = [];
        $typing     = [];
        $reactions  = (object)[]; // { msgId: [{user_id, emoji}, ...] }
        $seenForMe = 0; // pour la mise à jour live des coches "vu"
        if ($convId > 0) {
            ensureMember($pdo, $convId, $me);
            // Snapshot complet de la conversation : permet au client de
            // détecter en live les nouveaux messages, les éditions
            // et les suppressions sans recharger la page.
            $msgModel = new Message($pdo);
            $msgModel->id_conversation = $convId;
            $rows = $msgModel->readByConversation()->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $r['is_mine'] = ((int)$r['sender_id'] === $me);
                $r['moderation'] = Moderator::analyze((string)$r['contenu']);
                $messages[] = $r;
            }
            // Plus grand id_message envoyé par l'utilisateur courant et marqué vu
            // → permet au sender de voir la coche "vu" en temps réel sans recharger.
            $seenStmt = $pdo->prepare("SELECT MAX(id_message) FROM messages
                                        WHERE id_conversation = :cid
                                          AND sender_id = :uid AND is_seen = 1");
            $seenStmt->execute([':cid' => $convId, ':uid' => $me]);
            $seenForMe = (int)($seenStmt->fetchColumn() ?: 0);
            // Typing indicators (autres utilisateurs, fraîcheur < 5s)
            $stmt = $pdo->prepare("SELECT user_id FROM typing_indicators
                                    WHERE conversation_id = :cid AND user_id != :uid
                                      AND updated_at >= :cutoff");
            $stmt->execute([
                ':cid'    => $convId,
                ':uid'    => $me,
                ':cutoff' => date('Y-m-d H:i:s', time() - 5),
            ]);
            $typing = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            // Réactions (toutes les messages de la conversation, format léger)
            $rxStmt = $pdo->prepare("SELECT mr.message_id, mr.user_id, mr.emoji
                                       FROM message_reactions mr
                                       JOIN messages m ON mr.message_id = m.id_message
                                      WHERE m.id_conversation = :cid");
            $rxStmt->execute([':cid' => $convId]);
            $rxMap = [];
            foreach ($rxStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $mid = (int)$row['message_id'];
                if (!isset($rxMap[$mid])) $rxMap[$mid] = [];
                $rxMap[$mid][] = ['user_id' => (int)$row['user_id'], 'emoji' => $row['emoji']];
            }
            $reactions = $rxMap ?: (object)[];
        }

        $notifModel = new Notification($pdo);
        $newNotifs  = $notifModel->readForUser($me, $sinceNotif, 30);
        $unread     = $notifModel->countUnread($me);
        $recent     = $notifModel->recentForUser($me, 10);

        $lastNotifId = !empty($newNotifs) ? max(array_map(fn($n) => (int)$n['id'], $newNotifs)) : $sinceNotif;

        respond([
            'success'        => true,
            'server_time'    => date('Y-m-d H:i:s'),
            'messages'       => $messages,
            'typing_users'   => $typing,
            'new_notifs'     => $newNotifs,
            'recent_notifs'  => $recent,
            'unread_total'   => $unread,
            'last_notif_id'  => $lastNotifId,
            'seen_for_me'    => $seenForMe,
            'reactions'      => $reactions,
        ]);
    }

    // -------------------------------------------------
    // SEND : envoyer un message texte
    // -------------------------------------------------
    case 'send': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $convId = (int)($_POST['conv'] ?? 0);
        $contenu = trim((string)($_POST['contenu'] ?? ''));
        if ($convId <= 0)  fail("Conversation invalide.");
        ensureMember($pdo, $convId, $me);

        $result = $controller->sendMessage($convId, $me, $contenu, 'text');
        if (!$result['success']) {
            respond(['success' => false, 'errors' => $result['errors'] ?? ['Erreur inconnue.']], 422);
        }
        // Nettoyer l'indicateur de saisie
        $del = $pdo->prepare("DELETE FROM typing_indicators WHERE conversation_id = :cid AND user_id = :uid");
        $del->execute([':cid' => $convId, ':uid' => $me]);

        respond([
            'success'    => true,
            'id'         => $result['id'],
            'moderation' => $result['moderation'] ?? null,
        ]);
    }

    // -------------------------------------------------
    // UPLOAD : envoyer une image ou un fichier
    // -------------------------------------------------
    case 'upload': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $convId = (int)($_POST['conv'] ?? 0);
        if ($convId <= 0) fail("Conversation invalide.");
        ensureMember($pdo, $convId, $me);

        if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            fail("Aucun fichier reçu.");
        }
        $f = $_FILES['file'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            fail("Erreur d'upload (code " . (int)$f['error'] . ").");
        }

        // --- Validation taille (max 10 Mo) ---
        $maxBytes = 10 * 1024 * 1024;
        if ($f['size'] > $maxBytes) fail("Fichier trop volumineux (max 10 Mo).", 422);
        if ($f['size'] <= 0)        fail("Fichier vide.", 422);

        // --- Validation extension + MIME ---
        $name = (string)$f['name'];
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $imgExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExts = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip'];
        $allowed  = array_merge($imgExts, $fileExts);
        if (!in_array($ext, $allowed, true)) {
            fail("Extension non autorisée. Autorisées : " . implode(', ', $allowed), 422);
        }

        $mime = function_exists('mime_content_type')
            ? @mime_content_type($f['tmp_name'])
            : (string)($f['type'] ?? '');
        $blockedMime = ['application/x-php', 'text/x-php', 'application/x-httpd-php',
                        'image/svg+xml']; // SVG bloqué (XSS via JS embarqué)
        if (in_array($mime, $blockedMime, true)) {
            fail("Type MIME refusé pour des raisons de sécurité.", 422);
        }

        // --- Stockage : nom aléatoire, dossier par conversation ---
        $isImage = in_array($ext, $imgExts, true);
        $kind    = $isImage ? 'image' : 'file';

        $rootDir = realpath(__DIR__ . '/..');
        $convDir = $rootDir . '/uploads/chat/' . $convId;
        if (!is_dir($convDir)) {
            if (!@mkdir($convDir, 0775, true) && !is_dir($convDir)) {
                fail("Impossible de créer le dossier de stockage.", 500);
            }
        }
        $randomName = bin2hex(random_bytes(8)) . '.' . $ext;
        $absPath    = $convDir . '/' . $randomName;
        if (!@move_uploaded_file($f['tmp_name'], $absPath)) {
            fail("Échec de l'enregistrement du fichier.", 500);
        }
        @chmod($absPath, 0644);

        // URL relative servie par Apache / le serveur PHP intégré
        $relUrl = 'uploads/chat/' . $convId . '/' . $randomName;

        $meta = [
            'kind' => $kind,
            'url'  => $relUrl,
            'name' => mb_substr(preg_replace('/[\r\n\t]+/', ' ', $name), 0, 120, 'UTF-8'),
            'size' => (int)$f['size'],
            'mime' => is_string($mime) ? $mime : '',
        ];
        if ($isImage) {
            $info = @getimagesize($absPath);
            if ($info && isset($info[0], $info[1])) {
                $meta['width']  = (int)$info[0];
                $meta['height'] = (int)$info[1];
            }
        }

        $contenu = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $result = $controller->sendMessage($convId, $me, $contenu, $kind);
        if (!$result['success']) {
            @unlink($absPath);
            respond(['success' => false, 'errors' => $result['errors'] ?? ['Échec.']], 422);
        }
        respond(['success' => true, 'id' => $result['id'], 'message' => $meta]);
    }

    // -------------------------------------------------
    // TYPING : signal de saisie en cours (UPSERT)
    // -------------------------------------------------
    case 'typing': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $convId = (int)($_POST['conv'] ?? 0);
        if ($convId <= 0) fail("Conversation invalide.");
        ensureMember($pdo, $convId, $me);

        // REPLACE INTO fonctionne sur SQLite et MySQL
        $stmt = $pdo->prepare("REPLACE INTO typing_indicators (conversation_id, user_id, updated_at)
                                VALUES (:cid, :uid, :ts)");
        $stmt->execute([
            ':cid' => $convId,
            ':uid' => $me,
            ':ts'  => date('Y-m-d H:i:s'),
        ]);
        respond(['success' => true]);
    }

    // -------------------------------------------------
    // MARK-READ : marquer notifications comme lues
    // -------------------------------------------------
    case 'mark-read': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $convId = (int)($_POST['conv'] ?? 0);
        $idsRaw = $_POST['ids'] ?? null;
        $n = new Notification($pdo);
        if ($convId > 0) {
            $n->markReadForConversation($me, $convId);
        } elseif (is_array($idsRaw)) {
            $n->markRead($me, $idsRaw);
        } else {
            $n->markRead($me, null);
        }
        respond(['success' => true, 'unread_total' => $n->countUnread($me)]);
    }

    // -------------------------------------------------
    // REACT : ajouter / changer / retirer une réaction
    // -------------------------------------------------
    case 'react': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $msgId = (int)($_POST['msg_id'] ?? 0);
        $emoji = trim((string)($_POST['emoji'] ?? ''));
        if ($msgId <= 0) fail("Message invalide.");
        if ($emoji === '' || mb_strlen($emoji, 'UTF-8') > 8) fail("Emoji invalide.");

        // Vérifier que le message existe et que l'utilisateur est membre
        $stmt = $pdo->prepare("SELECT id_conversation FROM messages WHERE id_message = :id");
        $stmt->execute([':id' => $msgId]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$msg) fail("Message introuvable.", 404);
        ensureMember($pdo, (int)$msg['id_conversation'], $me);

        // Toggle : même emoji déjà posée → retirer ; sinon REPLACE
        $cur = $pdo->prepare("SELECT emoji FROM message_reactions
                                WHERE message_id = :mid AND user_id = :uid");
        $cur->execute([':mid' => $msgId, ':uid' => $me]);
        $existing = $cur->fetchColumn();

        if ($existing === $emoji) {
            $del = $pdo->prepare("DELETE FROM message_reactions
                                    WHERE message_id = :mid AND user_id = :uid");
            $del->execute([':mid' => $msgId, ':uid' => $me]);
            respond(['success' => true, 'removed' => true]);
        }

        $rep = $pdo->prepare("REPLACE INTO message_reactions (message_id, user_id, emoji, created_at)
                                VALUES (:mid, :uid, :emoji, :ts)");
        $rep->execute([
            ':mid'   => $msgId,
            ':uid'   => $me,
            ':emoji' => $emoji,
            ':ts'    => date('Y-m-d H:i:s'),
        ]);

        // Notifier l'auteur du message (sauf si c'est moi)
        $authStmt = $pdo->prepare("SELECT sender_id, contenu FROM messages WHERE id_message = :id");
        $authStmt->execute([':id' => $msgId]);
        $auth = $authStmt->fetch(PDO::FETCH_ASSOC);
        if ($auth && (int)$auth['sender_id'] !== $me) {
            $n = new Notification($pdo);
            $n->user_id = (int)$auth['sender_id'];
            $n->type = 'message_reacted';
            $n->conversation_id = (int)$msg['id_conversation'];
            $n->message_id = $msgId;
            $n->payload_json = json_encode([
                'actor_id'   => $me,
                'actor_name' => actorName($pdo, $me),
                'emoji'      => $emoji,
                'preview'    => mb_substr(strip_tags((string)$auth['contenu']), 0, 60, 'UTF-8'),
            ], JSON_UNESCAPED_UNICODE);
            $n->create();
        }

        respond(['success' => true, 'emoji' => $emoji]);
    }

    // -------------------------------------------------
    // EDIT / DELETE-MSG : actions existantes en JSON
    // -------------------------------------------------
    case 'edit-msg': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $msgId = (int)($_POST['msg_id'] ?? 0);
        $contenu = trim((string)($_POST['contenu'] ?? ''));
        if ($msgId <= 0) fail("Message invalide.");
        // Vérifier propriétaire
        $stmt = $pdo->prepare("SELECT sender_id, type, id_conversation FROM messages WHERE id_message = :id");
        $stmt->execute([':id' => $msgId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) fail("Message introuvable.", 404);
        if ((int)$row['sender_id'] !== $me) fail("Modification refusée.", 403);
        if ($row['type'] !== 'text') fail("Seuls les messages texte peuvent être modifiés.", 422);

        $result = $controller->updateMessage($msgId, $contenu, 'text');
        if (!$result['success']) respond(['success' => false, 'errors' => $result['errors']], 422);

        // Notifier l'autre participant
        $other = otherParticipant($pdo, (int)$row['id_conversation'], $me);
        if ($other > 0) {
            $n = new Notification($pdo);
            $n->user_id = $other;
            $n->type = 'message_edited';
            $n->conversation_id = (int)$row['id_conversation'];
            $n->message_id = $msgId;
            $n->payload_json = json_encode([
                'actor_id'   => $me,
                'actor_name' => actorName($pdo, $me),
                'preview'    => mb_substr(strip_tags($contenu), 0, 80, 'UTF-8'),
            ], JSON_UNESCAPED_UNICODE);
            $n->create();
        }

        respond(['success' => true]);
    }

    case 'delete-msg': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $msgId = (int)($_POST['msg_id'] ?? 0);
        if ($msgId <= 0) fail("Message invalide.");
        $stmt = $pdo->prepare("SELECT sender_id, id_conversation, contenu FROM messages WHERE id_message = :id");
        $stmt->execute([':id' => $msgId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) fail("Message introuvable.", 404);
        if ((int)$row['sender_id'] !== $me) fail("Suppression refusée.", 403);

        $convId = (int)$row['id_conversation'];
        $other = otherParticipant($pdo, $convId, $me);

        $result = $controller->deleteMessage($msgId);
        if (!$result['success']) respond(['success' => false, 'errors' => $result['errors']], 422);

        // Notifier l'autre participant (avant la cascade qui pourrait supprimer la notif)
        if ($other > 0) {
            $n = new Notification($pdo);
            $n->user_id = $other;
            $n->type = 'message_deleted';
            $n->conversation_id = $convId;
            $n->message_id = null; // le message n'existe plus
            $n->payload_json = json_encode([
                'actor_id'   => $me,
                'actor_name' => actorName($pdo, $me),
                'preview'    => mb_substr(strip_tags((string)$row['contenu']), 0, 80, 'UTF-8'),
            ], JSON_UNESCAPED_UNICODE);
            $n->create();
        }

        respond(['success' => true]);
    }

    // -------------------------------------------------
    // SEEN : marquer les messages d'une conversation comme vus
    // -------------------------------------------------
    case 'seen': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail("Méthode invalide.", 405);
        $convId = (int)($_POST['conv'] ?? 0);
        if ($convId <= 0) fail("Conversation invalide.");
        ensureMember($pdo, $convId, $me);
        $controller->markMessagesAsSeen($convId, $me);
        // On marque aussi les notifs liées comme lues
        $n = new Notification($pdo);
        $n->markReadForConversation($me, $convId);
        respond(['success' => true]);
    }

    default:
        fail("Action inconnue : " . htmlspecialchars($action), 400);
}
