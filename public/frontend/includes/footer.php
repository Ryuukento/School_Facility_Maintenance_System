    <?php if (!empty($user)): ?>
    <footer class="app-footer">
        <span class="app-footer-system">PhilCST Centralized School Facility Maintenance Report Management System</span>
        <span class="app-footer-copyright">&copy; <?php echo date('Y'); ?> Philippine College of Science and Technology. All rights reserved.</span>
    </footer>
    <?php endif; ?>

    <div id="global-logout-modal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="global-logout-title" aria-modal="true">
        <div class="modal-content logout-modal-content">
            <div class="modal-header logout-modal-header">
                <div class="logout-modal-text">
                    <h2 id="global-logout-title" class="modal-title">Log out of your account?</h2>
                    <p class="logout-modal-subtitle">Are you sure you want to log out now?</p>
                </div>
                <button class="modal-close logout-modal-close" type="button" aria-label="Close logout dialog">×</button>
            </div>

            <div class="modal-footer logout-modal-footer">
                <button type="button" class="btn btn-secondary logout-modal-cancel" id="global-logout-cancel">Cancel</button>
                <button type="button" class="btn btn-danger logout-modal-confirm" id="global-logout-confirm">Log Out</button>
            </div>
        </div>
    </div>

    <!-- TASK 7A — cache-buster bumped because utils.js now renders the shared
         system modal icons through UI._modalIcon() -> window.UIIcons.svg()
         instead of the previous HTML-entity emoji glyphs. Browsers holding the
         20260521 copy kept serving the pre-fix file, so the old emoji icons
         still appeared in systemConfirm()/systemAlert() dialogs until a forced
         refresh. Only the token changed; utils.js functionality is untouched. -->
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/utils.js?v=20260913')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/api.js?v=20260927')); ?>"></script>
    <!-- TASK 98.2 — cache-buster bumped because api.js now assigns
         `window.API = API` at the bottom of the file (top-level `const`
         does not attach to window on its own). Without bumping this,
         browsers holding the pre-fix cached copy would keep silently
         skipping API.logout(), so LOGOUT events would never reach the
         activity_logs table. -->
    <!-- TASK 18 verification fix — cache-buster bumped because resolveAppUrl()
         in components.js gained a guard against double-resolving URLs that
         were already built with window.SFMS_PUBLIC_URL() (pre-existing bug,
         unrelated to Task 18's notification work; discovered while browser-
         verifying the Purchase Receipt flow — see purchase-receipts.php,
         which passes an already-resolved PURCHASE_API into fetchJson()). -->
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/components.js?v=20260810')); ?>"></script>
    <!-- Cache-buster bumped because light is now the ONLY theme. ThemeManager in
         main.js lost getSavedMode()/resolveMode()/toggleMode() and the
         matchMedia subscription; what remains just re-asserts light.
         A browser still holding the 20260916 copy would keep reading the old
         localStorage preference and re-applying data-theme-resolved='dark'
         after the header boot script had already painted light — the page would
         visibly flip to dark a moment after load, which looks exactly like "the
         toggle removal didn't work". Bumping the token is what forces the old
         switching code out of cache. -->
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/main.js?v=20260920')); ?>"></script>
    <!-- TASK 18 — cache-buster bumped again because notification.js gained
         3 new ENTITY_ROUTES entries (purchase_receipt, inventory, building)
         and an allowedRoles override in resolveNotificationTarget()'s
         clientOnly branch; browsers holding the 20260804 copy would
         otherwise keep using the old routing table. -->
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/notification.js?v=20260804b')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/sidebar.js?v=20260521')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/network-indicator.js?v=20260521')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/mobile-table-cards.js?v=20260926-4')); ?>"></script>
</body>
</html>
