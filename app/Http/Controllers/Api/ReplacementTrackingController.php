<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoleNormalizerService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReplacementTrackingController extends Controller
{
    use ApiResponder;

    private const ALLOWED_ROLES = ['maintenance_staff', 'maintenance_admin', 'super_admin'];

    private function resolveRole(Request $request): string
    {
        $user = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        return RoleNormalizerService::normalize($user['role'] ?? '');
    }

    private function hasAccess(Request $request): bool
    {
        return in_array($this->resolveRole($request), self::ALLOWED_ROLES, true);
    }

    /**
     * GET /api/replacement-tracking
     * List all replacement workflow records derived from maintenance reports
     * that have a need_change_item_id set.
     * Query (all optional, client-side filtering also supported):
     *   ?building=, ?floor=, ?status=, ?date_from=, ?date_to=
     * Response: { success, data: { records, filters, summary } }
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->hasAccess($request)) {
            return $this->fail('Forbidden', 403);
        }

        // 1. Fetch reports with replacement item info
        $reports = DB::table('maintenance_reports as mr')
            ->select([
                'mr.report_id',
                'mr.title',
                'mr.description',
                'mr.location',
                'mr.status as report_status',
                'mr.created_at',
                'mr.updated_at',
                'mr.need_change_item_id',
                'mr.need_change_quantity',
                'mr.need_change_status',
                'mr.need_change_approved_at',
                'mr.need_change_deducted_at',
                'requester.full_name as requested_by_name',
                'approver.full_name  as approved_by_name',
                'i.name              as replacement_item_name',
                'i.description       as replacement_item_description',
                'i.quantity          as replacement_item_stock',
                'i.status            as replacement_item_stock_status',
                'category.name       as replacement_category_name',
            ])
            ->leftJoin('users as requester',               'mr.created_by',             '=', 'requester.user_id')
            ->leftJoin('users as approver',                'mr.need_change_approved_by', '=', 'approver.user_id')
            ->leftJoin('items as i',                       'mr.need_change_item_id',     '=', 'i.id')
            ->leftJoin('inventory_categories as category', 'i.category_id',              '=', 'category.id')
            ->whereNotNull('mr.need_change_item_id')
            ->orderByRaw('COALESCE(mr.need_change_deducted_at, mr.need_change_approved_at, mr.updated_at, mr.created_at) DESC')
            ->get()
            ->toArray();

        // 2. Fetch rooms for location matching (longest name first — matches legacy ORDER BY LENGTH DESC)
        $rooms = DB::table('rooms as r')
            ->select(['r.id', 'r.name', 'b.name as building_name', 'f.name as floor_name'])
            ->leftJoin('buildings as b', 'r.building_id', '=', 'b.id')
            ->leftJoin('floors as f',    'r.floor_id',    '=', 'f.id')
            ->orderByRaw('LENGTH(r.name) DESC')
            ->orderBy('r.name')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();

        // 3. Build enriched records
        $allRecords = [];
        foreach ($reports as $rawReport) {
            $report              = (array) $rawReport;
            $locationParts       = $this->resolveLocationParts((string) ($report['location'] ?? ''), $rooms);
            $trackingStatus      = $this->deriveTrackingStatus($report);
            $reason              = $this->deriveReason($report);
            $oldCondition        = $this->deriveOldCondition($report);
            $replacementQty      = max(1, (int) ($report['need_change_quantity'] ?? 1));
            $replacementName     = trim((string) ($report['replacement_item_name'] ?? 'Replacement item'));
            $replacementCategory = trim((string) ($report['replacement_category_name'] ?? ''));
            $replacementStock    = (int) ($report['replacement_item_stock'] ?? 0);
            $replacedAt          = $report['need_change_deducted_at']
                ?: ($report['need_change_approved_at'] ?: ($report['updated_at'] ?: $report['created_at']));

            $newItemDetails = $replacementQty . ' x ' . $replacementName;
            if ($replacementCategory !== '') {
                $newItemDetails .= ' • ' . $replacementCategory;
            }
            $newItemDetails .= ' • Stock left: ' . $replacementStock;

            $allRecords[] = [
                'report_id'              => (int) $report['report_id'],
                'item_name'              => $replacementName,
                'title'                  => (string) ($report['title'] ?? ''),
                'location_label'         => (string) ($report['location'] ?? ''),
                'building_name'          => $locationParts['building'],
                'floor_name'             => $locationParts['floor'],
                'room_name'              => $locationParts['room'],
                'reason_for_replacement' => $reason,
                'old_item_condition'     => $oldCondition,
                'new_item_details'       => $newItemDetails,
                'date_replaced'          => $replacedAt,
                'requested_by'           => (string) ($report['requested_by_name'] ?? 'Unknown'),
                'approved_by'            => trim((string) ($report['approved_by_name'] ?? '')) !== ''
                    ? (string) $report['approved_by_name']
                    : 'Pending approval',
                'tracking_status'        => $trackingStatus,
                'need_change_status'     => (string) ($report['need_change_status'] ?? ''),
                'report_status'          => (string) ($report['report_status'] ?? ''),
                'description'            => (string) ($report['description'] ?? ''),
            ];
        }

        // 4. Build filter options from the full unfiltered dataset
        $filterOptions = $this->buildFilterOptions($allRecords);

        // 5. Apply optional server-side filters (frontend currently filters client-side)
        $filtered = $this->applyFilters($allRecords, $request);

        // 6. Summary counts
        $summary = ['pending_replacement' => 0, 'replaced' => 0, 'for_disposal' => 0, 'total' => count($filtered)];
        foreach ($filtered as $rec) {
            $s = $rec['tracking_status'] ?? 'pending_replacement';
            if (array_key_exists($s, $summary)) {
                $summary[$s]++;
            }
        }

        return $this->ok('Replacement tracking retrieved', [
            'records' => array_values($filtered),
            'filters' => $filterOptions,
            'summary' => $summary,
        ]);
    }

    // ---------------------------------------------------------------------------
    // Filtering
    // ---------------------------------------------------------------------------

    private function applyFilters(array $records, Request $request): array
    {
        $building = trim((string) $request->query('building', ''));
        $floor    = trim((string) $request->query('floor', ''));
        $status   = trim((string) $request->query('status', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo   = trim((string) $request->query('date_to', ''));

        if ($building === '' && $floor === '' && $status === '' && $dateFrom === '' && $dateTo === '') {
            return $records;
        }

        return array_values(array_filter($records, function (array $rec) use ($building, $floor, $status, $dateFrom, $dateTo) {
            if ($building !== '' && strcasecmp((string) ($rec['building_name']    ?? ''), $building) !== 0) return false;
            if ($floor    !== '' && strcasecmp((string) ($rec['floor_name']       ?? ''), $floor)    !== 0) return false;
            if ($status   !== '' && strcasecmp((string) ($rec['tracking_status']  ?? ''), $status)   !== 0) return false;

            $recordDate = substr(trim((string) ($rec['date_replaced'] ?? '')), 0, 10);
            if ($dateFrom !== '' && $recordDate !== '' && $recordDate < $dateFrom) return false;
            if ($dateTo   !== '' && $recordDate !== '' && $recordDate > $dateTo)   return false;

            return true;
        }));
    }

    private function buildFilterOptions(array $records): array
    {
        $buildings = [];
        $floors    = [];
        foreach ($records as $rec) {
            $b = trim((string) ($rec['building_name'] ?? ''));
            $f = trim((string) ($rec['floor_name']    ?? ''));
            if ($b !== '') $buildings[$b] = true;
            if ($f !== '') $floors[$f]    = true;
        }

        $buildingList = array_keys($buildings);
        $floorList    = array_keys($floors);
        natcasesort($buildingList);
        natcasesort($floorList);

        return [
            'buildings' => array_values($buildingList),
            'floors'    => array_values($floorList),
            'statuses'  => [
                ['value' => 'pending_replacement', 'label' => 'Pending Replacement'],
                ['value' => 'replaced',            'label' => 'Replaced'],
                ['value' => 'for_disposal',        'label' => 'For Disposal'],
            ],
        ];
    }

    // ---------------------------------------------------------------------------
    // Location resolution (ported from legacy PHP)
    // ---------------------------------------------------------------------------

    private function resolveLocationParts(string $location, array $rooms): array
    {
        $matched = $this->matchLocationToRoom($location, $rooms);
        $parts   = [
            'building' => trim((string) ($matched['building_name'] ?? '')),
            'floor'    => trim((string) ($matched['floor_name']    ?? '')),
            'room'     => trim((string) ($matched['name']          ?? '')),
        ];

        if ($parts['building'] === '' && preg_match('/([A-Za-z0-9 ]+Building[A-Za-z0-9 ]*)/i', $location, $m)) {
            $parts['building'] = trim($m[1]);
        }
        if ($parts['floor'] === '' && preg_match('/((?:\d+(?:st|nd|rd|th)?\s*floor)|(?:floor\s*\d+)|(?:\d+\s*floor))/i', $location, $m)) {
            $parts['floor'] = trim($m[1]);
        }
        if ($parts['room'] === '' && preg_match('/((?:room|rm)\s*[A-Za-z0-9-]+|library|office|lab(?:oratory)?\s*[A-Za-z0-9-]*)/i', $location, $m)) {
            $parts['room'] = trim($m[1]);
        }
        if ($parts['room'] === '') {
            $parts['room'] = trim($location);
        }

        return $parts;
    }

    private function matchLocationToRoom(string $location, array $rooms): ?array
    {
        $normalized = trim((string) preg_replace('/[^a-z0-9]+/i', ' ', strtolower($location)));
        if ($normalized === '') return null;

        foreach ($rooms as $room) {
            $roomName = trim((string) ($room['name'] ?? ''));
            if ($roomName === '') continue;
            $normalizedRoom = trim((string) preg_replace('/[^a-z0-9]+/i', ' ', strtolower($roomName)));
            if ($normalizedRoom !== '' && str_contains($normalized, $normalizedRoom)) {
                return $room;
            }
        }
        return null;
    }

    // ---------------------------------------------------------------------------
    // Status / reason / condition derivation (ported from legacy PHP)
    // ---------------------------------------------------------------------------

    private function deriveTrackingStatus(array $report): string
    {
        $needChangeStatus = strtolower(trim((string) ($report['need_change_status'] ?? '')));
        $reportStatus     = strtolower(trim((string) ($report['report_status']     ?? '')));

        if ($needChangeStatus === 'deducted' && in_array($reportStatus, ['completed', 'closed'], true)) {
            return 'for_disposal';
        }
        if ($needChangeStatus === 'deducted') {
            return 'replaced';
        }
        return 'pending_replacement';
    }

    private function deriveReason(array $report): string
    {
        $haystack = strtolower(trim(((string) ($report['title'] ?? '')) . ' ' . ((string) ($report['description'] ?? ''))));
        foreach ([
            'worn out'  => 'Worn Out',
            'damaged'   => 'Damaged',
            'broken'    => 'Broken',
            'defective' => 'Defective',
            'faulty'    => 'Faulty',
            'rust'      => 'Rusted',
            'leak'      => 'Leaking',
            'crack'     => 'Cracked',
        ] as $needle => $label) {
            if (str_contains($haystack, $needle)) return $label;
        }
        return 'Needs Replacement';
    }

    private function deriveOldCondition(array $report): string
    {
        $description = trim((string) ($report['description'] ?? ''));
        if ($description !== '') {
            $sentences     = preg_split('/(?<=[.!?])\s+/', $description);
            $firstSentence = trim((string) ($sentences[0] ?? $description));
            if ($firstSentence !== '') return substr($firstSentence, 0, 120);
        }
        return $this->deriveReason($report);
    }
}
