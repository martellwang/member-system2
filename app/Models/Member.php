<?php

namespace Models;

use Core\Model;

class Member extends Model
{
    protected string $table = 'members';

    /** 全文搜尋 */
    public function search(string $keyword, string $type = '', string $status = ''): array
    {
        $sql    = "SELECT * FROM `members` WHERE 1=1";
        $params = [];

        if ($keyword) {
            $sql .= " AND (`name` LIKE ? OR `email` LIKE ? OR `id_number` LIKE ? OR `tax_id` LIKE ? OR `company_name` LIKE ?)";
            $kw = "%{$keyword}%";
            array_push($params, $kw, $kw, $kw, $kw, $kw);
        }
        if ($type)   { $sql .= " AND `type` = ?";   $params[] = $type; }
        if ($status) { $sql .= " AND `status` = ?"; $params[] = $status; }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** 統計 */
    public function stats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(type = 'personal') AS personal,
                SUM(type = 'company')  AS company,
                SUM(status = 'pending') AS pending,
                SUM(status = 'active')  AS active
            FROM `members`
        ");
        return $stmt->fetch();
    }

    /** 驗證 Email Token */
    public function findByToken(string $token): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `members` WHERE `email_verified_token` = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /** 檢查 Email 是否已存在 */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `members` WHERE `email` = ?");
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }

    /** 檢查身分證號是否已存在 */
    public function idNumberExists(string $idNumber): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `members` WHERE `id_number` = ?");
        $stmt->execute([$idNumber]);
        return (bool) $stmt->fetchColumn();
    }

    /** 檢查統一編號是否已存在 */
    public function taxIdExists(string $taxId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `members` WHERE `tax_id` = ?");
        $stmt->execute([$taxId]);
        return (bool) $stmt->fetchColumn();
    }
}
