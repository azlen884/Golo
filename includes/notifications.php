<?php
/**
 * Notifications Management
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Send notification to a specific user.
 *
 * @param int $userId
 * @param string $title
 * @param string $message
 * @param string $type ('info', 'success', 'warning', 'danger', 'system')
 * @param string|null $actionUrl
 * @return bool
 */
function send_notification(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): bool {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, action_url, created_at)
            VALUES (:uid, :title, :msg, :type, 0, :url, NOW())
        ");
        return $stmt->execute([
            ':uid'   => $userId,
            ':title' => $title,
            ':msg'   => $message,
            ':type'  => $type,
            ':url'   => $actionUrl
        ]);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Broadcast notification to all active users.
 *
 * @param string $title
 * @param string $message
 * @param string $type
 * @param string|null $actionUrl
 * @return bool
 */
function broadcast_notification(string $title, string $message, string $type = 'system', ?string $actionUrl = null): bool {
    try {
        $pdo = get_db();
        // Insert as global broadcast (user_id = NULL)
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, action_url, created_at)
            VALUES (NULL, :title, :msg, :type, 0, :url, NOW())
        ");
        return $stmt->execute([
            ':title' => $title,
            ':msg'   => $message,
            ':type'  => $type,
            ':url'   => $actionUrl
        ]);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Get count of unread notifications for a user.
 *
 * @param int $userId
 * @return int
 */
function get_unread_notifications_count(int $userId): int {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE (user_id = :uid OR user_id IS NULL) AND is_read = 0
        ");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Get notifications for a user.
 *
 * @param int $userId
 * @param int $limit
 * @return array
 */
function get_user_notifications(int $userId, int $limit = 30): array {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT * FROM notifications
            WHERE user_id = :uid OR user_id IS NULL
            ORDER BY created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Mark notification as read.
 *
 * @param int $notificationId
 * @param int $userId
 * @return bool
 */
function mark_notification_read(int $notificationId, int $userId): bool {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            UPDATE notifications SET is_read = 1
            WHERE id = :id AND (user_id = :uid OR user_id IS NULL)
        ");
        return $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
    } catch (Throwable $e) {
        return false;
    }
}
