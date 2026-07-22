<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function inventorySummary(array $filters = []): array
    {
        $cacheKey = 'analytics.inventorySummary:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $query = DB::table('items as i')
                ->leftJoin('inventory_categories as c', 'c.id', '=', 'i.category_id')
                ->selectRaw('i.id, i.name, i.quantity, COALESCE(i.low_stock_threshold_override, c.default_low_stock_threshold, 0) as threshold');

            if (!empty($filters['category_id'])) {
                $query->where('i.category_id', (int)$filters['category_id']);
            }

            if (!empty($filters['department_id'])) {
                $query->where('i.department_id', (int)$filters['department_id']);
            }

            if (!empty($filters['room_id'])) {
                $query->where('i.room_id', (int)$filters['room_id']);
            }

            $items = $query->get();

            $totalQuantity = (int) $items->sum('quantity');
            $distinctItems = (int) $items->count();

            $lowStock = $items->filter(function ($it) {
                return (int)$it->quantity <= (int)$it->threshold;
            })->values();

            return [
                'total_quantity' => $totalQuantity,
                'distinct_items' => $distinctItems,
                'low_stock_count' => $lowStock->count(),
                'low_stock_items' => $lowStock,
            ];
        });
    }

    public function lowStock(array $filters = []): array
    {
        $cacheKey = 'analytics.lowStock:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('items as i')
                ->leftJoin('inventory_categories as c', 'c.id', '=', 'i.category_id')
                ->selectRaw('i.id, i.name, i.quantity, COALESCE(i.low_stock_threshold_override, c.default_low_stock_threshold, 0) as threshold')
                ->whereRaw('i.quantity <= COALESCE(i.low_stock_threshold_override, c.default_low_stock_threshold, 0)');

            if (!empty($filters['category_id'])) {
                $q->where('i.category_id', (int)$filters['category_id']);
            }
            if (!empty($filters['department_id'])) {
                $q->where('i.department_id', (int)$filters['department_id']);
            }
            if (!empty($filters['room_id'])) {
                $q->where('i.room_id', (int)$filters['room_id']);
            }

            $items = $q->orderBy('i.quantity')->limit(1000)->get();
            return ['items' => $items];
        });
    }

    public function damagedItems(array $filters = []): array
    {
        $cacheKey = 'analytics.damagedItems:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('damage_reports as d')
                ->join('items as i', 'i.id', '=', 'd.item_id')
                ->selectRaw('i.id as item_id, i.name, COUNT(d.id) as damage_count')
                ->groupBy('i.id', 'i.name')
                ->orderByDesc('damage_count')
                ->limit(100);

            if (!empty($filters['date_from'])) {
                $q->whereDate('d.created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $q->whereDate('d.created_at', '<=', $filters['date_to']);
            }

            if (!empty($filters['department_id'])) {
                $q->where('d.department_id', (int)$filters['department_id']);
            }

            if (!empty($filters['room_id'])) {
                $q->where('d.room_id', (int)$filters['room_id']);
            }

            $results = $q->get();
            return ['most_damaged' => $results];
        });
    }

    public function dispatchReport(array $filters = []): array
    {
        $cacheKey = 'analytics.dispatchReport:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('dispatch_items as di')
                ->join('dispatches as d', 'd.id', '=', 'di.dispatch_id')
                ->join('items as i', 'i.id', '=', 'di.item_id')
                ->selectRaw('d.id as dispatch_id, d.dispatch_code, d.department_id, COUNT(di.id) as items_count, SUM(di.quantity) as total_quantity')
                ->groupBy('d.id', 'd.dispatch_code', 'd.department_id')
                ->orderByDesc('d.created_at')
                ->limit(200);

            if (!empty($filters['date_from'])) {
                $q->whereDate('d.created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $q->whereDate('d.created_at', '<=', $filters['date_to']);
            }

            if (!empty($filters['department_id'])) {
                $q->where('d.department_id', (int)$filters['department_id']);
            }

            return ['dispatches' => $q->get()];
        });
    }

    public function repairReport(array $filters = []): array
    {
        $cacheKey = 'analytics.repairReport:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('repair_requests as r')
                ->join('damage_reports as d', 'd.id', '=', 'r.damage_report_id')
                ->selectRaw('r.id as repair_id, r.repair_code, r.repair_status, COUNT(r.id) as count')
                ->groupBy('r.id', 'r.repair_code', 'r.repair_status')
                ->orderByDesc('r.created_at')
                ->limit(200);

            if (!empty($filters['date_from'])) {
                $q->whereDate('r.created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $q->whereDate('r.created_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['status'])) {
                $q->where('r.repair_status', $filters['status']);
            }

            return ['repairs' => $q->get()];
        });
    }

    public function replacementReport(array $filters = []): array
    {
        $cacheKey = 'analytics.replacementReport:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('damage_reports as d')
                ->selectRaw('d.id as damage_id, d.replacement_transaction_id, d.status, d.replacement_item_id')
                ->whereNotNull('d.replacement_transaction_id')
                ->orderByDesc('d.created_at')
                ->limit(200);

            if (!empty($filters['date_from'])) {
                $q->whereDate('d.created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $q->whereDate('d.created_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['department_id'])) {
                $q->where('d.department_id', (int)$filters['department_id']);
            }

            return ['replacements' => $q->get()];
        });
    }

    public function overview(array $filters = []): array
    {
        $cacheKey = 'analytics.overview:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            // last 12 months damaged trend
            $damaged = DB::select(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(id) as cnt FROM damage_reports WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym"
            );

            $repairs = DB::select(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(id) as cnt FROM repair_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym"
            );

            $dispatches = DB::select(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(id) as cnt FROM dispatches WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym"
            );

            $stockMovements = DB::select(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(quantity) as qty FROM inventory_transactions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym"
            );

            return [
                'damaged_trends' => $damaged,
                'repair_trends' => $repairs,
                'dispatch_trends' => $dispatches,
                'stock_movements' => $stockMovements,
            ];
        });
    }

    private function timeSeries(string $table, string $dateColumn = 'created_at', int $months = 12, ?string $sumColumn = null, array $where = []): array
    {
        $params = [];
        $whereSql = '';
        foreach ($where as $k => $v) {
            $whereSql .= ' AND ' . $k . ' = ?';
            $params[] = $v;
        }

        $sumExpr = $sumColumn ? ", SUM(" . $sumColumn . ") as val" : ", COUNT(id) as val";
        $sql = "SELECT DATE_FORMAT({$dateColumn}, '%Y-%m') as ym, " . ($sumColumn ? "SUM({$sumColumn}) as val" : "COUNT(id) as val") . " FROM {$table} WHERE {$dateColumn} >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH) {$whereSql} GROUP BY ym ORDER BY ym";

        $rows = DB::select($sql, $params);
        return array_map(fn($r) => (array)$r, $rows);
    }

    public function topRequestedItems(array $filters = []): array
    {
        $cacheKey = 'analytics.topRequestedItems:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('dispatch_items as di')
                ->join('items as i', 'i.id', '=', 'di.item_id')
                ->selectRaw('i.id as item_id, i.name, SUM(di.quantity) as total_requested')
                ->groupBy('i.id', 'i.name')
                ->orderByDesc('total_requested')
                ->limit(50);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $q->join('dispatches as d', 'd.id', '=', 'di.dispatch_id');
                if (!empty($filters['date_from'])) $q->whereDate('d.created_at', '>=', $filters['date_from']);
                if (!empty($filters['date_to'])) $q->whereDate('d.created_at', '<=', $filters['date_to']);
            }

            return ['top_requested' => $q->get()];
        });
    }

    public function topRepairedItems(array $filters = []): array
    {
        $cacheKey = 'analytics.topRepairedItems:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('repair_requests as r')
                ->join('damage_reports as d', 'd.id', '=', 'r.damage_report_id')
                ->join('items as i', 'i.id', '=', 'd.item_id')
                ->selectRaw('i.id as item_id, i.name, COUNT(r.id) as repairs_count')
                ->groupBy('i.id', 'i.name')
                ->orderByDesc('repairs_count')
                ->limit(50);

            if (!empty($filters['date_from'])) $q->whereDate('r.created_at', '>=', $filters['date_from']);
            if (!empty($filters['date_to'])) $q->whereDate('r.created_at', '<=', $filters['date_to']);

            return ['top_repaired' => $q->get()];
        });
    }

    public function monthlyInventoryComparison(array $filters = []): array
    {
        $cacheKey = 'analytics.monthlyInventoryComparison:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $thisMonth = $this->timeSeries('inventory_transactions', 'created_at', 1, 'quantity');
            $last12 = $this->timeSeries('inventory_transactions', 'created_at', 12, 'quantity');
            return ['this_month' => $thisMonth, 'last_12_months' => $last12];
        });
    }

    public function departmentUsageComparison(array $filters = []): array
    {
        $cacheKey = 'analytics.departmentUsageComparison:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $q = DB::table('inventory_transactions as t')
                ->join('items as i', 'i.id', '=', 't.item_id')
                ->leftJoin('departments as d', 'd.department_id', '=', 'i.department_id')
                ->selectRaw('d.department_id, d.name as department_name, SUM(t.quantity) as total_moved')
                ->groupBy('d.department_id', 'd.name')
                ->orderByDesc('total_moved')
                ->limit(50);

            if (!empty($filters['date_from'])) $q->whereDate('t.created_at', '>=', $filters['date_from']);
            if (!empty($filters['date_to'])) $q->whereDate('t.created_at', '<=', $filters['date_to']);

            return ['department_usage' => $q->get()];
        });
    }

    public function semesterYearComparison(array $filters = []): array
    {
        $cacheKey = 'analytics.semesterYearComparison:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            // semester: Jan-Jun, Jul-Dec
            $sql = "SELECT CASE WHEN MONTH(created_at) BETWEEN 1 AND 6 THEN CONCAT(YEAR(created_at),'-S1') ELSE CONCAT(YEAR(created_at),'-S2') END AS period, SUM(quantity) as qty FROM inventory_transactions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR) GROUP BY period ORDER BY period";
            $rows = DB::select($sql);
            return ['semester_trends' => $rows];
        });
    }

    /**
     * Semester detail for a specific year.
     * Returns counts for 4 metrics (maintenance reports, dispatches, damage reports, repairs)
     * split by 1st semester (Jan–Jun) and 2nd semester (Jul–Dec), plus a
     * department-level breakdown using damage_reports and dispatches.
     */
    public function semesterDetail(int $year): array
    {
        $cacheKey = 'analytics.semesterDetail:' . $year;
        return $this->rememberWithTags($cacheKey, 60, function () use ($year) {
            $s1From = "{$year}-01-01";
            $s1To   = "{$year}-06-30";
            $s2From = "{$year}-07-01";
            $s2To   = "{$year}-12-31";

            // Simple count helper — one lightweight query per (metric, semester)
            $count = fn(string $table, string $from, string $to): int =>
                (int) DB::table($table)
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to)
                    ->count();

            $s1 = [
                'maintenance_reports' => $count('maintenance_reports', $s1From, $s1To),
                'dispatches'          => $count('dispatches',          $s1From, $s1To),
                'damage_reports'      => $count('damage_reports',      $s1From, $s1To),
                'repairs'             => $count('repair_requests',     $s1From, $s1To),
            ];

            $s2 = [
                'maintenance_reports' => $count('maintenance_reports', $s2From, $s2To),
                'dispatches'          => $count('dispatches',          $s2From, $s2To),
                'damage_reports'      => $count('damage_reports',      $s2From, $s2To),
                'repairs'             => $count('repair_requests',     $s2From, $s2To),
            ];

            // Department breakdown using the two tables that have department_id FK:
            //   damage_reports.department_id  →  departments.department_id
            //   dispatches.department_id      →  departments.department_id
            $deptRows = DB::select(
                "SELECT
                     COALESCE(dept.name, 'No Department') AS department_name,
                     SUM(CASE WHEN src = 'damage'   AND sem = 1 THEN cnt ELSE 0 END) AS s1_damage,
                     SUM(CASE WHEN src = 'damage'   AND sem = 2 THEN cnt ELSE 0 END) AS s2_damage,
                     SUM(CASE WHEN src = 'dispatch' AND sem = 1 THEN cnt ELSE 0 END) AS s1_dispatches,
                     SUM(CASE WHEN src = 'dispatch' AND sem = 2 THEN cnt ELSE 0 END) AS s2_dispatches
                 FROM (
                     SELECT department_id, 'damage' AS src,
                            CASE WHEN MONTH(created_at) BETWEEN 1 AND 6 THEN 1 ELSE 2 END AS sem,
                            COUNT(*) AS cnt
                     FROM   damage_reports
                     WHERE  YEAR(created_at) = ?
                     GROUP  BY department_id, sem

                     UNION ALL

                     SELECT department_id, 'dispatch' AS src,
                            CASE WHEN MONTH(created_at) BETWEEN 1 AND 6 THEN 1 ELSE 2 END AS sem,
                            COUNT(*) AS cnt
                     FROM   dispatches
                     WHERE  YEAR(created_at) = ?
                     GROUP  BY department_id, sem
                 ) AS combined
                 LEFT JOIN departments dept ON dept.department_id = combined.department_id
                 GROUP  BY combined.department_id, dept.name
                 ORDER  BY (
                     SUM(CASE WHEN src = 'damage'   AND sem = 1 THEN cnt ELSE 0 END) +
                     SUM(CASE WHEN src = 'damage'   AND sem = 2 THEN cnt ELSE 0 END) +
                     SUM(CASE WHEN src = 'dispatch' AND sem = 1 THEN cnt ELSE 0 END) +
                     SUM(CASE WHEN src = 'dispatch' AND sem = 2 THEN cnt ELSE 0 END)
                 ) DESC
                 LIMIT  20",
                [$year, $year]
            );

            return [
                'year'                 => $year,
                'semesters'            => ['s1' => $s1, 's2' => $s2],
                'department_breakdown' => array_map(fn($r) => (array) $r, $deptRows),
            ];
        });
    }

    public function inventoryHealthIndicators(array $filters = []): array
    {
        $cacheKey = 'analytics.inventoryHealth:' . md5(json_encode($filters));
        return $this->rememberWithTags($cacheKey, 120, function () use ($filters) {
            $totalItems = DB::table('items')->count();
            $lowStock = DB::table('items as i')
                ->leftJoin('inventory_categories as c', 'c.id', '=', 'i.category_id')
                ->whereRaw('i.quantity <= COALESCE(i.low_stock_threshold_override, c.default_low_stock_threshold, 0)')
                ->count();
            $outOfStock = DB::table('items')->where('quantity', '<=', 0)->count();

            return [
                'total_items' => $totalItems,
                'low_stock_count' => $lowStock,
                'out_of_stock_count' => $outOfStock,
                'low_stock_percent' => $totalItems > 0 ? round(($lowStock / $totalItems) * 100, 2) : 0,
            ];
        });
    }
    private function rememberWithTags(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::tags(['analytics'])->remember($key, $ttl, $callback);
        } catch (\Throwable $e) {
            return Cache::remember($key, $ttl, $callback);
        }
    }

}
