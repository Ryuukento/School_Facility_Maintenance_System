<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryCategoryController extends Controller
{
    use ApiResponder;

    /**
     * GET /api/inventory-categories
     *
     * Query params:
     *   q        – optional search (matches category name or code)
     *   per_page – 1–200, default 100
     *   page     – default 1
     *
     * Response shape:
     *   { success, message, data: { categories: [{id, name, code, default_low_stock_threshold,
     *     allow_threshold_override, is_active, sort_order, total_items, total_stock_quantity}], pagination } }
     */
    public function index(Request $request): JsonResponse
    {
        $q       = trim((string) $request->query('q', ''));
        $perPage = max(1, min(200, (int) $request->query('per_page', 100)));
        $page    = max(1, (int) $request->query('page', 1));

        $baseQuery = fn () => DB::table('inventory_categories as c')
            ->where('c.is_active', 1)
            ->when($q !== '', function ($q2) use ($q) {
                $like = '%' . strtolower($q) . '%';
                $q2->where(function ($sub) use ($like) {
                    $sub->whereRaw('LOWER(c.name) LIKE ?', [$like])
                        ->orWhereRaw("LOWER(COALESCE(c.code, '')) LIKE ?", [$like]);
                });
            });

        $total = $baseQuery()->count();

        $categories = $baseQuery()
            ->select([
                'c.id',
                'c.name',
                'c.code',
                'c.default_low_stock_threshold',
                'c.allow_threshold_override',
                'c.is_active',
                'c.sort_order',
                DB::raw('COUNT(i.id) as total_items'),
                DB::raw("COALESCE(SUM(CASE WHEN i.item_type = 'inventory_stock' THEN i.quantity ELSE 0 END), 0) as total_stock_quantity"),
            ])
            ->leftJoin('items as i', 'i.category_id', '=', 'c.id')
            ->groupBy(
                'c.id', 'c.name', 'c.code',
                'c.default_low_stock_threshold', 'c.allow_threshold_override',
                'c.is_active', 'c.sort_order'
            )
            ->orderBy('c.sort_order')
            ->orderBy('c.name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->ok('Categories retrieved', [
            'categories' => $categories,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / max(1, $perPage)),
            ],
        ]);
    }

    /**
     * POST /api/inventory-categories
     * Create a new inventory category.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $name             = trim((string)($data['name'] ?? ''));
        $codeRaw          = trim((string)($data['code'] ?? ''));
        $code             = $codeRaw !== '' ? strtolower($codeRaw) : null;
        $defaultThreshold = (array_key_exists('default_low_stock_threshold', $data) && $data['default_low_stock_threshold'] !== '')
                                ? (int)$data['default_low_stock_threshold'] : null;
        $allowOverride    = array_key_exists('allow_threshold_override', $data)
                                ? (int)((bool)$data['allow_threshold_override']) : 1;
        $isActive         = array_key_exists('is_active', $data)
                                ? (int)((bool)$data['is_active']) : 1;
        $sortOrder        = array_key_exists('sort_order', $data) ? (int)$data['sort_order'] : 0;

        if ($name === '') {
            return $this->fail('Category name is required', 422);
        }
        if ($defaultThreshold !== null && $defaultThreshold < 0) {
            return $this->fail('Default threshold must be zero or greater', 422);
        }

        $exists = DB::table('inventory_categories')
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();
        if ($exists) {
            return $this->fail('Category with this name already exists', 409);
        }

        $id = DB::table('inventory_categories')->insertGetId([
            'name'                        => $name,
            'code'                        => $code,
            'default_low_stock_threshold' => $defaultThreshold,
            'allow_threshold_override'    => $allowOverride,
            'is_active'                   => $isActive,
            'sort_order'                  => $sortOrder,
        ]);

        return $this->ok('Category created successfully', ['id' => $id], 201);
    }

    /**
     * PATCH /api/inventory-categories/{id}
     * Update an existing inventory category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();

        $name             = trim((string)($data['name'] ?? ''));
        $codeRaw          = trim((string)($data['code'] ?? ''));
        $code             = $codeRaw !== '' ? strtolower($codeRaw) : null;
        $defaultThreshold = (array_key_exists('default_low_stock_threshold', $data) && $data['default_low_stock_threshold'] !== '')
                                ? (int)$data['default_low_stock_threshold'] : null;
        $allowOverride    = array_key_exists('allow_threshold_override', $data)
                                ? (int)((bool)$data['allow_threshold_override']) : 1;
        $isActive         = array_key_exists('is_active', $data)
                                ? (int)((bool)$data['is_active']) : 1;
        $sortOrder        = array_key_exists('sort_order', $data) ? (int)$data['sort_order'] : 0;

        if ($name === '') {
            return $this->fail('Category name is required', 422);
        }
        if ($defaultThreshold !== null && $defaultThreshold < 0) {
            return $this->fail('Default threshold must be zero or greater', 422);
        }

        if (!DB::table('inventory_categories')->where('id', $id)->exists()) {
            return $this->fail('Category not found', 404);
        }

        $nameConflict = DB::table('inventory_categories')
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $id)
            ->exists();
        if ($nameConflict) {
            return $this->fail('Category with this name already exists', 409);
        }

        DB::table('inventory_categories')->where('id', $id)->update([
            'name'                        => $name,
            'code'                        => $code,
            'default_low_stock_threshold' => $defaultThreshold,
            'allow_threshold_override'    => $allowOverride,
            'is_active'                   => $isActive,
            'sort_order'                  => $sortOrder,
        ]);

        return $this->ok('Category updated successfully');
    }

    /**
     * DELETE /api/inventory-categories/{id}
     * Delete an inventory category (only if it has no items).
     */
    public function destroy(int $id): JsonResponse
    {
        if (DB::table('items')->where('category_id', $id)->exists()) {
            return $this->fail('Cannot delete a category that has items', 409);
        }

        $deleted = DB::table('inventory_categories')->where('id', $id)->delete();

        if (!$deleted) {
            return $this->fail('Category not found', 404);
        }

        return $this->ok('Category deleted successfully');
    }
}
