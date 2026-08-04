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

    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/utils.js?v=20260521')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/api.js?v=20260521')); ?>"></script>
    <!-- TASK 18 verification fix — cache-buster bumped because resolveAppUrl()
         in components.js gained a guard against double-resolving URLs that
         were already built with window.SFMS_PUBLIC_URL() (pre-existing bug,
         unrelated to Task 18's notification work; discovered while browser-
         verifying the Purchase Receipt flow — see purchase-receipts.php,
         which passes an already-resolved PURCHASE_API into fetchJson()). -->
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/components.js?v=20260804')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/main.js?v=20260521')); ?>"></script>
    <!-- TASK 18 — cache-buster bumped again because notification.js gained
         3 new ENTITY_ROUTES entries (purchase_receipt, inventory, building)
         and an allowedRoles override in resolveNotificationTarget()'s
         clientOnly branch; browsers holding the 20260804 copy would
         otherwise keep using the old routing table. -->
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/notification.js?v=20260804b')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/sidebar.js?v=20260521')); ?>"></script>
    <script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/network-indicator.js?v=20260521')); ?>"></script>
</body>
</html>
