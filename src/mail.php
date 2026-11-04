<?php
declare(strict_types=1);

function send_mail(string $to, string $subject, string $body): bool
{
    $from = (string) cfg('mail.from', 'no-reply@quickbooks.cloudcompassai.com');
    if (cfg('mail.mode', 'log') === 'log') {
        file_put_contents(ROOT . '/storage/outbox.log', "To: $to\nSubject: $subject\n\n$body\n---\n", FILE_APPEND | LOCK_EX);
        return true;
    }
    $headers = "From: CloudCompass Books <$from>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers, '-f' . $from);
}
