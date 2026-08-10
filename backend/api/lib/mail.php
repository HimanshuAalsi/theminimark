<?php

declare(strict_types=1);

/**
 * @return array{enabled: bool, from_email: string, from_name: string}
 */
function tm_mail_settings(): array
{
    $cfg = tm_config()['mail'] ?? [];

    return [
        'enabled' => !empty($cfg['enabled']),
        'from_email' => trim((string) ($cfg['from_email'] ?? 'orders@theminimark.com')),
        'from_name' => trim((string) ($cfg['from_name'] ?? 'The Minimark')) ?: 'The Minimark',
    ];
}

function tm_mail_send(string $to, string $subject, string $htmlBody): bool
{
    $s = tm_mail_settings();
    if (!$s['enabled'] || $to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $s['from_name'] . ' <' . $s['from_email'] . '>',
    ];

    return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}

/**
 * @param array<string, mixed> $order
 */
function tm_mail_order_event(PDO $pdo, int $orderId, string $event): void
{
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $st->execute([$orderId]);
    $order = $st->fetch(PDO::FETCH_ASSOC);
    if ($order === false) {
        return;
    }

    $email = (string) $order['customer_email'];
    $name = (string) ($order['customer_name'] ?? 'Customer');
    $orderNum = (int) $order['id'];
    $total = number_format((float) $order['subtotal'], 2);

    $subjects = [
        'placed' => "Order #{$orderNum} received — The Minimark",
        'paid' => "Payment confirmed — Order #{$orderNum}",
        'shipped' => "Your order #{$orderNum} has shipped",
        'delivered' => "Order #{$orderNum} delivered",
        'refunded' => "Refund processed — Order #{$orderNum}",
    ];

    $bodies = [
        'placed' => "<p>Hi {$name},</p><p>We received your order <strong>#{$orderNum}</strong> (₹{$total}). We'll notify you when payment is confirmed.</p><p>Thank you for shopping with The Minimark!</p>",
        'paid' => "<p>Hi {$name},</p><p>Payment of <strong>₹{$total}</strong> for order <strong>#{$orderNum}</strong> is confirmed. We're preparing your items.</p>",
        'shipped' => "<p>Hi {$name},</p><p>Great news — order <strong>#{$orderNum}</strong> is on its way!</p>",
        'delivered' => "<p>Hi {$name},</p><p>Order <strong>#{$orderNum}</strong> has been marked delivered. We hope you love it!</p>",
        'refunded' => "<p>Hi {$name},</p><p>A refund for order <strong>#{$orderNum}</strong> has been initiated. It may take 5–7 business days to reflect.</p>",
    ];

    if (!isset($subjects[$event], $bodies[$event])) {
        return;
    }

    tm_mail_send($email, $subjects[$event], $bodies[$event]);
}
