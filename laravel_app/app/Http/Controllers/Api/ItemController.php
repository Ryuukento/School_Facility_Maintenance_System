<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Support\ApiResponder;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $query = Item::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->integer('room_id'));
        }

        $items = $query->orderByDesc('created_at')->paginate((int)$request->integer('per_page', 20));

        return $this->ok('Items retrieved', $items);
    }
}
