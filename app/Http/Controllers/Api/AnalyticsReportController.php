<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;

class AnalyticsReportController extends Controller
{
    use ApiResponder;

    protected AnalyticsService $service;

    public function __construct(AnalyticsService $service)
    {
        $this->service = $service;
    }

    public function inventorySummary(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'category_id', 'department_id', 'room_id']);
        $data = $this->service->inventorySummary($filters);
        return $this->ok('Inventory summary', $data);
    }

    public function options(Request $request)
    {
        $categories = \Illuminate\Support\Facades\DB::table('inventory_categories')->select(['id', 'name'])->orderBy('name')->get();
        $departments = \Illuminate\Support\Facades\DB::table('departments')->select(['department_id', 'name'])->orderBy('name')->get();
        $rooms = \Illuminate\Support\Facades\DB::table('rooms')->select(['id', 'name'])->orderBy('name')->get();

        $statuses = [
            'items' => ['available', 'low_stock', 'out_of_stock', 'damaged', 'for_repair'],
            'repairs' => ['pending', 'assigned', 'in_progress', 'completed', 'archived'],
        ];

        return $this->ok('Options', ['categories' => $categories, 'departments' => $departments, 'rooms' => $rooms, 'statuses' => $statuses]);
    }

    public function lowStock(Request $request)
    {
        $filters = $request->only(['category_id', 'department_id', 'room_id']);
        $data = $this->service->lowStock($filters);
        return $this->ok('Low stock items', $data);
    }

    public function damagedItems(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'department_id', 'room_id']);
        $data = $this->service->damagedItems($filters);
        return $this->ok('Damaged items', $data);
    }

    public function dispatchReport(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'department_id', 'room_id']);
        $data = $this->service->dispatchReport($filters);
        return $this->ok('Dispatch report', $data);
    }

    public function repairReport(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'department_id', 'room_id']);
        $data = $this->service->repairReport($filters);
        return $this->ok('Repair report', $data);
    }

    public function replacementReport(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'department_id', 'room_id']);
        $data = $this->service->replacementReport($filters);
        return $this->ok('Replacement report', $data);
    }

    public function overview(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->service->overview($filters);
        return $this->ok('Analytics overview', $data);
    }

    public function topRequested(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->service->topRequestedItems($filters);
        return $this->ok('Top requested items', $data);
    }

    public function topRepaired(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->service->topRepairedItems($filters);
        return $this->ok('Top repaired items', $data);
    }

    public function monthlyComparison(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->service->monthlyInventoryComparison($filters);
        return $this->ok('Monthly inventory comparison', $data);
    }

    public function departmentUsage(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->service->departmentUsageComparison($filters);
        return $this->ok('Department usage comparison', $data);
    }

    public function semesterComparison(Request $request)
    {
        $data = $this->service->semesterYearComparison($request->only(['date_from', 'date_to']));
        return $this->ok('Semester vs yearly comparison', $data);
    }

    public function inventoryHealth(Request $request)
    {
        $data = $this->service->inventoryHealthIndicators($request->only(['date_from', 'date_to']));
        return $this->ok('Inventory health indicators', $data);
    }

    public function semesterDetail(Request $request)
    {
        $year = max(2020, min((int) $request->integer('year', (int) now()->year), (int) now()->year));
        return $this->ok('Semester detail', $this->service->semesterDetail($year));
    }
}
