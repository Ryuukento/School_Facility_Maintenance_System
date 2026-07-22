<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Services\DamageReportService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DamageReportController extends Controller
{
    use ApiResponder;

    public function __construct(private readonly DamageReportService $damageService)
    {
    }

    public function index(Request $request)
    {
        $authUser = (array)$request->session()->get('auth_user', $request->session()->get('user', []));
        $reports = $this->damageService->listReports($request->all(), $authUser);

        $statusCounts = [
            'pending' => 0,
            'under_review' => 0,
            'repairing' => 0,
            'repaired' => 0,
            'replaced' => 0,
            'closed' => 0,
        ];

        foreach ($reports->items() as $report) {
            $key = (string)$report->status;
            if (array_key_exists($key, $statusCounts)) {
                $statusCounts[$key]++;
            }
        }

        return $this->ok('Damage reports retrieved', [
            'reports' => $reports,
            'status_counts_page' => $statusCounts,
        ]);
    }

    public function store(Request $request)
    {
        $authUser = (array)$request->session()->get('auth_user', $request->session()->get('user', []));

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'department_id' => ['required', 'integer', 'exists:departments,department_id'],
            'source_dispatch_id' => ['nullable', 'integer', 'exists:dispatches,id'],
            'damage_description' => ['required', 'string', 'min:5'],
            'severity_level' => ['required', 'string', 'in:low,medium,high,critical'],
            'repair_notes' => ['nullable', 'string'],
            'damage_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        try {
            $report = $this->damageService->createReport(
                $validated,
                $authUser,
                $request->file('damage_image')
            );

            return $this->ok('Damage report created successfully', [
                'damage_report_id' => $report->id,
                'damage_report_code' => $report->damage_report_code,
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->fail('Failed to create damage report: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, DamageReport $damageReport)
    {
        $damageReport->load([
            'item:id,name,brand,model,quantity,reserved_quantity',
            'room:id,name',
            'department:department_id,name',
            'reporter:user_id,full_name,email',
            'replacementItem:id,name,brand,model',
        ]);

        return $this->ok('Damage report retrieved', [
            'report' => $damageReport,
        ]);
    }

    public function updateStatus(Request $request, DamageReport $damageReport)
    {
        $authUser = (array)$request->session()->get('auth_user', $request->session()->get('user', []));

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,under_review,repairing,repaired,replaced,closed'],
            'repair_notes' => ['nullable', 'string'],
            'replacement_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'replacement_quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $updated = $this->damageService->updateStatus($damageReport, $validated, $authUser);
            $updated->load([
                'item:id,name,brand,model',
                'room:id,name',
                'department:department_id,name',
                'replacementItem:id,name,brand,model',
            ]);

            return $this->ok('Damage report updated successfully', [
                'report' => $updated,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->fail('Failed to update damage report: ' . $e->getMessage(), 500);
        }
    }

    public function history(Request $request, DamageReport $damageReport)
    {
        $histories = $this->damageService->getHistories($damageReport);

        return $this->ok('Damage report history retrieved', [
            'histories' => $histories,
        ]);
    }
}
