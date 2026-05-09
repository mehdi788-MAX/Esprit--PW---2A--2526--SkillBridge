<?php
require_once __DIR__ . '/auth_check_admin.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/utilisateur.php';

$utilisateurModel = new Utilisateur($pdo);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . backoffice_url() . '/users_list.php');
    exit;
}

$utilisateurModel->id = $_GET['id'];
$utilisateurModel->readOne();

$utilisateur = [
    'id'        => $utilisateurModel->id,
    'nom'       => $utilisateurModel->nom,
    'prenom'    => $utilisateurModel->prenom,
    'email'     => $utilisateurModel->email,
    'role'      => $utilisateurModel->role,
    'telephone' => $utilisateurModel->telephone,
    'photo'     => $utilisateurModel->photo,
];

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');

    if (empty($nom))                    $errors[] = "Le nom est obligatoire.";
    elseif (strlen($nom) < 3)           $errors[] = "Le nom doit contenir au moins 3 caractères.";

    if (empty($prenom))                 $errors[] = "Le prénom est obligatoire.";
    elseif (strlen($prenom) < 3)        $errors[] = "Le prénom doit contenir au moins 3 caractères.";

    if (empty($email))                                  $errors[] = "L'email est obligatoire.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email invalide.";

    if (empty($role))                                                                        $errors[] = "Le rôle est obligatoire.";
    if (!empty($telephone) && strlen(preg_replace('/\D/', '', $telephone)) < 8)              $errors[] = "Le téléphone doit contenir au moins 8 chiffres.";

    if (empty($errors)) {
        $utilisateurModel->id        = $_POST['id'];
        $utilisateurModel->nom       = $nom;
        $utilisateurModel->prenom    = $prenom;
        $utilisateurModel->email     = $email;
        $utilisateurModel->role      = $role;
        $utilisateurModel->telephone = $telephone;
        $utilisateurModel->photo     = $utilisateur['photo'];

        if (!empty($_FILES['photo']['name'])) {
            $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $_POST['id'] . '_' . time() . '.' . $ext;
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../frontoffice/EasyFolio/assets/img/profile/' . $filename);
                $utilisateurModel->photo = $filename;
            }
        }

        if ($utilisateurModel->update()) {
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_new_password']) {
                    $utilisateurModel->password = $_POST['new_password'];
                    $utilisateurModel->updatePassword();
                } else {
                    $errors[] = "Les mots de passe ne correspondent pas.";
                }
            }
            if (empty($errors)) {
                $_SESSION['success'] = "Utilisateur modifié avec succès.";
                header('Location: ' . backoffice_url() . '/users_list.php');
                exit;
            }
        } else {
            $errors[] = "Erreur lors de la modification.";
        }
    }
}

$pageTitle  = 'Modifier — ' . trim($utilisateur['prenom'] . ' ' . $utilisateur['nom']);
$pageActive = 'users_list';
$pageIcon   = 'bi-pencil-square';

