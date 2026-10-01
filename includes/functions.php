<?php
/**
 * Global Utility Functions
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/validation.php';

/**
 * Safe redirect to a URL.
 *
 * @param string $url
 */
function redirect(string $url): void {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }
    echo '<script>window.location.href = "' . e($url) . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . e($url) . '"></noscript>';
    exit;
}

/**
 * Format timestamp nicely.
 *
 * @param string|null $datetime
 * @param string $format
 * @return string
 */
function format_date(?string $datetime, string $format = 'M d, Y H:i'): string {
    if (empty($datetime)) {
        return '—';
    }
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Throwable $e) {
        return (string)$datetime;
    }
}

/**
 * Human readable time difference (e.g. 5 minutes ago).
 *
 * @param string|null $datetime
 * @return string
 */
function time_ago(?string $datetime): string {
    if (empty($datetime)) {
        return 'Never';
    }
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $m = floor($diff / 60);
        return $m . 'm ago';
    } elseif ($diff < 86400) {
        $h = floor($diff / 3600);
        return $h . 'h ago';
    } elseif ($diff < 2592000) {
        $d = floor($diff / 86400);
        return $d . 'd ago';
    } else {
        return date('M d, Y', $time);
    }
}

/**
 * Render standard badge for status strings.
 *
 * @param string $status
 * @return string
 */
function render_status_badge(string $status): string {
    $status = strtolower($status);
    $styles = [
        'completed'  => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'approved'   => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'active'     => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'won'        => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'success'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'verified'   => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'open'       => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'scheduled'  => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
        'pending'    => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'in_progress'=> 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'processing' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        'answered'   => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        'failed'     => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'rejected'   => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'lost'       => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'banned'     => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'suspended'  => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'cancelled'  => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        'closed'     => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        'expired'    => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        'refunded'   => 'bg-teal-500/10 text-teal-400 border-teal-500/20',
    ];

    $class = $styles[$status] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
    $label = ucwords(str_replace('_', ' ', $status));

    return "<span class=\"inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {$class}\">{$label}</span>";
}

/**
 * Generate a unique random reference string.
 *
 * @param string $prefix
 * @param int $length
 * @return string
 */
function generate_reference(string $prefix = 'TX', int $length = 10): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random = '';
    for ($i = 0; $i < $length; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . '-' . date('ymd') . '-' . $random;
}
