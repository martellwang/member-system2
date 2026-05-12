<?php

namespace Core;

use PDO;

abstract class Model
{
    protected PDO    $db;
    protected string $table  = '';
    protected string $pk     = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** 取全部 */
    public function all(string $order = 'created_at DESC'): array
    {
        $stmt = $this->db->query("SELECT * FROM `{$this->table}` ORDER BY {$order}");
        return $stmt->fetchAll();
    }

    /** 依 ID 取一筆 */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `{$this->pk}` = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** 條件查詢 */
    public function where(array $conditions, string $order = 'created_at DESC'): array
    {
        $clauses = implode(' AND ', array_map(fn($k) => "`{$k}` = ?", array_keys($conditions)));
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE {$clauses} ORDER BY {$order}");
        $stmt->execute(array_values($conditions));
        return $stmt->fetchAll();
    }

    /** 新增 */
    public function insert(array $data): int
    {
        $cols = implode('`, `', array_keys($data));
        $vals = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` (`{$cols}`) VALUES ({$vals})");
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /** 更新 */
    public function update(int $id, array $data): bool
    {
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET {$sets} WHERE `{$this->pk}` = ?");
        return $stmt->execute([...array_values($data), $id]);
    }

    /** 刪除 */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `{$this->pk}` = ?");
        return $stmt->execute([$id]);
    }

    /** 計算總筆數 */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($conditions) {
            $clauses = implode(' AND ', array_map(fn($k) => "`{$k}` = ?", array_keys($conditions)));
            $sql .= " WHERE {$clauses}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($conditions));
        return (int) $stmt->fetchColumn();
    }
}
