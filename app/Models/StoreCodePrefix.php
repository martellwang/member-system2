<?php

namespace Models;

use Core\Model;

class StoreCodePrefix extends Model
{
    protected string $table = 'store_code_prefixes';

    public function findByMember(int $memberId): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `member_id` = ? ORDER BY `setting_date` DESC, `id` DESC LIMIT 1");
        $stmt->execute([$memberId]);
        return $stmt->fetch();
    }

    public function listByMember(int $memberId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `member_id` = ? ORDER BY `setting_date` DESC, `id` DESC");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function findForMember(int $id, int $memberId): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `id` = ? AND `member_id` = ? LIMIT 1");
        $stmt->execute([$id, $memberId]);
        return $stmt->fetch();
    }

    public function existsPrefix(string $prefix, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `prefix` = ?";
        $params = [$prefix];
        if ($exceptId !== null) {
            $sql .= " AND `id` <> ?";
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
