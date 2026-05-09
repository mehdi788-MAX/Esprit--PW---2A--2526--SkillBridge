<?php
/**
 * Backoffice footer partial — closes the page wrap and ships scripts.
 *
 * Pages may set BEFORE includng this:
 *   $useDataTables   bool   load DataTables JS
 *   $useChatBus      bool   wire ChatBus polling for the bell
 */

$useDataTables = $useDataTables ?? false;
$useChatBus    = $useChatBus    ?? false;

$adminId   = (int)($_SESSION['user_id']   ?? 0);

$BASE = function_exists('base_url')   ? base_url()   : '';
$API  = function_exists('api_url')    ? api_url()    : '../../api';
?>

    </main>
  </div><!-- /.admin-main -->
</div><!-- /.admin-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($useDataTables): ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && jQuery.fn.DataTable) {
      jQuery('table.ad-datatable').each(function () {
        jQuery(this).DataTable({
          language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' },
          pageLength: 25,
          order: []
        });
      });
    }
  });
</script>
<?php endif; ?>

<?php if ($useChatBus && $adminId): ?>
<script src="<?= htmlspecialchars(($BASE ?: '../..') . '/view/shared/chatbus.js') ?>"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof ChatBus !== 'undefined') {
      ChatBus.init({ apiBase: '<?= htmlspecialchars($API) ?>/chat.php', user: <?= (int)$adminId ?>, conv: <?= (int)($chatBusConv ?? 0) ?> });
      const slot = document.querySelector('#bellSlot');
      if (slot) ChatBus.mountBell('#bellSlot');
    }
  });
</script>
<?php endif; ?>

<script>
  // Mobile sidebar toggle
  (function () {
    const shell  = document.getElementById('adminShell');
    const side   = document.getElementById('adminSidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (!toggle || !side) return;
    toggle.addEventListener('click', function () {
      side.classList.toggle('open');
      shell.classList.toggle('has-overlay');
    });
    document.addEventListener('click', function (e) {
      if (!side.classList.contains('open')) return;
      if (toggle.contains(e.target) || side.contains(e.target)) return;
      side.classList.remove('open');
      shell.classList.remove('has-overlay');
    });
  })();
</script>

</body>
</html>
