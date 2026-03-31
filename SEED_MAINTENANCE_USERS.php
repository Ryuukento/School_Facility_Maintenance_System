<?php
/**
 * Seed maintenance staff and maintenance admin users.
 * Ensures there are at least 6 users for each role.
 */

header('Content-Type: text/html; charset=utf-8');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fetchDepartmentIds(mysqli $conn) {
    $ids = [];
    $result = $conn->query("SELECT department_id FROM school_facility_maintenance.departments ORDER BY department_id ASC");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['department_id'];
        }
        $result->free();
    }

    return $ids;
}

function emailExists(mysqli $conn, $email) {
    $stmt = $conn->prepare("SELECT 1 FROM school_facility_maintenance.users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Failed to prepare email check statement: ' . $conn->error);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function getNextAvailableEmail(mysqli $conn, $basePrefix, &$counter) {
    do {
        $email = sprintf('%s%02d@sfms.local', $basePrefix, $counter);
        $counter++;
    } while (emailExists($conn, $email));

    return $email;
}

function ensureUsersForRole(mysqli $conn, $role, $displayPrefix, $emailPrefix, $targetCount, array $departmentIds, $passwordHash) {
    $createdUsers = [];

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM school_facility_maintenance.users WHERE role = ?");
    if (!$countStmt) {
        throw new Exception('Failed to prepare count statement: ' . $conn->error);
    }

    $countStmt->bind_param('s', $role);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $existingCount = (int)($countResult['total'] ?? 0);
    $countStmt->close();

    if ($existingCount >= $targetCount) {
        return [
            'role' => $role,
            'existing' => $existingCount,
            'created' => 0,
            'final' => $existingCount,
            'users' => []
        ];
    }

    $insertStmt = $conn->prepare(
        "INSERT INTO school_facility_maintenance.users
        (full_name, email, password, role, department_id, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())"
    );

    if (!$insertStmt) {
        throw new Exception('Failed to prepare insert statement: ' . $conn->error);
    }

    $emailCounter = 1;
    $needed = $targetCount - $existingCount;

    for ($i = 0; $i < $needed; $i++) {
        $index = $existingCount + $i + 1;
        $fullName = sprintf('%s %02d', $displayPrefix, $index);
        $email = getNextAvailableEmail($conn, $emailPrefix, $emailCounter);

        $departmentId = null;
        if (!empty($departmentIds)) {
            $departmentId = $departmentIds[$i % count($departmentIds)];
        }

        $insertStmt->bind_param('ssssi', $fullName, $email, $passwordHash, $role, $departmentId);

        if (!$insertStmt->execute()) {
            throw new Exception('Failed to insert user (' . $fullName . '): ' . $insertStmt->error);
        }

        $createdUsers[] = [
            'user_id' => $insertStmt->insert_id,
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'department_id' => $departmentId
        ];
    }

    $insertStmt->close();

    return [
        'role' => $role,
        'existing' => $existingCount,
        'created' => count($createdUsers),
        'final' => $existingCount + count($createdUsers),
        'users' => $createdUsers
    ];
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Maintenance Users</title>
    <style>
        :root {
            --bg1: #2b1055;
            --bg2: #6d2ea3;
            --bg3: #a73af5;
            --card: #ffffff;
            --ok-bg: #e9f9ee;
            --ok: #0f7a39;
            --warn-bg: #fff7e6;
            --warn: #8a5a00;
            --err-bg: #ffe8ec;
            --err: #a11a39;
            --text: #23262f;
            --muted: #6b7280;
            --line: #e5e7eb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: linear-gradient(130deg, var(--bg1), var(--bg2) 55%, var(--bg3));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: min(980px, 100%);
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.30);
            overflow: hidden;
        }

        .head {
            padding: 24px 28px;
            background: linear-gradient(130deg, #34105f, #7a2fbe);
            color: #fff;
        }

        .head h1 {
            margin: 0 0 8px;
            font-size: 28px;
            letter-spacing: 0.4px;
        }

        .head p {
            margin: 0;
            opacity: 0.95;
        }

        .body {
            padding: 26px;
        }

        .msg {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 14px;
            border-left: 5px solid transparent;
        }

        .ok {
            background: var(--ok-bg);
            border-left-color: var(--ok);
            color: var(--ok);
        }

        .warn {
            background: var(--warn-bg);
            border-left-color: var(--warn);
            color: var(--warn);
        }

        .err {
            background: var(--err-bg);
            border-left-color: var(--err);
            color: var(--err);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            border: 1px solid var(--line);
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f8f7fc;
        }

        code {
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 6px;
        }

        .actions {
            margin-top: 22px;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .btn {
            text-align: center;
            text-decoration: none;
            background: #4f46e5;
            color: #fff;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 600;
        }

        .btn:hover { opacity: 0.92; }

        .muted {
            color: var(--muted);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1>User Seeder</h1>
            <p>Nagti-tiyak ng 6 maintenance staff at 6 maintenance admin users.</p>
        </div>
        <div class="body">
<?php
try {
    $conn = new mysqli('localhost', 'root', '', 'school_facility_maintenance');
    if ($conn->connect_error) {
        throw new Exception('Hindi makakonekta sa MySQL: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    $conn->begin_transaction();

    $departmentIds = fetchDepartmentIds($conn);
    $defaultPassword = 'Admin@123';
    $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $staffResult = ensureUsersForRole(
        $conn,
        'maintenance_staff',
        'Maintenance Staff',
        'maintenance.staff',
        6,
        $departmentIds,
        $passwordHash
    );

    $adminResult = ensureUsersForRole(
        $conn,
        'maintenance_admin',
        'Maintenance Admin',
        'maintenance.admin',
        6,
        $departmentIds,
        $passwordHash
    );

    $conn->commit();

    echo '<div class="msg ok"><strong>Tapos na:</strong> Nakapag-seed na ng users. Minimum 6 users bawat target role ang naka-set.</div>';

    echo '<table>';
    echo '<tr><th>Role</th><th>Existing Before</th><th>Created Now</th><th>Final Count</th></tr>';
    echo '<tr><td>maintenance_staff</td><td>' . e($staffResult['existing']) . '</td><td>' . e($staffResult['created']) . '</td><td>' . e($staffResult['final']) . '</td></tr>';
    echo '<tr><td>maintenance_admin</td><td>' . e($adminResult['existing']) . '</td><td>' . e($adminResult['created']) . '</td><td>' . e($adminResult['final']) . '</td></tr>';
    echo '</table>';

    $allCreated = array_merge($staffResult['users'], $adminResult['users']);
    if (!empty($allCreated)) {
        echo '<h3>Newly Created Accounts</h3>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Department</th></tr>';
        foreach ($allCreated as $user) {
            echo '<tr>';
            echo '<td>' . e($user['user_id']) . '</td>';
            echo '<td>' . e($user['full_name']) . '</td>';
            echo '<td><code>' . e($user['email']) . '</code></td>';
            echo '<td>' . e($user['role']) . '</td>';
            echo '<td>' . e($user['department_id'] ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="msg warn">Walang bagong user na ginawa dahil may 6 o higit pa nang users sa dalawang role.</div>';
    }

    echo '<p class="muted">Default password ng bagong accounts: <code>' . e($defaultPassword) . '</code></p>';

    $conn->close();
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        if ($conn->errno === 0) {
            // no-op
        } else {
            $conn->rollback();
        }
    }

    echo '<div class="msg err"><strong>May error:</strong> ' . e($e->getMessage()) . '</div>';
    echo '<p class="muted">Tip: I-check kung naka-start ang MySQL at existing ang database na <code>school_facility_maintenance</code>.</p>';
}
?>
            <div class="actions">
                <a class="btn" href="/School_Facility_Maintenance_System/frontend/pages/index.php">Punta sa Login</a>
                <a class="btn" href="/School_Facility_Maintenance_System/SEED_MAINTENANCE_USERS.php">Run Seeder Again</a>
            </div>
        </div>
    </div>
</body>
</html>
