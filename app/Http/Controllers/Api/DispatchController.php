<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Services\DispatchService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DispatchController extends Controller
{
    use ApiResponder;

    public function __construct(private readonly DispatchService $dispatchService)
    {
    }

    public function index(Request $request)
    {
        $query = Dispatch::query()
            ->with(['department', 'room', 'approvedByUser', 'releasedByUser', 'receiverUser'])
            ->withCount(['items as item_count']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $q = $request->string('search')->toString();
            $query->where('dispatch_code', 'like', "%{$q}%");
        }

        $dispatches = $query->orderByDesc('created_at')->paginate((int)$request->integer('per_page', 20));

        return $this->ok('Dispatches retrieved', $dispatches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id'       => ['nullable', 'integer', 'exists:departments,department_id'],
            'room_id'             => ['nullable', 'integer', 'exists:rooms,id'],
            'purchase_receipt_id' => ['nullable', 'integer', 'exists:purchase_receipts,id'],
            'notes'               => ['nullable', 'string'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.item_id'     => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
        ]);

        $dispatch = $this->dispatchService->createDispatch($validated, (int)$request->session()->get('user_id'));

        return $this->ok('Dispatch created', ['dispatch_id' => $dispatch->id], 201);
    }

    public function show(Request $request, Dispatch $dispatch)
    {
        $dispatch->load(['items.item', 'department', 'room', 'approvedByUser', 'releasedByUser', 'receiverUser']);
        return $this->ok('Dispatch retrieved', ['dispatch' => $dispatch]);
    }

    public function approve(Request $request, Dispatch $dispatch)
    {
        $validated = $request->validate(['approved_by' => ['required', 'integer', 'exists:users,user_id']]);
        try {
            $this->dispatchService->approveDispatch($dispatch, (int)$validated['approved_by'], (int)$request->session()->get('user_id'));
            return $this->ok('Dispatch approved');
        } catch (ValidationException $e) {
            return $this->fail(collect($e->errors())->flatten()->first() ?: 'Unable to approve dispatch', 400);
        }
    }

    public function release(Request $request, Dispatch $dispatch)
    {
        $validated = $request->validate([
            'released_by' => ['required', 'integer', 'exists:users,user_id'],
            'receiver_user_id' => ['nullable', 'integer', 'exists:users,user_id'],
        ]);
        try {
            $this->dispatchService->releaseDispatch(
                $dispatch,
                (int)$validated['released_by'],
                isset($validated['receiver_user_id']) ? (int)$validated['receiver_user_id'] : null,
                (int)$request->session()->get('user_id')
            );
        } catch (\Throwable $e) {
            $code = $e instanceof ValidationException ? 400 : 500;
            $message = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?: 'Failed to release dispatch')
                : 'Failed to release dispatch: ' . $e->getMessage();
            return $this->fail($message, $code);
        }

        return $this->ok('Dispatch released successfully');
    }

    public function cancel(Request $request, Dispatch $dispatch)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        try {
            $this->dispatchService->cancelDispatch($dispatch, $validated['reason'], (int)$request->session()->get('user_id'));
        } catch (\Throwable $e) {
            $code = $e instanceof \Illuminate\Validation\ValidationException ? 400 : 500;
            $message = $e instanceof \Illuminate\Validation\ValidationException
                ? (collect($e->errors())->flatten()->first() ?: 'Failed to cancel dispatch')
                : 'Failed to cancel dispatch: ' . $e->getMessage();
            return $this->fail($message, $code);
        }

        return $this->ok('Dispatch cancelled successfully');
    }

    public function print(Request $request, Dispatch $dispatch)
    {
        $dispatch->load('items.item');
        // Simple HTML printable report
        $html = '<html><head><title>Dispatch ' . $dispatch->dispatch_code . '</title></head><body>';
        $html .= '<h1>Dispatch ' . $dispatch->dispatch_code . '</h1>';
        $html .= '<p>Status: ' . $dispatch->status . '</p>';
        $html .= '<table border="1" cellpadding="6"><thead><tr><th>Item</th><th>Quantity</th></tr></thead><tbody>';
        foreach ($dispatch->items as $di) {
            $html .= '<tr><td>' . htmlspecialchars($di->item->name ?? 'Unknown') . '</td><td>' . $di->quantity . '</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
