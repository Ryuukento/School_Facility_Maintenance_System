<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use App\Services\RepairService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RepairController extends Controller
{
    use ApiResponder;

    public function __construct(private readonly RepairService $repairService)
    {
    }

    public function index(Request $request)
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        $repairs = $this->repairService->listRequests($request->all(), $authUser);

        return $this->ok('Repair requests retrieved', [
            'repairs' => $repairs,
        ]);
    }

    public function store(Request $request)
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));

        $validated = $request->validate([
            'damage_report_id' => ['required', 'integer', 'exists:damage_reports,id'],
            'technician_user_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'repair_type' => ['required', 'string', 'max:100'],
            'repair_description' => ['required', 'string', 'min:5'],
            'repair_cost' => ['nullable', 'numeric', 'min:0'],
            'repair_date' => ['nullable', 'date'],
            'estimated_completion_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $repair = $this->repairService->createRequest($validated, $authUser);

            return $this->ok('Repair request created successfully', [
                'repair_id' => $repair->id,
                'repair_code' => $repair->repair_code,
                'repair' => $repair,
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->fail('Failed to create repair request: ' . $e->getMessage(), 500);
        }
    }

    public function show(RepairRequest $repairRequest)
    {
        $repairRequest->load([
            'damageReport.item:id,name,brand,model,quantity,reserved_quantity',
            'damageReport.room:id,name',
            'damageReport.department:department_id,name',
            'damageReport.reporter:user_id,full_name,email',
            'technician:user_id,full_name,email,role',
            'replacementItem:id,name,brand,model',
            'replacementDispatch:id,dispatch_code,status',
            'createdBy:user_id,full_name',
            'updatedBy:user_id,full_name',
        ]);

        return $this->ok('Repair request retrieved', [
            'repair' => $repairRequest,
        ]);
    }

    public function history(RepairRequest $repairRequest)
    {
        return $this->ok('Repair history retrieved', [
            'histories' => $this->repairService->getHistories($repairRequest),
        ]);
    }

    public function assign(Request $request, RepairRequest $repairRequest)
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        $validated = $request->validate([
            'technician_user_id' => ['required', 'integer', 'exists:users,user_id'],
            'estimated_completion_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $repair = $this->repairService->assignTechnician($repairRequest, $validated, $authUser);

            return $this->ok('Technician assignment updated successfully', [
                'repair' => $repair,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->fail('Failed to assign technician: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, RepairRequest $repairRequest)
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        $validated = $request->validate([
            'repair_type' => ['nullable', 'string', 'max:100'],
            'repair_description' => ['nullable', 'string'],
            'repair_cost' => ['nullable', 'numeric', 'min:0'],
            'repair_status' => ['required', 'string', 'in:pending,assigned,diagnosing,repairing,waiting_parts,completed,failed,archived'],
            'repair_date' => ['nullable', 'date'],
            'estimated_completion_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'failure_reason' => ['nullable', 'string'],
        ]);

        try {
            $repair = $this->repairService->updateRequest($repairRequest, $validated, $authUser);

            return $this->ok('Repair request updated successfully', [
                'repair' => $repair,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->fail('Failed to update repair request: ' . $e->getMessage(), 500);
        }
    }

    public function replacement(Request $request, RepairRequest $repairRequest)
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        $validated = $request->validate([
            'replacement_item_id' => ['required', 'integer', 'exists:items,id'],
            'replacement_quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $repair = $this->repairService->fulfillReplacement($repairRequest, $validated, $authUser);

            return $this->ok('Replacement fulfilled successfully', [
                'repair' => $repair,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->fail('Failed to fulfill replacement: ' . $e->getMessage(), 500);
        }
    }

    public function supportDamageReports(Request $request)
    {
        return $this->ok('Eligible damage reports retrieved', [
            'reports' => $this->repairService->searchEligibleDamageReports(
                trim((string) $request->query('q', '')),
                (int) $request->query('per_page', 20)
            ),
        ]);
    }

    public function supportTechnicians(Request $request)
    {
        return $this->ok('Technicians retrieved', [
            'users' => $this->repairService->searchTechnicians(
                trim((string) $request->query('q', '')),
                (int) $request->query('per_page', 20)
            ),
        ]);
    }
}
