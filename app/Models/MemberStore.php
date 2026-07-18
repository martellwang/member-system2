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

    public function adminStoreCodeRows(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                `member_stores`.`id`,
                `member_stores`.`member_id`,
                `member_stores`.`status`,
                `member_stores`.`store_type`,
                `member_stores`.`store_name`,
                `member_stores`.`store_email`,
                `member_stores`.`created_at`,
                `members`.`member_code`,
                `members`.`name` AS `member_name`,
                `members`.`company_name`,
                `members`.`email` AS `member_email`,
                `members`.`is_dealer`,
                `latest_store_code_prefixes`.`prefix` AS `dealer_prefix`,
                0 AS `device_count`
            FROM `member_stores`
            INNER JOIN `members`
                ON `members`.`id` = `member_stores`.`member_id`
            LEFT JOIN (
                SELECT `store_code_prefixes`.`member_id`, `store_code_prefixes`.`prefix`
                FROM `store_code_prefixes`
                INNER JOIN (
                    SELECT `member_id`, MAX(`id`) AS `latest_id`
                    FROM `store_code_prefixes`
                    GROUP BY `member_id`
                ) AS `latest_prefixes`
                    ON `latest_prefixes`.`latest_id` = `store_code_prefixes`.`id`
            ) AS `latest_store_code_prefixes`
                ON `latest_store_code_prefixes`.`member_id` = `members`.`id`
            ORDER BY `member_stores`.`created_at` DESC, `member_stores`.`id` DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
