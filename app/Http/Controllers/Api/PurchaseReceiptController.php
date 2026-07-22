<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PurchaseReceiptPostingService;
use App\Services\RoleNormalizerService;
use App\Support\ApiResponder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReceiptController extends Controller
{
    use ApiResponder;

    private const ALLOWED_POST_ROLES = ['super_admin', 'maintenance_admin', 'maintenance_staff'];

    public function __construct(
        private readonly PurchaseReceiptPostingService $postingService
    ) {
    }

    private function sessionUser(Request $request): array
    {
        return (array) $request->session()->get('auth_user', $request->session()->get('user', []));
    }

    private function sessionUserId(Request $request): int
    {
        return (int) ($this->sessionUser($request)['user_id'] ?? 0);
    }

    private function resolveRole(Request $request): string
    {
        return RoleNormalizerService::normalize($this->sessionUser($request)['role'] ?? '');
    }

    public function index(Request $request): JsonResponse
    {
        $receipts = DB::table('purchase_receipts as pr')
            ->select([
                'pr.id',
                'pr.or_number',
                'pr.receipt_date',
                'pr.supplier_name',
                'u.full_name as received_by_name',
                'd.name as department_name',
                'pr.status',
                'pr.created_at',
                DB::raw('COUNT(pri.id) as item_count'),
            ])
            ->join('users as u', 'u.user_id', '=', 'pr.received_by')
            ->leftJoin('departments as d', 'd.department_id', '=', 'pr.department_id')
            ->leftJoin('purchase_receipt_items as pri', 'pri.purchase_receipt_id', '=', 'pr.id')
            ->groupBy(
                'pr.id',
                'pr.or_number',
                'pr.receipt_date',
                'pr.supplier_name',
                'u.full_name',
                'd.name',
                'pr.status',
                'pr.created_at'
            )
            ->orderByDesc('pr.created_at')
            ->get();

        return $this->ok('Receipts retrieved', [
            'receipts' => $receipts,
            'pagination' => ['total' => $receipts->count()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $receivedBy = $this->sessionUserId($request);
        if ($receivedBy <= 0) {
            return $this->fail('Invalid session user', 401);
        }

        $rawReceivedBy = $request->input('received_by_user_id');
        if ($rawReceivedBy !== null && $rawReceivedBy !== '') {
            $overrideId = (int) $rawReceivedBy;
            if ($overrideId > 0 && DB::table('users')->where('user_id', $overrideId)->exists()) {
                $receivedBy = $overrideId;
            }
        }

        $orNumber = trim((string) $request->input('or_number', ''));
        $receiptDate = trim((string) $request->input('receipt_date', ''));
        $supplierName = trim((string) $request->input('supplier_name', ''));
        $rawDept = $request->input('department_id');
        $departmentId = ($rawDept !== null && $rawDept !== '') ? (int) $rawDept : null;
        $remarks = trim((string) $request->input('remarks', ''));

        if ($orNumber === '' || $receiptDate === '' || $supplierName === '') {
            return $this->fail('OR number, receipt date, and supplier name are required', 422);
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $receiptDate);
        if (!$parsed || $parsed->format('Y-m-d') !== $receiptDate) {
            return $this->fail('Invalid receipt date. Use YYYY-MM-DD format', 422);
        }

        if (DB::table('purchase_receipts')->where('or_number', $orNumber)->exists()) {
            return $this->fail('OR number already exists', 409);
        }

        $receiptId = DB::table('purchase_receipts')->insertGetId([
            'or_number' => $orNumber,
            'receipt_date' => $receiptDate,
            'supplier_name' => $supplierName,
            'department_id' => $departmentId,
            'received_by' => $receivedBy,
            'remarks' => $remarks !== '' ? $remarks : null,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok('Purchase receipt created', [
            'id' => $receiptId,
            'or_number' => $orNumber,
            'status' => 'draft',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $receipt = DB::table('purchase_receipts as pr')
            ->select([
                'pr.id', 'pr.or_number', 'pr.receipt_date', 'pr.supplier_name',
                'pr.department_id', 'd.name as department_name',
                'pr.received_by', 'u.full_name as received_by_name',
                'pr.remarks', 'pr.status', 'pr.created_at', 'pr.updated_at',
            ])
            ->join('users as u', 'u.user_id', '=', 'pr.received_by')
            ->leftJoin('departments as d', 'd.department_id', '=', 'pr.department_id')
            ->where('pr.id', $id)
            ->first();

        if (!$receipt) {
            return $this->fail('Purchase receipt not found', 404);
        }

        $items = DB::table('purchase_receipt_items as pri')
            ->select([
                'pri.id', 'pri.item_id', 'pri.item_name',
                'pri.quantity_received', 'pri.unit',
                'pri.category_id', 'ic.name as category_name',
                'pri.inventory_room_id', 'ir.name as inventory_room_name',
            ])
            ->leftJoin('inventory_categories as ic', 'ic.id', '=', 'pri.category_id')
            ->leftJoin('inventory_rooms as ir', 'ir.id', '=', 'pri.inventory_room_id')
            ->where('pri.purchase_receipt_id', $id)
            ->orderBy('pri.id')
            ->get();

        return $this->ok('Receipt retrieved', [
            'receipt' => $receipt,
            'items' => $items,
        ]);
    }

    public function addItem(Request $request, int $id): JsonResponse
    {
        $itemName = trim((string) $request->input('item_name', ''));
        $rawItemId = $request->input('item_id');
        $itemId = ($rawItemId !== null && $rawItemId !== '') ? (int) $rawItemId : null;
        $rawCatId = $request->input('category_id');
        $categoryId = ($rawCatId !== null && $rawCatId !== '') ? (int) $rawCatId : null;
        $inventoryRoomId = (int) $request->input('inventory_room_id', 0);
        $quantityReceived = (int) $request->input('quantity_received', 0);
        $unit = trim((string) $request->input('unit', 'pc')) ?: 'pc';

        if ($itemName === '' || $inventoryRoomId <= 0 || $quantityReceived <= 0) {
            return $this->fail(
                'item_name, inventory_room_id, and quantity_received (> 0) are required',
                422
            );
        }

        $receipt = DB::table('purchase_receipts')
            ->select('id', 'status')
            ->where('id', $id)
            ->first();

        if (!$receipt) {
            return $this->fail('Purchase receipt not found', 404);
        }
        if ($receipt->status !== 'draft') {
            return $this->fail('Cannot add items to a posted receipt', 422);
        }

        $roomExists = DB::table('inventory_rooms')
            ->where('id', $inventoryRoomId)
            ->where('is_active', 1)
            ->exists();
        if (!$roomExists) {
            return $this->fail('Inventory room not found or inactive', 422);
        }

        if ($itemId !== null && !DB::table('items')->where('id', $itemId)->exists()) {
            return $this->fail('Item not found', 422);
        }

        $lineItemId = DB::table('purchase_receipt_items')->insertGetId([
            'purchase_receipt_id' => $id,
            'item_id' => $itemId,
            'item_name' => $itemName,
            'category_id' => $categoryId,
            'inventory_room_id' => $inventoryRoomId,
            'quantity_received' => $quantityReceived,
            'unit' => $unit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok('Item added to receipt', ['id' => $lineItemId], 201);
    }

    public function postReceipt(Request $request, int $id): JsonResponse
    {
        if (!in_array($this->resolveRole($request), self::ALLOWED_POST_ROLES, true)) {
            return $this->fail(
                'Forbidden: Only admins and maintenance staff can post receipts',
                403
            );
        }

        try {
            $this->postingService->postReceipt($id, $this->sessionUserId($request), $request->ip());
        } catch (ModelNotFoundException $e) {
            return $this->fail('Purchase receipt not found', 404);
        } catch (ValidationException $e) {
            return $this->fail(collect($e->errors())->flatten()->first() ?: 'Unable to post receipt', 422);
        }

        return $this->ok('Receipt posted. Stock has been updated.', [
            'id' => $id,
            'status' => 'posted',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = DB::table('purchase_receipts as pr')
            ->select([
                'pr.id',
                DB::raw('pr.or_number AS name'),
                DB::raw('pr.supplier_name AS code'),
                'pr.or_number',
                'pr.supplier_name',
                'pr.receipt_date',
                'pr.status',
            ])
            ->orderByDesc('pr.receipt_date')
            ->orderBy('pr.or_number')
            ->limit(50);

        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('pr.or_number', 'LIKE', '%' . $q . '%')
                    ->orWhere('pr.supplier_name', 'LIKE', '%' . $q . '%');
            });
        }

        return $this->ok('Search results', ['receipts' => $query->get()]);
    }
}
