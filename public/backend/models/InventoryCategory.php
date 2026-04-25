<?php
/**
 * Inventory Category Model
 */

class InventoryCategory {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($activeOnly = false) {
        $sql = "SELECT * FROM inventory_categories";
        $params = [];

        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }

        $sql .= " ORDER BY sort_order ASC, name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_categories WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existsByName($name, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->pdo->prepare("SELECT id FROM inventory_categories WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
            $stmt->execute([$name, (int)$excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT id FROM inventory_categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmt->execute([$name]);
        }

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $code, $defaultThreshold, $allowOverride, $isActive, $sortOrder) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO inventory_categories (name, code, default_low_stock_threshold, allow_threshold_override, is_active, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $name,
            $code,
            $defaultThreshold,
            (int)$allowOverride,
            (int)$isActive,
            (int)$sortOrder
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, $name, $code, $defaultThreshold, $allowOverride, $isActive, $sortOrder) {
        $stmt = $this->pdo->prepare(
            "UPDATE inventory_categories
             SET name = ?, code = ?, default_low_stock_threshold = ?, allow_threshold_override = ?, is_active = ?, sort_order = ?, updated_at = NOW()
             WHERE id = ?"
        );

        return $stmt->execute([
            $name,
            $code,
            $defaultThreshold,
            (int)$allowOverride,
            (int)$isActive,
            (int)$sortOrder,
            (int)$id
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM inventory_categories WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function hasItems($id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM items WHERE category_id = ?");
        $stmt->execute([(int)$id]);
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
