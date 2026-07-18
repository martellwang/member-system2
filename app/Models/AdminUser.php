<?php

namespace Models;

use Core\Model;

class AdminUser extends Model
{
    protected string $table = 'admin_users';

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `admin_users` WHERE `email` = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findByPasswordSetupToken(string $token): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `admin_users` WHERE `password_setup_token` = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `admin_users` WHERE `email` = ?";
        $params = [$email];
        if ($excludeId !== null) {
            $sql .= " AND `id` <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function setActiveSession(int $id, string $sessionId): bool
    {
        return $this->update($id, [
            'active_session_id' => $sessionId,
            'active_session_last_seen_at' => date('Y-m-d H:i:s'),
            'duplicate_login_attempt_at' => null,
            'duplicate_login_attempt_ip' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function touchActiveSession(int $id, string $sessionId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE `admin_users`
            SET `active_session_last_seen_at` = ?, `updated_at` = ?
            WHERE `id` = ? AND `active_session_id` = ?
        ");

        $now = date('Y-m-d H:i:s');
        return $stmt->execute([$now, $now, $id, $sessionId]);
    }

    public function clearActiveSession(int $id, string $sessionId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE `admin_users`
            SET
              `active_session_id` = NULL,
              `active_session_last_seen_at` = NULL,
              `updated_at` = ?
            WHERE `id` = ? AND `active_session_id` = ?
        ");

        return $stmt->execute([date('Y-m-d H:i:s'), $id, $sessionId]);
    }

    public function clearAnyActiveSession(int $id): bool
    {
        return $this->update($id, [
            'active_session_id' => null,
            'active_session_last_seen_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recordDuplicateLoginAttempt(int $id, string $ipAddress): bool
    {
        return $this->update($id, [
            'duplicate_login_attempt_at' => date('Y-m-d H:i:s'),
            'duplicate_login_attempt_ip' => $ipAddress,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function consumeDuplicateLoginAttempt(int $id, string $sessionId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT `duplicate_login_attempt_at`, `duplicate_login_attempt_ip`
            FROM `admin_users`
            WHERE `id` = ? AND `active_session_id` = ?
        ");
        $stmt->execute([$id, $sessionId]);
        $row = $stmt->fetch();
        if (!$row || empty($row['duplicate_login_attempt_at'])) {
            return null;
        }

        $clear = $this->db->prepare("
            UPDATE `admin_users`
            SET
              `duplicate_login_attempt_at` = NULL,
              `duplicate_login_attempt_ip` = NULL,
              `updated_at` = ?
            WHERE `id` = ? AND `active_session_id` = ?
        ");
        $clear->execute([date('Y-m-d H:i:s'), $id, $sessionId]);

        return [
            'attempt_at' => $row['duplicate_login_attempt_at'],
            'ip_address' => $row['duplicate_login_attempt_ip'] ?: '—',
        ];
    }
}
