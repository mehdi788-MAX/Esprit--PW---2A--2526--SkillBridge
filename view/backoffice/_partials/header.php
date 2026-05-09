<?php
/**
 * Backoffice header partial — sidebar + topbar.
 *
 * Each page sets these variables BEFORE including this file:
 *   $pageTitle      string  e.g. 'Liste utilisateurs'
 *   $pageActive     string  one of: dashboard | users_list | users_profils |
 *                          users_search | chat_conversations | chat_messages |
 *                          chat_search | chat_new
 *   $pageIcon       string  optional Bootstrap icon class, e.g. 'bi-people-fill'
 *   $useDataTables  bool    optional, opt-in to DataTables vendor
 *   $useChatBus     bool    optional, opt-in to ChatBus polling (chat pages)
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Always require config.php so the URL helpers + $pdo are available
$cfg = __DIR__ . '/../../../config.php';
if (file_exists($cfg)) require_once $cfg;

$pageTitle  = $pageTitle  ?? 'Backoffice';
$pageActive = $pageActive ?? '';
$pageIcon   = $pageIcon   ?? 'bi-speedometer2';
$useDataTables = $useDataTables ?? false;
$useChatBus    = $useChatBus    ?? false;

$adminId   = (int)($_SESSION['user_id']   ?? 0);
$adminNom  = trim((string)($_SESSION['user_nom']  ?? 'Admin'));
$adminFirstName = trim(explode(' ', $adminNom)[0] ?? '') ?: 'Admin';

// Admin's photo for nav chip
$adminPhoto = '';
if (isset($pdo) && $adminId) {
    try {
        $stmt = $pdo->prepare("SELECT photo FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $adminId]);
        $adminPhoto = (string)($stmt->fetchColumn() ?: '');
    } catch (Throwable $e) {}
}

$BO       = function_exists('backoffice_url')  ? backoffice_url()        : '../';
$BOCHAT   = function_exists('backoffice_url')  ? backoffice_url('chat')  : '../chat';
$FO       = function_exists('frontoffice_url') ? frontoffice_url()       : '';
// Admin logout uses the dedicated backoffice endpoint so we never touch the frontoffice login.
$LOGOUT   = $BO . '/logout.php';
$ADMIN_AVATAR = !empty($adminPhoto)
    ? ($FO ? $FO . '/assets/img/profile/' . htmlspecialchars($adminPhoto)
           : '../frontoffice/EasyFolio/assets/img/profile/' . htmlspecialchars($adminPhoto))
    : 'https://ui-avatars.com/api/?name=' . urlencode($adminNom ?: 'Admin') . '&background=1F5F4D&color=fff&bold=true&size=80';
$ADMIN_AVATAR_FALLBACK = 'https://ui-avatars.com/api/?name=' . urlencode($adminNom ?: 'Admin') . '&background=1F5F4D&color=fff&bold=true&size=80';

// Logo path — works whether including from / or /chat/
$LOGO_SRC = (function_exists('frontoffice_url') ? frontoffice_url() : '../frontoffice/EasyFolio') . '/assets/img/skillbridge-logo.png';

// Sidebar nav definition
$navGroups = [
    [
        'heading' => null,
        'items'   => [
            ['key' => 'dashboard',      'icon' => 'bi-speedometer2',     'label' => 'Dashboard',           'url' => $BO . '/dashbord.php'],
        ],
    ],
    [
        'heading' => 'Utilisateurs',
        'items'   => [
            ['key' => 'users_list',     'icon' => 'bi-people-fill',      'label' => 'Liste',               'url' => $BO . '/users_list.php'],
            ['key' => 'users_profils',  'icon' => 'bi-person-vcard-fill','label' => 'Profils complets',    'url' => $BO . '/users_profils.php'],
            ['key' => 'users_search',   'icon' => 'bi-search',           'label' => 'Recherche par rôle',  'url' => $BO . '/search_utilisateurs.php'],
        ],
    ],
    [
        'heading' => 'Chat',
        'items'   => [
            ['key' => 'chat_conversations', 'icon' => 'bi-chat-square-dots-fill', 'label' => 'Conversations',       'url' => $BOCHAT . '/conversations.php'],
            ['key' => 'chat_messages',      'icon' => 'bi-envelope-fill',         'label' => 'Tous les messages',   'url' => $BOCHAT . '/messages.php'],
            ['key' => 'chat_search',        'icon' => 'bi-search-heart-fill',     'label' => 'Recherche messages',  'url' => $BOCHAT . '/searchMessages.php'],
            ['key' => 'chat_new',           'icon' => 'bi-plus-circle-fill',      'label' => 'Nouvelle conversation','url' => $BOCHAT . '/add_conversation.php'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= htmlspecialchars($pageTitle) ?> — SkillBridge Admin</title>

  <link href="<?= htmlspecialchars($LOGO_SRC) ?>" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
  <?php if ($useDataTables): ?>
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <?php endif; ?>

  <style>
    :root {
      --bg:#F7F4ED; --paper:#FFFFFF; --ink:#0F0F0F; --ink-2:#2A2A2A;
      --ink-mute:#5C5C5C; --ink-soft:#A3A3A3; --rule:#E8E2D5;
      --sage:#1F5F4D; --sage-d:#134438; --sage-soft:#E8F0EC;
      --honey:#F5C842; --honey-d:#E0B033; --honey-soft:#FBF1D0;
      --danger:#DC2626; --danger-soft:#FEF2F2;
      --info:#0EA5E9; --info-soft:#E0F2FE;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family:'Manrope', system-ui, -apple-system, sans-serif;
      background: var(--bg); color: var(--ink); letter-spacing:-.005em;
      -webkit-font-smoothing:antialiased; margin:0; min-height:100vh;
    }
    ::selection { background: var(--sage); color: var(--honey); }
    h1, h2, h3, h4, h5, h6 { font-family:'Manrope', sans-serif; font-weight:700; letter-spacing:-.022em; color: var(--ink); }
    a { color: var(--sage); text-decoration:none; }
    a:hover { color: var(--sage-d); }

    /* ============ Layout shell ============ */
    .admin-shell { display:flex; min-height:100vh; }
    .admin-sidebar { width: 260px; flex-shrink:0; background: var(--sage); color: rgba(255,255,255,.78); position: sticky; top:0; height:100vh; overflow-y:auto; display:flex; flex-direction:column; }
    .admin-main { flex:1; min-width:0; display:flex; flex-direction:column; }

    /* ============ Sidebar ============ */
    .sb-brand { padding: 18px 18px 16px; display:flex; align-items:center; gap:10px; border-bottom: 1px solid rgba(255,255,255,.08); }
    .sb-brand .logo-wrap {
      background: var(--paper);
      border-radius: 12px;
      padding: 6px 10px;
      display: inline-flex;
      align-items: center;
      box-shadow: 0 4px 10px -4px rgba(0,0,0,.18);
    }
    .sb-brand .logo { height: 28px; width: auto; display: block; }
    .sb-brand .role { display:inline-flex; align-items:center; padding: 3px 10px; border-radius: 999px; background: var(--honey); color: var(--ink); font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; margin-left:auto; }

    .sb-nav { padding: 14px 12px 24px; flex:1; }
    .sb-heading { font-size: .68rem; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.45); padding: 14px 14px 6px; font-weight: 700; }
    .sb-link {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; border-radius: 12px;
      color: rgba(255,255,255,.78); font-weight: 500; font-size: .92rem;
      text-decoration: none; transition: all .15s ease;
      margin-bottom: 2px;
    }
    .sb-link i { font-size: 1.05rem; flex-shrink:0; opacity: .85; }
    .sb-link:hover { background: rgba(255,255,255,.06); color: #fff; }
    .sb-link.active { background: var(--honey); color: var(--ink); font-weight: 700; box-shadow: 0 6px 16px -8px rgba(245,200,66,.6); }
    .sb-link.active i { opacity: 1; }

    .sb-foot { border-top: 1px solid rgba(255,255,255,.08); padding: 14px 12px; }
    .sb-foot a { display:flex; align-items:center; gap:10px; padding: 10px 14px; border-radius: 12px; color: rgba(255,255,255,.78); font-weight: 500; font-size: .88rem; text-decoration: none; transition: all .15s; }
    .sb-foot a:hover { background: rgba(255,255,255,.06); color: #fff; }

    /* ============ Topbar ============ */
    .admin-topbar {
      position: sticky; top: 0; z-index: 50;
      background: rgba(247,244,237,.92); backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--rule);
      padding: 12px 26px;
      display: flex; align-items: center; gap: 16px;
    }
    .topbar-toggle { display: none; background: var(--paper); border: 1px solid var(--rule); border-radius: 10px; width: 40px; height: 40px; align-items: center; justify-content: center; color: var(--ink); cursor: pointer; }
    .topbar-title { display:flex; align-items:center; gap: 10px; flex: 1; min-width:0; }
    .topbar-title h1 { font-size: 1.25rem; font-weight: 800; margin: 0; line-height: 1.1; letter-spacing:-.01em; }
    .topbar-title .pi {
      width: 36px; height: 36px; border-radius: 10px;
      background: var(--sage-soft); color: var(--sage);
      display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink:0;
    }
    .topbar-actions { display: flex; align-items: center; gap: 8px; }
    .tb-link { display:inline-flex; align-items:center; gap:6px; padding: 9px 14px; border-radius: 999px; background: transparent; color: var(--ink-mute); text-decoration:none; font-weight: 600; font-size: .88rem; transition: all .15s; }
    .tb-link:hover { background: var(--paper); color: var(--ink); }
    .tb-chip { display:inline-flex; align-items:center; gap:8px; padding:4px 14px 4px 4px; border-radius:999px; background: var(--paper); border: 1px solid var(--rule); color: var(--ink); font-weight:600; font-size:.88rem; }
    .tb-chip img { width: 30px; height:30px; border-radius: 50%; object-fit: cover; }
    .tb-cta {
      display:inline-flex; align-items:center; gap:8px;
      background: var(--ink); color: var(--bg); padding: 9px 18px; border-radius: 999px;
      text-decoration:none; font-weight: 600; font-size: .88rem; transition: all .2s;
    }
    .tb-cta:hover { background: var(--sage); color: var(--paper); transform: translateY(-1px); }

    /* ============ Page area ============ */
    .admin-page { padding: 28px 26px 64px; flex: 1; min-height: 0; }

    /* Reusable cards / table styling */
    .ad-card { background: var(--paper); border: 1px solid var(--rule); border-radius: 16px; box-shadow: 0 1px 3px rgba(15,15,15,.04); margin-bottom: 20px; overflow: hidden; }
    .ad-card-head { padding: 16px 22px; border-bottom: 1px solid var(--rule); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .ad-card-head h6 { font-size: .98rem; font-weight: 700; margin: 0; color: var(--ink); display: flex; align-items: center; gap: 8px; }
    .ad-card-head .count { font-family: ui-monospace, monospace; font-size: .78rem; color: var(--ink-mute); background: var(--bg); padding: 3px 10px; border-radius: 999px; font-weight: 600; }
    .ad-card-body { padding: 22px; }
    .ad-card-body.tight { padding: 0; }

    /* Stats cards */
    .stat-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 16px;
      padding: 22px 22px 20px;
      display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;
      transition: all .2s ease;
    }
    .stat-card:hover { border-color: var(--sage); transform: translateY(-2px); box-shadow: 0 14px 28px -16px rgba(31,95,77,.18); }
    .stat-card .lbl { font-size: .72rem; color: var(--ink-mute); text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
    .stat-card .num { font-size: 2rem; font-weight: 800; color: var(--ink); line-height: 1; margin-top: 6px; letter-spacing:-.02em; }
    .stat-card .sub { color: var(--ink-soft); font-size: .82rem; margin-top: 4px; }
    .stat-card .ic { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .stat-card .ic.t-sage   { background: var(--sage-soft);  color: var(--sage); }
    .stat-card .ic.t-honey  { background: var(--honey-soft); color: #92660A; }
    .stat-card .ic.t-info   { background: var(--info-soft);  color: var(--info); }
    .stat-card .ic.t-danger { background: var(--danger-soft); color: var(--danger); }

    /* Tables */
    .ad-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .ad-table thead th {
      background: var(--bg); color: var(--ink-mute);
      font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
      padding: 12px 16px; border-bottom: 1px solid var(--rule); text-align: left;
    }
    .ad-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--rule); font-size: .92rem; vertical-align: middle; }
    .ad-table tbody tr:last-child td { border-bottom: none; }
    .ad-table tbody tr { transition: background .12s ease; }
    .ad-table tbody tr:hover { background: var(--bg); }

    .ad-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid var(--rule); }
    .ad-avatar-fb { width: 36px; height: 36px; border-radius: 50%; background: var(--sage-soft); color: var(--sage); display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .82rem; }

    .ad-badge { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; letter-spacing: -.005em; }
    .ad-badge.b-freelancer { background: var(--honey-soft); color: #92660A; }
    .ad-badge.b-client     { background: var(--sage-soft);  color: var(--sage); }
    .ad-badge.b-admin      { background: var(--danger-soft); color: var(--danger); }
    .ad-badge.b-active     { background: var(--sage-soft);  color: var(--sage); }
    .ad-badge.b-inactive   { background: var(--danger-soft); color: var(--danger); }
    .ad-badge.b-info       { background: var(--info-soft);   color: var(--info); }
    .ad-badge.b-neutral    { background: var(--bg);          color: var(--ink-mute); border: 1px solid var(--rule); }

    .ad-chip { display: inline-block; background: var(--bg); color: var(--ink); padding: 3px 9px; border-radius: 999px; font-size: .72rem; margin: 2px 2px 2px 0; border: 1px solid var(--rule); }

    /* Buttons */
    .ad-btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      padding: 10px 18px; border-radius: 10px; border: 1px solid transparent;
      font-weight: 700; font-size: .9rem; cursor: pointer;
      text-decoration: none; transition: all .15s ease; line-height: 1;
      white-space: nowrap;
    }
    .ad-btn-sage    { background: var(--sage); color: var(--paper); }
    .ad-btn-sage:hover { background: var(--sage-d); color: var(--paper); transform: translateY(-1px); box-shadow: 0 8px 20px -10px rgba(31,95,77,.4); }
    .ad-btn-honey   { background: var(--honey); color: var(--ink); }
    .ad-btn-honey:hover { background: var(--honey-d); color: var(--ink); transform: translateY(-1px); }
    .ad-btn-ink     { background: var(--ink); color: var(--paper); }
    .ad-btn-ink:hover { background: var(--sage); color: var(--paper); transform: translateY(-1px); }
    .ad-btn-ghost   { background: var(--paper); color: var(--ink-mute); border-color: var(--rule); }
    .ad-btn-ghost:hover { color: var(--ink); border-color: var(--ink); }
    .ad-btn-danger  { background: var(--danger); color: #fff; }
    .ad-btn-danger:hover { background: #991B1B; color: #fff; }
    .ad-btn-sm { padding: 7px 12px; font-size: .82rem; }

    /* Icon-only square button (used in table action columns) */
    .ad-iconbtn {
      width: 34px; height: 34px; border-radius: 9px;
      display: inline-flex; align-items: center; justify-content: center;
      background: var(--paper); border: 1px solid var(--rule); color: var(--ink-mute);
      cursor: pointer; transition: all .15s ease; font-size: .92rem; padding: 0;
      text-decoration: none;
    }
    .ad-iconbtn:hover { transform: translateY(-1px); }
    .ad-iconbtn.edit:hover    { border-color: var(--honey-d); background: var(--honey-soft); color: #92660A; }
    .ad-iconbtn.open:hover    { border-color: var(--sage);   background: var(--sage-soft);  color: var(--sage); }
    .ad-iconbtn.toggle:hover  { border-color: var(--info);   background: var(--info-soft);  color: var(--info); }
    .ad-iconbtn.delete:hover  { border-color: var(--danger); background: var(--danger-soft); color: var(--danger); }

    /* Forms */
    .ad-form-label { display: block; font-weight: 600; font-size: .85rem; color: var(--ink-2); margin-bottom: 6px; }
    .ad-form-control, .ad-form-select {
      width: 100%; border-radius: 11px; border: 1px solid var(--rule); padding: 11px 14px; font-size: .94rem;
      background: var(--paper); color: var(--ink);
      transition: border-color .18s, box-shadow .18s;
      font-family:'Manrope', sans-serif;
    }
    .ad-form-control:focus, .ad-form-select:focus { outline: none; border-color: var(--sage); box-shadow: 0 0 0 4px rgba(31,95,77,.12); }
    .ad-form-control.is-invalid { border-color: var(--danger); }
    .ad-form-text { color: var(--ink-soft); font-size: .8rem; margin-top: 4px; }

    /* Alerts */
    .ad-alert { border-radius: 12px; padding: 13px 16px; border: 1px solid; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 10px; font-size: .92rem; }
    .ad-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
    .ad-alert.danger  { background: var(--danger-soft); border-color: #FECACA; color: #991B1B; }
    .ad-alert.warning { background: var(--honey-soft); border-color: rgba(224,176,51,.3); color: #7a4f08; }

    /* Empty state */
    .ad-empty { text-align: center; padding: 50px 20px; color: var(--ink-mute); }
    .ad-empty .ic { width: 72px; height: 72px; border-radius: 22px; background: var(--sage-soft); color: var(--sage); display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 14px; }
    .ad-empty h5 { font-weight: 800; color: var(--ink); margin-bottom: 6px; }

    /* Eyebrow */
    .ad-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: .72rem; font-weight: 700; color: var(--sage); padding: 5px 12px;
      background: var(--sage-soft); border-radius: 999px; letter-spacing: .04em;
    }
    .ad-eyebrow .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--sage); }

    /* Mobile: collapsible sidebar */
    @media (max-width: 991.98px) {
      .topbar-toggle { display: inline-flex; }
      .admin-sidebar {
        position: fixed; left: 0; top: 0; bottom: 0;
        transform: translateX(-100%); transition: transform .25s ease;
        z-index: 100; box-shadow: 0 0 60px rgba(0,0,0,.4);
      }
      .admin-sidebar.open { transform: translateX(0); }
      .admin-shell.has-overlay::after {
        content:''; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 90;
      }
      .admin-page { padding: 18px 16px 56px; }
      .admin-topbar { padding: 12px 16px; }
    }

    /* Bootstrap form-control overrides for legacy markup that snuck in */
    .form-control { border-radius: 11px; border-color: var(--rule); padding: 11px 14px; font-family:'Manrope', sans-serif; }
    .form-control:focus { border-color: var(--sage); box-shadow: 0 0 0 4px rgba(31,95,77,.12); }
    .form-select { border-radius: 11px; border-color: var(--rule); }
    .form-select:focus { border-color: var(--sage); box-shadow: 0 0 0 4px rgba(31,95,77,.12); }

    /* ============ KPI cards (dashboard-style, denser) ============ */
    .kpi {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 14px;
      padding: 18px 18px 16px;
      transition: all .18s ease;
      display: flex; flex-direction: column; gap: 6px;
    }
    .kpi:hover { border-color: var(--sage); transform: translateY(-2px); box-shadow: 0 14px 28px -16px rgba(31,95,77,.18); }
    .kpi .head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .kpi .lbl { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-mute); }
    .kpi .ic-sm { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: .92rem; flex-shrink: 0; }
    .kpi .ic-sm.t-sage   { background: var(--sage-soft);  color: var(--sage); }
    .kpi .ic-sm.t-honey  { background: var(--honey-soft); color: #92660A; }
    .kpi .ic-sm.t-info   { background: var(--info-soft);  color: var(--info); }
    .kpi .ic-sm.t-danger { background: var(--danger-soft); color: var(--danger); }
    .kpi .num { font-size: 1.85rem; font-weight: 800; color: var(--ink); line-height: 1; letter-spacing: -.02em; margin-top: 2px; }
    .kpi .sub { font-size: .78rem; color: var(--ink-soft); display: flex; align-items: center; gap: 6px; }

    /* ============ DataTables theming — match the design system ============ */
    .dataTables_wrapper { padding: 16px 22px 4px; font-family: 'Manrope', sans-serif; }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter { color: var(--ink-mute); font-size: .85rem; margin-bottom: 12px; }
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label { display: inline-flex; align-items: center; gap: 8px; font-weight: 500; color: var(--ink-mute); }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
      border-radius: 10px !important; border: 1px solid var(--rule) !important;
      padding: 8px 12px !important; font-size: .88rem !important; font-family: 'Manrope', sans-serif !important;
      background: var(--paper) !important; color: var(--ink) !important;
      transition: border-color .15s, box-shadow .15s; min-width: 70px;
    }
    .dataTables_wrapper .dataTables_filter input { min-width: 220px; }
    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
      outline: none; border-color: var(--sage) !important;
      box-shadow: 0 0 0 4px rgba(31,95,77,.12) !important;
    }
    /* hide the ad-card-body padding around the wrapper since it has its own */
    .ad-card-body.tight .dataTables_wrapper { padding-top: 16px; padding-bottom: 12px; }
    /* Sort arrows */
    table.dataTable thead th.sorting,
    table.dataTable thead th.sorting_asc,
    table.dataTable thead th.sorting_desc { cursor: pointer; position: relative; padding-right: 22px !important; }
    table.dataTable thead th.sorting::after { content: '\f0dc'; font-family: 'bootstrap-icons'; opacity: .35; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: .7rem; }
    table.dataTable thead th.sorting_asc::after  { content: '\f12c'; font-family: 'bootstrap-icons'; opacity: 1; color: var(--sage); position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: .7rem; }
    table.dataTable thead th.sorting_desc::after { content: '\f128'; font-family: 'bootstrap-icons'; opacity: 1; color: var(--sage); position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: .7rem; }
    /* Bootstrap 5 dataTables built-in arrows hidden */
    table.dataTable thead .sorting_asc::before,
    table.dataTable thead .sorting_desc::before,
    table.dataTable thead .sorting::before,
    table.dataTable thead .sorting::after { display: none !important; }
    /* re-add our own arrows */
    table.dataTable thead th.sorting:not(.sorting_asc):not(.sorting_desc)::after { content: '\f282' !important; font-family: 'bootstrap-icons' !important; display: inline-block !important; opacity: .4; }
    table.dataTable thead th.sorting_asc::after  { content: '\f146' !important; font-family: 'bootstrap-icons' !important; display: inline-block !important; opacity: 1; color: var(--sage); }
    table.dataTable thead th.sorting_desc::after { content: '\f231' !important; font-family: 'bootstrap-icons' !important; display: inline-block !important; opacity: 1; color: var(--sage); }
    /* Pagination */
    .dataTables_wrapper .dataTables_paginate { padding: 12px 22px 16px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      border-radius: 9px !important; border: 1px solid var(--rule) !important;
      background: var(--paper) !important; color: var(--ink-mute) !important;
      padding: 7px 14px !important; margin: 0 3px !important; font-weight: 600 !important; font-size: .86rem !important;
      transition: all .15s ease;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: var(--sage-soft) !important; color: var(--sage) !important; border-color: var(--sage) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
      background: var(--sage) !important; color: var(--paper) !important; border-color: var(--sage) !important;
      box-shadow: 0 6px 14px -8px rgba(31,95,77,.4);
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
      opacity: .35 !important; background: var(--paper) !important; color: var(--ink-soft) !important; border-color: var(--rule) !important; cursor: not-allowed;
    }
    .dataTables_wrapper .dataTables_info { padding: 12px 22px 0; color: var(--ink-soft); font-size: .82rem; }
    /* DataTables Bootstrap5 wrapper — kill its default page-link styling */
    .page-link { border-radius: 9px !important; border-color: var(--rule) !important; color: var(--ink-mute) !important; margin: 0 3px !important; padding: 7px 14px !important; font-weight: 600 !important; font-size: .86rem !important; }
    .page-link:hover { background: var(--sage-soft) !important; color: var(--sage) !important; border-color: var(--sage) !important; }
    .page-item.active .page-link { background: var(--sage) !important; color: var(--paper) !important; border-color: var(--sage) !important; }
    .page-item.disabled .page-link { color: var(--ink-soft) !important; opacity: .5; }
    /* DataTables Bootstrap 5: row layout */
    .dt-length, .dt-search, .dt-info, .dt-paging { font-family: 'Manrope', sans-serif; font-size: .85rem; color: var(--ink-mute); }
    .dt-length select, .dt-search input {
      border-radius: 10px !important; border: 1px solid var(--rule) !important;
      padding: 8px 12px !important; font-size: .88rem !important; font-family: 'Manrope', sans-serif !important;
      background: var(--paper) !important; color: var(--ink) !important; min-width: 70px;
    }
    .dt-search input { min-width: 220px; }
    .dt-length select:focus, .dt-search input:focus {
      outline: none !important; border-color: var(--sage) !important;
      box-shadow: 0 0 0 4px rgba(31,95,77,.12) !important;
    }
    .dt-layout-row { padding: 14px 22px 0; }
    .dt-layout-row.dt-layout-table { padding: 0; }
    .dt-layout-row .dt-info, .dt-layout-row .dt-paging { padding: 14px 0 16px; }
  </style>
</head>
<body>

<div class="admin-shell" id="adminShell">

  <!-- =========== Sidebar =========== -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sb-brand">
      <span class="logo-wrap">
        <img src="<?= htmlspecialchars($LOGO_SRC) ?>" alt="SkillBridge" class="logo">
      </span>
      <span class="role">Admin</span>
    </div>

    <nav class="sb-nav">
      <?php foreach ($navGroups as $group): ?>
        <?php if (!empty($group['heading'])): ?>
          <div class="sb-heading"><?= htmlspecialchars($group['heading']) ?></div>
        <?php endif; ?>
        <?php foreach ($group['items'] as $item):
              $isActive = ($pageActive === $item['key']); ?>
          <a href="<?= htmlspecialchars($item['url']) ?>" class="sb-link<?= $isActive ? ' active' : '' ?>">
            <i class="bi <?= htmlspecialchars($item['icon']) ?>"></i>
            <span><?= htmlspecialchars($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sb-foot">
      <a href="<?= htmlspecialchars($FO ?: '../frontoffice/EasyFolio') ?>/index.php">
        <i class="bi bi-box-arrow-up-right"></i><span>Voir le site</span>
      </a>
      <a href="<?= htmlspecialchars($LOGOUT) ?>">
        <i class="bi bi-box-arrow-right"></i><span>Déconnexion</span>
      </a>
    </div>
  </aside>

  <!-- =========== Main column =========== -->
  <div class="admin-main">

    <!-- Topbar -->
    <div class="admin-topbar">
      <button type="button" class="topbar-toggle" id="sidebarToggle" aria-label="Menu">
        <i class="bi bi-list" style="font-size:1.3rem;"></i>
      </button>
      <div class="topbar-title">
        <span class="pi"><i class="bi <?= htmlspecialchars($pageIcon) ?>"></i></span>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
      </div>
      <div class="topbar-actions">
        <a href="<?= htmlspecialchars($FO ?: '../frontoffice/EasyFolio') ?>/index.php" class="tb-link d-none d-md-inline-flex">
          <i class="bi bi-box-arrow-up-right"></i>Voir le site
        </a>
        <span class="tb-chip">
          <img src="<?= htmlspecialchars($ADMIN_AVATAR) ?>" alt=""
               onerror="this.onerror=null;this.src='<?= htmlspecialchars($ADMIN_AVATAR_FALLBACK) ?>';">
          <span><?= htmlspecialchars($adminFirstName) ?></span>
        </span>
        <a href="<?= htmlspecialchars($LOGOUT) ?>" class="tb-cta">
          <i class="bi bi-box-arrow-right"></i><span>Quitter</span>
        </a>
      </div>
    </div>

    <!-- Page content (closed in footer.php) -->
    <main class="admin-page">
