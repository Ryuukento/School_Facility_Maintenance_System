<?php
/**
 * Item Model
 */

class Item {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->prepare(
            "SELECT i.*, r.name as room_name
             FROM items i
             LEFT JOIN rooms r ON i.room_id = r.id
             ORDER BY i.name"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByRoom($roomId) {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE room_id = ? ORDER BY name");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($roomId, $name, $status, $quantity, $description) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO items (room_id, name, status, quantity, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$roomId, $name, $status, $quantity, $description]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, $roomId, $name, $status, $quantity, $description) {
        $stmt = $this->pdo->prepare(
            "UPDATE items
             SET room_id = ?, name = ?, status = ?, quantity = ?, description = ?, updated_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$roomId, $name, $status, $quantity, $description, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM items WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
