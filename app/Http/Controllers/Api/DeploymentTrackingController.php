<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoleNormalizerService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeploymentTrackingController extends Controller
{
    use ApiResponder;

    /** Roles allowed to view deployment tracking (same as legacy access guard) */
    private const ALLOWED_ROLES = ['super_admin', 'maintenance_admin'];

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
     * GET /api/deployment-tracking
     * All released dispatch-items with source OR info.
     * Query: ?q=, ?room_id=, ?department_id=, ?status= (default: released)
     * Response: { success, data: { rows, total, filter_options: { rooms, departments } } }
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->hasAccess($request)) {
            return $this->fail('Forbidden', 403);
        }

        $q      = trim((string) $request->query('q', ''));
        $roomId = (int) $request->query('room_id', 0);
        $deptId = (int) $request->query('department_id', 0);
        $status = trim((string) $request->query('status', 'released'));

        $validStatuses = ['pending', 'approved', 'released', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'released';
        }

        // Build dynamic WHERE clause for the raw query
        $where  = ['d.status = ?'];
        $params = [$status];

        if ($q !== '') {
            $where[]  = '(i.name LIKE ? OR COALESCE(pr_direct.or_number, pr_item.or_number) LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        if ($roomId > 0) {
            $where[]  = 'd.room_id = ?';
            $params[] = $roomId;
        }

        if ($deptId > 0) {
            $where[]  = 'd.department_id = ?';
            $params[] = $deptId;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Complex query: source OR comes from direct link or fallback (oldest receipt referencing item)
        $sql = "
            SELECT
                d.id                                                              AS dispatch_id,
                d.dispatch_code,
                d.status                                                          AS dispatch_status,
                d.created_at                                                      AS dispatch_date,
                i.id                                                              AS item_id,
                i.name                                                            AS item_name,
                di.quantity                                                       AS dispatched_qty,
                COALESCE(pr_direct.or_number,     pr_item.or_number)     AS source_or,
                COALESCE(pr_direct.supplier_name, pr_item.supplier_name) AS source_supplier,
                COALESCE(pr_direct.receipt_date,  pr_item.receipt_date)  AS source_receipt_date,
                r.name                                                            AS room_name,
                dept.name                                                         AS department_name
            FROM       dispatches d
            INNER JOIN dispatch_items di   ON di.dispatch_id = d.id
            INNER JOIN items i             ON i.id = di.item_id
            LEFT  JOIN purchase_receipts pr_direct ON pr_direct.id = d.purchase_receipt_id
            LEFT  JOIN (
                SELECT   pri2.item_id, MIN(pri2.purchase_receipt_id) AS pr_id
                FROM     purchase_receipt_items pri2
                WHERE    pri2.item_id IS NOT NULL
                GROUP BY pri2.item_id
            ) AS pri_min ON pri_min.item_id = di.item_id
            LEFT  JOIN purchase_receipts pr_item ON pr_item.id = pri_min.pr_id
            LEFT  JOIN rooms r             ON r.id = d.room_id
            LEFT  JOIN departments dept    ON dept.department_id = d.department_id
            {$whereClause}
            ORDER BY d.created_at DESC, i.name ASC
            LIMIT 500";

        $rows = DB::select($sql, $params);

        // Filter-option dropdowns: rooms/departments that have at least one released dispatch
        $rooms = DB::table('rooms as r')
            ->select('r.id', 'r.name')
            ->join('dispatches as d', function ($join) {
                $join->on('d.room_id', '=', 'r.id')
                     ->where('d.status', '=', 'released');
            })
            ->distinct()
            ->orderBy('r.name')
            ->get();

        $departments = DB::table('departments as dept')
            ->selectRaw('dept.department_id AS id, dept.name')
            ->join('dispatches as d', function ($join) {
                $join->on('d.department_id', '=', 'dept.department_id')
                     ->where('d.status', '=', 'released');
            })
            ->distinct()
            ->orderBy('dept.name')
            ->get();

        return $this->ok('Deployed items retrieved', [
            'rows'           => $rows,
            'total'          => count($rows),
            'filter_options' => [
                'rooms'       => $rooms,
                'departments' => $departments,
            ],
        ]);
    }

    /**
     * GET /api/deployment-tracking/search
     * Trace items from a purchase receipt (OR number partial match) to their
     * deployment rooms. One row per (receipt_line_item × released dispatch).
     * NULL dispatch columns = item not yet deployed.
     * Query: ?q=
     * Response: { success, data: { rows, receipts } }
     */
    public function search(Request $request): JsonResponse
    {
        if (!$this->hasAccess($request)) {
            return $this->fail('Forbidden', 403);
        }

        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return $this->ok('Search results', ['rows' => [], 'receipts' => []]);
        }

        // Find matching receipts (partial OR number match)
        $receipts = DB::table('purchase_receipts as pr')
            ->select([
                'pr.id', 'pr.or_number', 'pr.supplier_name', 'pr.receipt_date', 'pr.status',
                'u.full_name as received_by_name',
                'd.name as department_name',
            ])
            ->join('users as u',             'u.user_id',       '=', 'pr.received_by')
            ->leftJoin('departments as d',   'd.department_id', '=', 'pr.department_id')
            ->where('pr.or_number', 'LIKE', '%' . $q . '%')
            ->orderByDesc('pr.receipt_date')
            ->limit(10)
            ->get();

        if ($receipts->isEmpty()) {
            return $this->ok('No receipt found matching "' . $q . '"', [
                'rows'     => [],
                'receipts' => [],
                'message'  => 'No receipt found matching "' . $q . '".',
            ]);
        }

        $allRows = [];

        foreach ($receipts as $receipt) {
            // One row per (line_item × dispatch). Items never dispatched appear once with NULL dispatch columns.
            $lines = DB::table('purchase_receipt_items as pri')
                ->select([
                    'pri.id              as line_item_id',
                    'pri.item_name       as receipt_item_name',
                    'pri.quantity_received',
                    'pri.unit',
                    'pri.item_id',
                    'di.quantity         as dispatched_qty',
                    'd.id                as dispatch_id',
                    'd.dispatch_code',
                    'd.status            as dispatch_status',
                    'd.created_at        as dispatch_date',
                    'r.name              as room_name',
                    'dept.name           as department_name',
                ])
                ->leftJoin('dispatch_items as di', function ($join) {
                    $join->on('di.item_id', '=', 'pri.item_id')
                         ->whereNotNull('pri.item_id');
                })
                ->leftJoin('dispatches as d',      'd.id',              '=', 'di.dispatch_id')
                ->leftJoin('rooms as r',            'r.id',              '=', 'd.room_id')
                ->leftJoin('departments as dept',   'dept.department_id', '=', 'd.department_id')
                ->where('pri.purchase_receipt_id', $receipt->id)
                ->orderBy('pri.id')
                ->orderBy('d.created_at')
                ->get();

            foreach ($lines as $line) {
                $row                  = (array) $line;
                $row['or_number']     = $receipt->or_number;
                $row['supplier_name'] = $receipt->supplier_name;
                $row['receipt_date']  = $receipt->receipt_date;
                $row['receipt_status']= $receipt->status;
                $allRows[]            = $row;
            }
        }

        return $this->ok('Search results', [
            'rows'     => $allRows,
            'receipts' => $receipts,
        ]);
    }
}
