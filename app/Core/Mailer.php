<?php

namespace Core;

class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if (defined('MAIL_HOST') && MAIL_HOST !== '' && defined('MAIL_USERNAME') && MAIL_USERNAME !== '') {
            return self::sendViaSmtp($to, $subject, $body);
        }

        $headers = implode("\r\n", [
            'From: ' . self::fromAddress(),
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ]);

        return @mail($to, self::encodedSubject($subject), $body, $headers);
    }

    private static function sendViaSmtp(string $to, string $subject, string $body): bool
    {
        $host = MAIL_HOST;
        $port = defined('MAIL_PORT') ? (int) MAIL_PORT : 587;
        $timeout = 20;
        $encryption = defined('MAIL_ENCRYPTION') ? strtolower((string) MAIL_ENCRYPTION) : 'tls';
        $transport = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => self::verifyPeer(),
                'verify_peer_name' => self::verifyPeer(),
                'peer_name' => $host,
            ],
        ]);
        $socket = @stream_socket_client("{$transport}://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            error_log("SMTP connection failed: {$errno} {$errstr}");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            self::expect($socket, 220);
            self::command($socket, 'EHLO ' . self::ehloDomain(), 250);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log('SMTP STARTTLS failed.');
                    fclose($socket);
                    return false;
                }
                self::command($socket, 'EHLO ' . self::ehloDomain(), 250);
            }

            self::command($socket, 'AUTH LOGIN', 334);
            self::command($socket, base64_encode(MAIL_USERNAME), 334);
            self::command($socket, base64_encode(MAIL_PASSWORD), 235);
            self::command($socket, 'MAIL FROM:<' . self::fromAddress() . '>', 250);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', 354);

            $message = self::message($to, $subject, $body);
            fwrite($socket, $message . "\r\n.\r\n");
            self::expect($socket, 250);
            self::command($socket, 'QUIT', 221);
            fclose($socket);
            return true;
        } catch (\RuntimeException $exception) {
            error_log($exception->getMessage());
            if (is_resource($socket)) {
                fclose($socket);
            }
            return false;
        }
    }

    private static function message(string $to, string $subject, string $body): string
    {
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'To: ' . $to,
            'From: ' . self::fromName() . ' <' . self::fromAddress() . '>',
            'Subject: ' . self::encodedSubject($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $body);
    }

    private static function command($socket, string $command, int|array $expected): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expected);
    }

    private static function expect($socket, int|array $expected): string
    {
        $expectedCodes = (array) $expected;
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException("SMTP unexpected response: {$response}");
        }

        return $response;
    }

    private static function fromAddress(): string
    {
        return defined('MAIL_FROM') ? MAIL_FROM : 'martellwang@gmail.com';
    }

    private static function fromName(): string
    {
        return defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'NewPay';
    }

    private static function ehloDomain(): string
    {
        $domain = defined('MAIL_EHLO_DOMAIN') ? trim((string) MAIL_EHLO_DOMAIN) : '';
        if ($domain === '' || str_contains($domain, ' ')) {
            return 'localhost';
        }

        return $domain;
    }

    private static function verifyPeer(): bool
    {
        return defined('MAIL_VERIFY_PEER') ? (bool) MAIL_VERIFY_PEER : true;
    }

    private static function encodedSubject(string $subject): string
    {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }
}
