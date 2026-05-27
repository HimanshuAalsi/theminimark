<?php

declare(strict_types=1);

function tm_newsletter_subscribe(PDO $pdo, string $email, ?string $source): array
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Invalid email address'];
    }

    $source = $source !== null && $source !== '' ? substr($source, 0, 64) : null;

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO newsletter_subscribers (email, source) VALUES (:email, :source)'
        );
        $stmt->execute([':email' => $email, ':source' => $source]);
        return ['ok' => true, 'message' => 'Subscribed'];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => true, 'message' => 'Already subscribed'];
        }
        throw $e;
    }
}
