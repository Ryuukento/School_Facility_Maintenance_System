<?php
/**
 * Replacement Tracking API
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? 'list';

try {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    requireReplacementTrackingReadAccess();

    switch ($action) {
        case 'list':
            listReplacementTracking($pdo);
            break;

        default:
            Response::send(['success' => false, 'message' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
    }
} catch (Throwable $e) {
    Logger::error('replacement-tracking-api error', [
        'action' => $action,
        'error' => $e->getMessage()
    ]);
    Response::send(['success' => false, 'message' => 'Internal server error'], Response::HTTP_INTERNAL_ERROR);
}

function normalizeReplacementTrackingRole($role): string {
    $normalized = strtolower(trim((string)$role));

    if ($normalized === 'admin_maintenance') {
        return 'maintenance_admin';
    }

    if ($normalized === 'eelab_staff' || $normalized === 'maintenance_personnel') {
        return 'maintenance_staff';
    }

    return $normalized;
}

function requireReplacementTrackingReadAccess(): void {
    $user = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
    $role = normalizeReplacementTrackingRole($user['role'] ?? '');
    $allowed = ['maintenance_staff', 'maintenance_admin', 'super_admin'];

    if (!in_array($role, $allowed, true)) {
        Response::send(['success' => false, 'message' => 'Forbidden: Access denied'], Response::HTTP_FORBIDDEN);
    }
}

function listReplacementTracking(PDO $pdo): void {
    $records = fetchReplacementTrackingRecords($pdo);
    $records = applyReplacementTrackingFilters($records, $_GET);

    $filterOptions = buildReplacementTrackingFilterOptions($records);
    $summary = [
        'pending_replacement' => 0,
        'replaced' => 0,
        'for_disposal' => 0,
        'total' => count($records)
    ];

    foreach ($records as $record) {
        $status = $record['tracking_status'] ?? 'pending_replacement';
        if (isset($summary[$status])) {
            $summary[$status] += 1;
        }
    }

    Response::send([
        'success' => true,
        'data' => [
            'records' => array_values($records),
            'filters' => $filterOptions,
            'summary' => $summary
        ]
    ]);
}

function fetchReplacementTrackingRecords(PDO $pdo): array {
    $reportStmt = $pdo->query(
        "SELECT
            mr.report_id,
            mr.title,
            mr.description,
            mr.location,
            mr.status AS report_status,
            mr.created_at,
            mr.updated_at,
            mr.need_change_item_id,
            mr.need_change_quantity,
            mr.need_change_status,
            mr.need_change_approved_at,
            mr.need_change_deducted_at,
            requester.full_name AS requested_by_name,
            approver.full_name AS approved_by_name,
            i.name AS replacement_item_name,
            i.description AS replacement_item_description,
            i.quantity AS replacement_item_stock,
            i.status AS replacement_item_stock_status,
            category.name AS replacement_category_name
         FROM maintenance_reports mr
         LEFT JOIN users requester ON mr.created_by = requester.user_id
         LEFT JOIN users approver ON mr.need_change_approved_by = approver.user_id
         LEFT JOIN items i ON mr.need_change_item_id = i.id
         LEFT JOIN inventory_categories category ON i.category_id = category.id
         WHERE mr.need_change_item_id IS NOT NULL
         ORDER BY COALESCE(mr.need_change_deducted_at, mr.need_change_approved_at, mr.updated_at, mr.created_at) DESC"
    );
    $reports = $reportStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $roomsStmt = $pdo->query(
        "SELECT r.id, r.name, b.name AS building_name, f.name AS floor_name
         FROM rooms r
         LEFT JOIN buildings b ON r.building_id = b.id
         LEFT JOIN floors f ON r.floor_id = f.id
         ORDER BY LENGTH(r.name) DESC, r.name ASC"
    );
    $rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $records = [];
    foreach ($reports as $report) {
        $locationParts = resolveReplacementLocationParts((string)($report['location'] ?? ''), $rooms);
        $trackingStatus = deriveReplacementTrackingStatus($report);
        $reason = deriveReplacementReason($report);
        $oldCondition = deriveOldItemCondition($report);
        $replacementQuantity = max(1, (int)($report['need_change_quantity'] ?? 1));
        $replacementName = trim((string)($report['replacement_item_name'] ?? 'Replacement item'));
        $replacementCategory = trim((string)($report['replacement_category_name'] ?? ''));
        $replacementStock = (int)($report['replacement_item_stock'] ?? 0);
        $replacedAt = $report['need_change_deducted_at']
            ?: ($report['need_change_approved_at'] ?: ($report['updated_at'] ?: $report['created_at']));

        $newItemDetails = $replacementQuantity . ' x ' . $replacementName;
        if ($replacementCategory !== '') {
            $newItemDetails .= ' • ' . $replacementCategory;
        }
        $newItemDetails .= ' • Stock left: ' . $replacementStock;

        $records[] = [
            'report_id' => (int)$report['report_id'],
            'item_name' => $replacementName,
            'title' => (string)($report['title'] ?? ''),
            'location_label' => (string)($report['location'] ?? ''),
            'building_name' => $locationParts['building'],
            'floor_name' => $locationParts['floor'],
            'room_name' => $locationParts['room'],
            'reason_for_replacement' => $reason,
            'old_item_condition' => $oldCondition,
            'new_item_details' => $newItemDetails,
            'date_replaced' => $replacedAt,
            'requested_by' => (string)($report['requested_by_name'] ?? 'Unknown'),
            'approved_by' => trim((string)($report['approved_by_name'] ?? '')) !== '' ? (string)$report['approved_by_name'] : 'Pending approval',
            'tracking_status' => $trackingStatus,
            'need_change_status' => (string)($report['need_change_status'] ?? ''),
            'report_status' => (string)($report['report_status'] ?? ''),
            'description' => (string)($report['description'] ?? '')
        ];
    }

    return $records;
}

function applyReplacementTrackingFilters(array $records, array $filters): array {
    $building = trim((string)($filters['building'] ?? ''));
    $floor = trim((string)($filters['floor'] ?? ''));
    $status = trim((string)($filters['status'] ?? ''));
    $dateFrom = trim((string)($filters['date_from'] ?? ''));
    $dateTo = trim((string)($filters['date_to'] ?? ''));

    return array_values(array_filter($records, function (array $record) use ($building, $floor, $status, $dateFrom, $dateTo) {
        if ($building !== '' && strcasecmp((string)$record['building_name'], $building) !== 0) {
            return false;
        }

        if ($floor !== '' && strcasecmp((string)$record['floor_name'], $floor) !== 0) {
            return false;
        }

        if ($status !== '' && strcasecmp((string)$record['tracking_status'], $status) !== 0) {
            return false;
        }

        $recordDate = trim((string)($record['date_replaced'] ?? ''));
        $recordDateOnly = $recordDate !== '' ? substr($recordDate, 0, 10) : '';

        if ($dateFrom !== '' && $recordDateOnly !== '' && $recordDateOnly < $dateFrom) {
            return false;
        }

        if ($dateTo !== '' && $recordDateOnly !== '' && $recordDateOnly > $dateTo) {
            return false;
        }

        return true;
    }));
}

function buildReplacementTrackingFilterOptions(array $records): array {
    $buildings = [];
    $floors = [];

    foreach ($records as $record) {
        $building = trim((string)($record['building_name'] ?? ''));
        $floor = trim((string)($record['floor_name'] ?? ''));

        if ($building !== '') {
            $buildings[$building] = true;
        }

        if ($floor !== '') {
            $floors[$floor] = true;
        }
    }

    $buildingOptions = array_keys($buildings);
    $floorOptions = array_keys($floors);
    sort($buildingOptions, SORT_NATURAL | SORT_FLAG_CASE);
    sort($floorOptions, SORT_NATURAL | SORT_FLAG_CASE);

    return [
        'buildings' => $buildingOptions,
        'floors' => $floorOptions,
        'statuses' => [
            ['value' => 'pending_replacement', 'label' => 'Pending Replacement'],
            ['value' => 'replaced', 'label' => 'Replaced'],
            ['value' => 'for_disposal', 'label' => 'For Disposal']
        ]
    ];
}

function resolveReplacementLocationParts(string $location, array $rooms): array {
    $matchedRoom = matchLocationToRoom($location, $rooms);
    $parts = [
        'building' => trim((string)($matchedRoom['building_name'] ?? '')),
        'floor' => trim((string)($matchedRoom['floor_name'] ?? '')),
        'room' => trim((string)($matchedRoom['name'] ?? ''))
    ];

    if ($parts['building'] === '' && preg_match('/([A-Za-z0-9 ]+Building[A-Za-z0-9 ]*)/i', $location, $match)) {
        $parts['building'] = trim($match[1]);
    }

    if ($parts['floor'] === '' && preg_match('/((?:\d+(?:st|nd|rd|th)?\s*floor)|(?:floor\s*\d+)|(?:\d+\s*floor))/i', $location, $match)) {
        $parts['floor'] = trim($match[1]);
    }

    if ($parts['room'] === '' && preg_match('/((?:room|rm)\s*[A-Za-z0-9-]+|library|office|lab(?:oratory)?\s*[A-Za-z0-9-]*)/i', $location, $match)) {
        $parts['room'] = trim($match[1]);
    }

    if ($parts['room'] === '') {
        $parts['room'] = trim($location);
    }

    return $parts;
}

function matchLocationToRoom(string $location, array $rooms): ?array {
    $normalizedLocation = preg_replace('/[^a-z0-9]+/i', ' ', strtolower($location));
    $normalizedLocation = trim((string)$normalizedLocation);

    if ($normalizedLocation === '') {
        return null;
    }

    foreach ($rooms as $room) {
        $roomName = trim((string)($room['name'] ?? ''));
        if ($roomName === '') {
            continue;
        }

        $normalizedRoom = preg_replace('/[^a-z0-9]+/i', ' ', strtolower($roomName));
        $normalizedRoom = trim((string)$normalizedRoom);

        if ($normalizedRoom !== '' && strpos($normalizedLocation, $normalizedRoom) !== false) {
            return $room;
        }
    }

    return null;
}

function deriveReplacementTrackingStatus(array $report): string {
    $needChangeStatus = strtolower(trim((string)($report['need_change_status'] ?? '')));
    $reportStatus = strtolower(trim((string)($report['report_status'] ?? '')));

    if ($needChangeStatus === 'deducted' && in_array($reportStatus, ['completed', 'closed'], true)) {
        return 'for_disposal';
    }

    if ($needChangeStatus === 'deducted') {
        return 'replaced';
    }

    return 'pending_replacement';
}

function deriveReplacementReason(array $report): string {
    $haystack = strtolower(trim(((string)($report['title'] ?? '')) . ' ' . ((string)($report['description'] ?? ''))));
    $patterns = [
        'worn out' => 'Worn Out',
        'damaged' => 'Damaged',
        'broken' => 'Broken',
        'defective' => 'Defective',
        'faulty' => 'Faulty',
        'rust' => 'Rusted',
        'leak' => 'Leaking',
        'crack' => 'Cracked'
    ];

    foreach ($patterns as $needle => $label) {
        if (strpos($haystack, $needle) !== false) {
            return $label;
        }
    }

    return 'Needs Replacement';
}

function deriveOldItemCondition(array $report): string {
    $description = trim((string)($report['description'] ?? ''));
    if ($description !== '') {
        $firstSentence = preg_split('/(?<=[.!?])\s+/', $description)[0] ?? $description;
        $firstSentence = trim((string)$firstSentence);
        if ($firstSentence !== '') {
            return substr($firstSentence, 0, 120);
        }
    }

    return deriveReplacementReason($report);
}