include __DIR__ . '/_partials/header.php';
?>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Édition</span>
    <h2 style="font-size: 1.5rem; font-weight: 800; margin: 10px 0 0;">Modifier l'utilisateur</h2>
  </div>
  <a href="<?= $BO ?>/users_list.php" class="ad-btn ad-btn-ghost"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="ad-alert danger">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
      <strong>Erreur(s) de validation :</strong>
      <ul style="margin: 4px 0 0; padding-left: 18px;">
        <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="ad-card">
      <div class="ad-card-head"><h6><i class="bi bi-person-circle"></i> Aperçu</h6></div>
      <div class="ad-card-body text-center">
        <?php if (!empty($utilisateur['photo'])): ?>
          <img src="<?= htmlspecialchars($FO) ?>/assets/img/profile/<?= htmlspecialchars($utilisateur['photo']) ?>"
               alt=""
               style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:4px solid var(--paper); box-shadow: 0 0 0 2px var(--sage); margin-bottom: 16px;">
        <?php else: ?>
          <div style="width:120px; height:120px; border-radius:50%; background: var(--sage-soft); color: var(--sage); display: inline-flex; align-items: center; justify-content: center; font-size: 2.6rem; font-weight: 800; margin-bottom: 16px;"><?= htmlspecialchars(strtoupper(mb_substr($utilisateur['prenom'] ?? '?', 0, 1, 'UTF-8'))) ?></div>
        <?php endif; ?>
        <h5 style="margin: 0 0 4px;"><?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h5>
        <span class="ad-badge b-<?= htmlspecialchars($utilisateur['role']) ?>"><?= ucfirst(htmlspecialchars($utilisateur['role'])) ?></span>
        <p style="color: var(--ink-mute); margin: 14px 0 0; font-size:.88rem;"><?= htmlspecialchars($utilisateur['email']) ?></p>
        <?php if (!empty($utilisateur['telephone'])): ?>
          <p style="color: var(--ink-mute); margin: 4px 0 0; font-size:.88rem;"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($utilisateur['telephone']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="ad-card">
      <div class="ad-card-head"><h6><i class="bi bi-pencil-square"></i> Informations</h6></div>
      <div class="ad-card-body">
        <form id="editForm" action="<?= $BO ?>/edit_user.php?id=<?= (int)$utilisateur['id'] ?>" method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="id" value="<?= htmlspecialchars($utilisateur['id']) ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="ad-form-label">Nom <span style="color:var(--danger);">*</span></label>
              <input type="text" name="nom" id="nom" class="ad-form-control" value="<?= htmlspecialchars($utilisateur['nom']) ?>">
              <div id="nom-error" class="ad-form-text" style="color:var(--danger); display:none;"></div>
            </div>
            <div class="col-md-6">
              <label class="ad-form-label">Prénom <span style="color:var(--danger);">*</span></label>
              <input type="text" name="prenom" id="prenom" class="ad-form-control" value="<?= htmlspecialchars($utilisateur['prenom']) ?>">
              <div id="prenom-error" class="ad-form-text" style="color:var(--danger); display:none;"></div>
            </div>
            <div class="col-md-7">
              <label class="ad-form-label">Email <span style="color:var(--danger);">*</span></label>
              <input type="text" name="email" id="email" class="ad-form-control" value="<?= htmlspecialchars($utilisateur['email']) ?>">
              <div id="email-error" class="ad-form-text" style="color:var(--danger); display:none;"></div>
            </div>
            <div class="col-md-5">
              <label class="ad-form-label">Téléphone</label>
              <input type="text" name="telephone" id="telephone" class="ad-form-control" value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
              <div id="tel-error" class="ad-form-text" style="color:var(--danger); display:none;"></div>
            </div>
            <div class="col-md-6">
              <label class="ad-form-label">Rôle <span style="color:var(--danger);">*</span></label>
              <select name="role" id="role" class="ad-form-select">
                <option value="freelancer" <?= $utilisateur['role'] === 'freelancer' ? 'selected' : '' ?>>Freelancer</option>
                <option value="client"     <?= $utilisateur['role'] === 'client'     ? 'selected' : '' ?>>Client</option>
                <option value="admin"      <?= $utilisateur['role'] === 'admin'      ? 'selected' : '' ?>>Admin</option>
              </select>
              <div id="role-error" class="ad-form-text" style="color:var(--danger); display:none;"></div>
            </div>
            <div class="col-md-6">
              <label class="ad-form-label">Nouvelle photo</label>
              <input type="file" name="photo" class="ad-form-control" accept="image/*">
              <div class="ad-form-text">JPG / PNG / WebP — laisser vide pour conserver.</div>
            </div>
            <div class="col-md-6">
              <label class="ad-form-label">Nouveau mot de passe</label>
              <input type="password" name="new_password" id="new_password" class="ad-form-control" placeholder="Laisser vide pour ne pas changer">
              <div id="pwd-error" class="ad-form-text" style="color:var(--danger); display:none;"></div>
            </div>
            <div class="col-md-6">
              <label class="ad-form-label">Confirmer mot de passe</label>
              <input type="password" name="confirm_new_password" id="confirm_new_password" class="ad-form-control" placeholder="Répétez">
              <div id="confirmpwd-error" class="ad-form-text" style="color:var(--danger); display:none;"></div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= $BO ?>/users_list.php" class="ad-btn ad-btn-ghost">Annuler</a>
            <button type="submit" class="ad-btn ad-btn-sage"><i class="bi bi-check2-circle"></i> Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('editForm').addEventListener('submit', function (e) {
  let valid = true;
  ['nom','prenom','email','role','telephone'].forEach(id => {
    const f = document.getElementById(id);
    if (f) f.classList.remove('is-invalid');
  });
  ['nom-error','prenom-error','email-error','role-error','tel-error','pwd-error','confirmpwd-error'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.textContent = ''; el.style.display = 'none'; }
  });
  function show(fid, eid, m) {
    const f = document.getElementById(fid), er = document.getElementById(eid);
    if (f) f.classList.add('is-invalid');
    if (er) { er.textContent = m; er.style.display = 'block'; }
    valid = false;
  }
  const nom = document.getElementById('nom').value.trim();
  const prenom = document.getElementById('prenom').value.trim();
  const email = document.getElementById('email').value.trim();
  const role = document.getElementById('role').value;
  const tel = document.getElementById('telephone').value.trim();
  const pwd = document.getElementById('new_password').value;
  const cpwd = document.getElementById('confirm_new_password').value;
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!nom)            show('nom', 'nom-error', 'Le nom est obligatoire.');
  else if (nom.length<3) show('nom', 'nom-error', 'Le nom doit contenir au moins 3 caractères.');
  if (!prenom)          show('prenom', 'prenom-error', 'Le prénom est obligatoire.');
  else if (prenom.length<3) show('prenom', 'prenom-error', 'Le prénom doit contenir au moins 3 caractères.');
  if (!email)             show('email', 'email-error', "L'email est obligatoire.");
  else if (!re.test(email)) show('email', 'email-error', "Format invalide (ex: nom@email.com).");
  if (!role)              show('role', 'role-error', 'Le rôle est obligatoire.');
  if (tel) {
    const digits = tel.replace(/\D/g, '');
    if (digits.length < 8) show('telephone', 'tel-error', 'Au moins 8 chiffres.');
  }
  if (pwd && pwd.length < 8)            show('new_password', 'pwd-error', 'Minimum 8 caractères.');
  if (pwd && pwd !== cpwd)              show('confirm_new_password', 'confirmpwd-error', 'Les mots de passe ne correspondent pas.');
  if (!valid) e.preventDefault();
});
</script>

<?php include __DIR__ . '/_partials/footer.php'; ?>
