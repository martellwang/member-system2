<?php

namespace Models;

use Core\Model;

class SystemSetting extends Model
{
    protected string $table = 'system_settings';

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare("SELECT `value` FROM `system_settings` WHERE `key_name` = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string) $value;
    }

    public function set(string $key, string $value): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO `system_settings` (`key_name`, `value`, `updated_at`)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()
        ");
        return $stmt->execute([$key, $value]);
    }
}
