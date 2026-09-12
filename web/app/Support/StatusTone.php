<?php

namespace App\Support;

/**
 * Platform status tone map for badges (light + dark CSS variants).
 *
 * Green  success  – completed / ok
 * Red    danger   – failure / critical
 * Amber  warning  – major attention / in progress
 * Yellow caution  – pending / minor caution
 * Blue   info     – informational / active standby
 * Grey   neutral  – inactive / not started
 */
final class StatusTone
{
    public const SUCCESS = 'success';

    public const DANGER = 'danger';

    public const WARNING = 'warning';

    public const CAUTION = 'caution';

    public const INFO = 'info';

    public const NEUTRAL = 'neutral';

    /**
     * @return self::SUCCESS|self::DANGER|self::WARNING|self::CAUTION|self::INFO|self::NEUTRAL
     */
    public static function for(?string $status): string
    {
        $key = strtolower(trim(str_replace(['-', ' '], '_', (string) $status)));

        return match ($key) {
            'signed', 'approved', 'completed', 'complete', 'success', 'successful',
            'active', 'verified', 'me_verified', 'ceo_delivered', 'published',
            'baseline_locked', 'cleared', 'paid', 'renewed', 'resolved', 'closed_ok',
            'pass', 'passed', 'yes', 'open_ok', 'me_approved', 'acknowledged',
            'compliant' => self::SUCCESS,

            'rejected', 'failed', 'failure', 'error', 'danger', 'critical', 'overdue',
            'blocked', 'cancelled', 'canceled', 'terminated', 'inactive', 'not_cleared',
            'not_selected', 'breach', 'fail', 'no', 'non_compliant', 'failed_status' => self::DANGER,

            'in_progress', 'processing', 'under_review', 'finance_review', 'executive_review',
            'me_review', 'returned', 'attention', 'warning', 'major', 'action_required',
            'needs_attention', 'submitted', 'dispatched' => self::WARNING,

            'pending', 'draft', 'awaiting', 'awaiting_review', 'caution', 'on_hold',
            'hold', 'queued', 'scheduled', 'not_started', 'unverified' => self::CAUTION,

            'info', 'informational', 'review', 'new', 'received', 'assigned',
            'standby', 'locked', 'open' => self::INFO,

            default => self::NEUTRAL,
        };
    }

    public static function badgeClass(?string $status): string
    {
        return 'tich-badge tich-badge--'.self::for($status);
    }

    public static function statusBadgeClass(?string $status): string
    {
        $tone = self::for($status);

        return 'tich-status-badge is-'.$tone;
    }

    public static function label(?string $status): string
    {
        $raw = trim((string) $status);
        if ($raw === '') {
            return '—';
        }

        return ucwords(str_replace(['_', '-'], ' ', $raw));
    }
}
