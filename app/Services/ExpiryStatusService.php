<?php

namespace App\Services;

/**
 * Maps a batch's expired_date into a configurable status (§7). Thresholds
 * (days) are stored in settings: inventory.expiry_watch/medium/high/critical.
 */
class ExpiryStatusService
{
    public static function thresholds(): array
    {
        return [
            'watch' => (int) SettingsService::get('inventory.expiry_watch_days', 90),
            'medium' => (int) SettingsService::get('inventory.expiry_medium_days', 60),
            'high' => (int) SettingsService::get('inventory.expiry_high_days', 30),
            'critical' => (int) SettingsService::get('inventory.expiry_critical_days', 7),
        ];
    }

    public static function statusFor(?string $expiredDate): array
    {
        if (!$expiredDate) {
            return ['code' => 'unknown', 'label' => 'Tidak diketahui', 'badge' => 'badge-muted'];
        }

        $days = days_until($expiredDate);
        $t = self::thresholds();

        if ($days < 0) {
            return ['code' => 'expired', 'label' => 'Expired', 'badge' => 'badge-danger', 'days' => $days];
        }
        if ($days <= $t['critical']) {
            return ['code' => 'critical', 'label' => 'Kritis (' . $days . ' hari)', 'badge' => 'badge-danger', 'days' => $days];
        }
        if ($days <= $t['high']) {
            return ['code' => 'high', 'label' => 'Segera Expired (' . $days . ' hari)', 'badge' => 'badge-warning', 'days' => $days];
        }
        if ($days <= $t['medium']) {
            return ['code' => 'medium', 'label' => 'Perhatian (' . $days . ' hari)', 'badge' => 'badge-warning-soft', 'days' => $days];
        }
        if ($days <= $t['watch']) {
            return ['code' => 'watch', 'label' => 'Pantau (' . $days . ' hari)', 'badge' => 'badge-info', 'days' => $days];
        }

        return ['code' => 'safe', 'label' => 'Aman', 'badge' => 'badge-success', 'days' => $days];
    }
}
