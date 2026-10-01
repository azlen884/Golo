<?php
/**
 * Session & Flash Messages Helper
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Set a flash message for the next request.
 *
 * @param string $type 'success', 'error', 'info', 'warning'
 * @param string $message
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear all flash messages.
 *
 * @return array
 */
function get_flash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Check if there are any flash messages.
 *
 * @return bool
 */
function has_flash(): bool {
    return !empty($_SESSION['flash_messages']);
}

/**
 * Render flash messages as Tailwind alert banners.
 *
 * @return string
 */
function render_flash(): string {
    $messages = get_flash();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="space-y-3 mb-6">';
    foreach ($messages as $msg) {
        $type = $msg['type'];
        $text = e($msg['message']);

        $colors = [
            'success' => 'bg-emerald-950/70 border-emerald-500/40 text-emerald-300',
            'error'   => 'bg-rose-950/70 border-rose-500/40 text-rose-300',
            'warning' => 'bg-amber-950/70 border-amber-500/40 text-amber-300',
            'info'    => 'bg-blue-950/70 border-blue-500/40 text-blue-300',
        ];
        $colorClass = $colors[$type] ?? $colors['info'];

        $html .= "
        <div class=\"flex items-center justify-between p-4 rounded-xl border backdrop-blur-md {$colorClass} transition-all duration-200 animate-fadeIn\">
            <div class=\"flex items-center gap-3\">
                <span class=\"text-sm font-medium\">{$text}</span>
            </div>
            <button type=\"button\" onclick=\"this.parentElement.remove()\" class=\"text-slate-400 hover:text-white transition-colors p-1\">
                <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"></path></svg>
            </button>
        </div>";
    }
    $html .= '</div>';
    return $html;
}
