<?php
/**
 * Item Model
 */

class Item {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($itemType = null) {
        $sql = "SELECT i.*, r.name as room_name, c.name as category_name,
                       COALESCE(i.low_stock_threshold_override, c.default_low_stock_threshold) as effective_low_stock_threshold
                FROM items i
                LEFT JOIN rooms r ON i.room_id = r.id
                LEFT JOIN inventory_categories c ON i.category_id = c.id";
        $params = [];

        if ($itemType !== null && $itemType !== '') {
            $sql .= " WHERE i.item_type = ?";
            $params[] = $itemType;
        }

        $sql .= " ORDER BY i.name";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByRoom($roomId) {
        $stmt = $this->pdo->prepare(
            "SELECT i.*, c.name as category_name,
                    COALESCE(i.low_stock_threshold_override, c.default_low_stock_threshold) as effective_low_stock_threshold
             FROM items i
             LEFT JOIN inventory_categories c ON i.category_id = c.id
             WHERE i.room_id = ?
             ORDER BY i.name"
        );
        $stmt->execute([$roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByRoomAndName($roomId, $name) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM items
             WHERE room_id = ? AND LOWER(name) = LOWER(?)
             LIMIT 1"
        );
        $stmt->execute([$roomId, $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($roomId, $categoryId, $name, $status, $quantity, $thresholdOverride, $description, $itemType = 'room_asset', $reservedQuantity = 0, $reorderLevel = 5) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO items (room_id, category_id, item_type, name, status, quantity, reserved_quantity, reorder_level, low_stock_threshold_override, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$roomId, $categoryId, $itemType, $name, $status, $quantity, $reservedQuantity, $reorderLevel, $thresholdOverride, $description]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, $roomId, $categoryId, $name, $status, $quantity, $thresholdOverride, $description, $itemType = null, $reservedQuantity = null, $reorderLevel = null) {
        $sql = "UPDATE items
                SET room_id = ?, category_id = ?, name = ?, status = ?, quantity = ?, low_stock_threshold_override = ?, description = ?";
        $params = [$roomId, $categoryId, $name, $status, $quantity, $thresholdOverride, $description];

        if ($itemType !== null) {
            $sql .= ", item_type = ?";
            $params[] = $itemType;
        }

        if ($reservedQuantity !== null) {
            $sql .= ", reserved_quantity = ?";
            $params[] = (int)$reservedQuantity;
        }

        if ($reorderLevel !== null) {
            $sql .= ", reorder_level = ?";
            $params[] = (int)$reorderLevel;
        }

        $sql .= ", updated_at = NOW() WHERE id = ?";
        $params[] = $id;

        $stmt = $this->pdo->prepare(
            $sql
        );
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM items WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
