<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
$currentRole = strtolower(trim((string)($user['role'] ?? '')));
if ($currentRole === 'admin_maintenance') {
    $currentRole = 'maintenance_admin';
}

$allowedRoles = ['super_admin', 'maintenance_admin'];
if (!in_array($currentRole, $allowedRoles, true)) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();

$actionFilter = trim((string)($_GET['action'] ?? ''));
$entityFilter = trim((string)($_GET['entity'] ?? ''));
$searchFilter = trim((string)($_GET['q'] ?? ''));
$dateFrom = trim((string)($_GET['from'] ?? ''));
$dateTo = trim((string)($_GET['to'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($actionFilter !== '') {
    $where[] = 'al.action = ?';
    $params[] = $actionFilter;
}

if ($entityFilter !== '') {
    $where[] = 'al.entity_type = ?';
    $params[] = $entityFilter;
}

if ($searchFilter !== '') {
    $where[] = '(al.details LIKE ? OR u.full_name LIKE ? OR al.action LIKE ?)';
    $like = '%' . $searchFilter . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($dateFrom !== '') {
    $where[] = 'DATE(al.created_at) >= ?';
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = 'DATE(al.created_at) <= ?';
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id WHERE {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$listSql = "SELECT al.id, al.user_id, al.action, al.entity_type, al.entity_id, al.details, al.created_at,
                  u.full_name, u.email
           FROM activity_logs al
           LEFT JOIN users u ON al.user_id = u.user_id
           WHERE {$whereSql}
           ORDER BY al.created_at DESC
           LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($listSql);
$bindParams = $params;
$bindParams[] = $perPage;
$bindParams[] = $offset;
$stmt->execute($bindParams);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$actionOptionsStmt = $pdo->query("SELECT DISTINCT action FROM activity_logs WHERE action IS NOT NULL AND action <> '' ORDER BY action ASC");
$actionOptions = $actionOptionsStmt ? $actionOptionsStmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
$entityOptionsStmt = $pdo->query("SELECT DISTINCT entity_type FROM activity_logs WHERE entity_type IS NOT NULL AND entity_type <> '' ORDER BY entity_type ASC");
$entityOptions = $entityOptionsStmt ? $entityOptionsStmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];

$pageTitle = 'Audit Logs - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/audit-log.inline.css">

<main class="container maintenance-admin-dashboard-page audit-log-page">
    <div class="page-header audit-log-header mb-lg">
        <div class="audit-log-title-block">
            <h1 class="audit-log-title">Audit Logs</h1>
            <p class="text-muted audit-log-subtitle">Recent activity across reports, inventory, and system actions.</p>
        </div>
        <div class="text-muted audit-log-total">Total: <?php echo (int)$totalRows; ?></div>
    </div>

    <div class="card">
        <div class="card-body audit-log-filters">
            <form method="GET" class="audit-log-filter-grid">
                <div>
                    <label for="action">Action</label>
                    <select id="action" name="action" class="form-control">
                        <option value="">All actions</option>
                        <?php foreach ($actionOptions as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($actionFilter === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="entity">Entity</label>
                    <select id="entity" name="entity" class="form-control">
                        <option value="">All entities</option>
                        <?php foreach ($entityOptions as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($entityFilter === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="q">Search</label>
                    <input id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="details or user" />
                </div>
                <div>
                    <label for="from">From</label>
                    <input id="from" type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>" />
                </div>
                <div>
                    <label for="to">To</label>
                    <input id="to" type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>" />
                </div>
                <div class="audit-log-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="activity-log.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body audit-log-table-wrap">
            <table class="table audit-log-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Entity ID</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6">No audit logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)($log['created_at'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['full_name'] ?? ('User #' . (int)($log['user_id'] ?? 0)))); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['action'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['entity_type'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['entity_id'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['details'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="audit-log-pagination">
                <div class="text-muted">Page <?php echo (int)$page; ?> of <?php echo (int)$totalPages; ?></div>
                <div class="audit-log-pagination-actions">
                    <?php
                        $baseQuery = $_GET;
                        $prevPage = max(1, $page - 1);
                        $nextPage = min($totalPages, $page + 1);
                        $baseQuery['page'] = $prevPage;
                        $prevUrl = 'activity-log.php?' . http_build_query($baseQuery);
                        $baseQuery['page'] = $nextPage;
                        $nextUrl = 'activity-log.php?' . http_build_query($baseQuery);
                    ?>
                    <a class="btn btn-secondary" href="<?php echo htmlspecialchars($prevUrl); ?>" <?php echo ($page <= 1) ? 'aria-disabled="true"' : ''; ?>>Prev</a>
                    <a class="btn btn-secondary" href="<?php echo htmlspecialchars($nextUrl); ?>" <?php echo ($page >= $totalPages) ? 'aria-disabled="true"' : ''; ?>>Next</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
