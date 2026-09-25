<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Writes to audit_logs (§26). Called explicitly from controllers after
 * every create/update/delete/login/logout/stock event — never silently
 * skipped, and never blocks the main action if logging fails.
 */
class AuditLogger
{
    public static function log(string $action, string $module, ?int $recordId = null, ?array $oldData = null, ?array $newData = null): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO audit_logs (user_id, action, module, record_id, old_data, new_data, ip_address, user_agent, created_at)
                 VALUES (:user_id, :action, :module, :record_id, :old_data, :new_data, :ip, :ua, NOW())'
            );
            $stmt->execute([
                'user_id' => Auth::id(),
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'old_data' => $oldData !== null ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                'new_data' => $newData !== null ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('AuditLogger failed: ' . $e->getMessage());
        }
    }
}
