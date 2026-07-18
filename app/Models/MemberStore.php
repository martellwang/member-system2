<?php

namespace Models;

use Core\Model;

class MemberStore extends Model
{
    protected string $table = 'member_stores';

    public function findByMember(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM `member_stores`
            WHERE `member_id` = ?
            ORDER BY `created_at` DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function findByMemberAndId(int $memberId, int $storeId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM `member_stores`
            WHERE `member_id` = ? AND `id` = ?
            LIMIT 1
        ");
        $stmt->execute([$memberId, $storeId]);
        return $stmt->fetch();
    }

    public function countsByMemberIds(array $memberIds): array
    {
        $memberIds = array_values(array_unique(array_filter(
            array_map('intval', $memberIds),
            static fn ($id) => $id > 0
        )));

        if (!$memberIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $stmt = $this->db->prepare("
            SELECT `member_id`, COUNT(*) AS `store_count`
            FROM `member_stores`
            WHERE `member_id` IN ({$placeholders})
            GROUP BY `member_id`
        ");
        $stmt->execute($memberIds);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['member_id']] = (int) $row['store_count'];
        }

        return $counts;
    }
}
