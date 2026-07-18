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
            $sql .= " AND (`member_code` LIKE ? OR `name` LIKE ? OR `email` LIKE ? OR `id_number` LIKE ? OR `line_id` LIKE ? OR `tax_id` LIKE ? OR `company_name` LIKE ? OR `contact_city` LIKE ? OR `contact_district` LIKE ? OR `contact_address_line` LIKE ? OR `contact_address` LIKE ?)";
            $kw = "%{$keyword}%";
            array_push($params, $kw, $kw, $kw, $kw, $kw, $kw, $kw, $kw, $kw, $kw, $kw);
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
                SUM(status = 'email_unverified') AS email_unverified,
                SUM(status = 'active')  AS active,
                SUM(status = 'suspended') AS suspended
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

    /** 產生會員編號 */
    public function makeMemberCode(int $id): string
    {
        return 'M' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
    }

    /** 補上會員編號 */
    public function assignMemberCode(int $id): bool
    {
        return $this->update($id, [
            'member_code' => $this->makeMemberCode($id),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** 依 Email 查詢會員 */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `members` WHERE `email` = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /** 依 Email + 身分證號查詢個人會員 */
    public function findPersonalByIdentity(string $email, string $idNumber): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `members` WHERE `type` = 'personal' AND `email` = ? AND `id_number` = ?");
        $stmt->execute([$email, strtoupper($idNumber)]);
        return $stmt->fetch();
    }

    /** 依 Email + 統一編號查詢公司法人會員 */
    public function findCompanyByIdentity(string $email, string $taxId): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `members` WHERE `type` = 'company' AND `email` = ? AND `tax_id` = ?");
        $stmt->execute([$email, $taxId]);
        return $stmt->fetch();
    }

    /** 依 Google ID 查詢會員 */
    public function findByGoogleId(string $googleId): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `members` WHERE `google_id` = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch();
    }

    /** 依 Google ID + 會員類型查詢會員 */
    public function findByGoogleIdAndType(string $googleId, string $type): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `members` WHERE `google_id` = ? AND `type` = ?");
        $stmt->execute([$googleId, $type]);
        return $stmt->fetch();
    }

    /** 檢查 Email 是否已存在 */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `members` WHERE `email` = ?";
        $params = [$email];
        if ($excludeId !== null) {
            $sql .= " AND `id` <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /** 檢查個人會員 Email + 身分證號是否已存在 */
    public function personalIdentityExists(string $email, string $idNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `members` WHERE `type` = 'personal' AND `email` = ? AND `id_number` = ?";
        $params = [$email, strtoupper($idNumber)];
        if ($excludeId !== null) {
            $sql .= " AND `id` <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /** 檢查公司法人 Email + 統一編號是否已存在 */
    public function companyIdentityExists(string $email, string $taxId, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `members` WHERE `type` = 'company' AND `email` = ? AND `tax_id` = ?";
        $params = [$email, $taxId];
        if ($excludeId !== null) {
            $sql .= " AND `id` <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /** 檢查身分證號是否已存在 */
    public function idNumberExists(string $idNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `members` WHERE `id_number` = ?";
        $params = [$idNumber];
        if ($excludeId !== null) {
            $sql .= " AND `id` <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /** 檢查統一編號是否已存在 */
    public function taxIdExists(string $taxId, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `members` WHERE `tax_id` = ?";
        $params = [$taxId];
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
            UPDATE `members`
            SET `active_session_last_seen_at` = ?, `updated_at` = ?
            WHERE `id` = ? AND `active_session_id` = ?
        ");

        $now = date('Y-m-d H:i:s');
        return $stmt->execute([$now, $now, $id, $sessionId]);
    }

    public function clearActiveSession(int $id, string $sessionId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE `members`
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
            FROM `members`
            WHERE `id` = ? AND `active_session_id` = ?
        ");
        $stmt->execute([$id, $sessionId]);
        $row = $stmt->fetch();
        if (!$row || empty($row['duplicate_login_attempt_at'])) {
            return null;
        }

        $clear = $this->db->prepare("
            UPDATE `members`
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
