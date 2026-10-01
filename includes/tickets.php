<?php
/**
 * Support Ticket System
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notifications.php';

/**
 * Open a new support ticket.
 *
 * @param int $userId
 * @param string $subject
 * @param string $department
 * @param string $priority
 * @param string $initialMessage
 * @return array
 */
function create_support_ticket(int $userId, string $subject, string $department, string $priority, string $initialMessage): array {
    $subject = trim($subject);
    $initialMessage = trim($initialMessage);

    if (empty($subject) || empty($initialMessage)) {
        return ['success' => false, 'message' => 'Subject and message are required.'];
    }

    $validPriorities = ['low', 'medium', 'high', 'urgent'];
    if (!in_array($priority, $validPriorities)) {
        $priority = 'medium';
    }

    $ticketCode = generate_reference('TCK', 8);
    $pdo = get_db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (ticket_code, user_id, department, subject, priority, status, last_reply_at, created_at)
            VALUES (:code, :uid, :dept, :subj, :pri, 'open', NOW(), NOW())
        ");
        $stmt->execute([
            ':code' => $ticketCode,
            ':uid'  => $userId,
            ':dept' => $department,
            ':subj' => $subject,
            ':pri'  => $priority
        ]);
        $ticketId = (int)$pdo->lastInsertId();

        $mStmt = $pdo->prepare("
            INSERT INTO support_messages (ticket_id, sender_type, sender_id, message, created_at)
            VALUES (:tid, 'user', :sid, :msg, NOW())
        ");
        $mStmt->execute([
            ':tid' => $ticketId,
            ':sid' => $userId,
            ':msg' => $initialMessage
        ]);

        $pdo->commit();

        send_notification($userId, 'Support Ticket Created', "Your ticket #{$ticketCode} has been logged. Our helpdesk team will respond promptly.", 'info', "/user/ticket.php?id={$ticketId}");

        return [
            'success'     => true,
            'message'     => 'Support ticket submitted successfully.',
            'ticket_id'   => $ticketId,
            'ticket_code' => $ticketCode
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Add a reply message to a support ticket.
 *
 * @param int $ticketId
 * @param string $senderType ('user' or 'admin')
 * @param int $senderId
 * @param string $message
 * @return array
 */
function add_ticket_message(int $ticketId, string $senderType, int $senderId, string $message): array {
    $message = trim($message);
    if (empty($message)) {
        return ['success' => false, 'message' => 'Reply message cannot be empty.'];
    }

    $pdo = get_db();
    $pdo->beginTransaction();

    try {
        $tStmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = :id FOR UPDATE");
        $tStmt->execute([':id' => $ticketId]);
        $ticket = $tStmt->fetch();

        if (!$ticket) {
            throw new Exception("Ticket not found.");
        }

        // Insert message
        $mStmt = $pdo->prepare("
            INSERT INTO support_messages (ticket_id, sender_type, sender_id, message, created_at)
            VALUES (:tid, :stype, :sid, :msg, NOW())
        ");
        $mStmt->execute([
            ':tid'   => $ticketId,
            ':stype' => $senderType,
            ':sid'   => $senderId,
            ':msg'   => $message
        ]);

        // Update ticket status
        $newStatus = ($senderType === 'admin') ? 'answered' : 'in_progress';
        $uStmt = $pdo->prepare("
            UPDATE support_tickets
            SET status = :status, last_reply_at = NOW(), updated_at = NOW()
            WHERE id = :id
        ");
        $uStmt->execute([':status' => $newStatus, ':id' => $ticketId]);

        $pdo->commit();

        if ($senderType === 'admin') {
            send_notification(
                (int)$ticket['user_id'],
                'Ticket Update Received',
                "Our team replied to your ticket #{$ticket['ticket_code']}.",
                'info',
                "/user/ticket.php?id={$ticketId}"
            );
        }

        return ['success' => true, 'message' => 'Reply posted successfully.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
