<?php

namespace Models;

use Core\Model;

class AdminLoginLog extends Model
{
    protected string $table = 'admin_login_logs';

    public function latestByAdminUser(int $adminUserId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $this->db->prepare("
            SELECT
              *,
              COALESCE(`duration_seconds`, GREATEST(0, TIMESTAMPDIFF(SECOND, `login_at`, COALESCE(`logout_at`, NOW())))) AS `usage_seconds`
            FROM `admin_login_logs`
            WHERE `admin_user_id` = ?
            ORDER BY `login_at` DESC, `id` DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$adminUserId]);
        return $stmt->fetchAll();
    }

    public function closeSessionLog(int $logId, ?int $adminUserId = null): bool
    {
        if ($logId <= 0) {
            return false;
        }

        $conditions = ['id' => $logId];
        if ($adminUserId !== null && $adminUserId > 0) {
            $conditions['admin_user_id'] = $adminUserId;
        }

        $where = implode(' AND ', array_map(fn($key) => "`{$key}` = ?", array_keys($conditions)));
        $stmt = $this->db->prepare("
            UPDATE `admin_login_logs`
            SET
              `logout_at` = COALESCE(`logout_at`, NOW()),
              `duration_seconds` = COALESCE(`duration_seconds`, GREATEST(0, TIMESTAMPDIFF(SECOND, `login_at`, NOW())))
            WHERE {$where}
        ");

        return $stmt->execute(array_values($conditions));
    }

    public function closeLatestOpenLogByAdminUser(int $adminUserId): bool
    {
        if ($adminUserId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE `admin_login_logs`
            SET
              `logout_at` = NOW(),
              `duration_seconds` = GREATEST(0, TIMESTAMPDIFF(SECOND, `login_at`, NOW()))
            WHERE `admin_user_id` = ?
              AND `logout_at` IS NULL
            ORDER BY `login_at` DESC, `id` DESC
            LIMIT 1
        ");

        return $stmt->execute([$adminUserId]);
    }
}
