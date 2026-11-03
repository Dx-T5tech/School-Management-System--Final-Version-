<?php

declare(strict_types=1);

function admissionSmtpRequiredSetting(string $name): string
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        throw new RuntimeException(sprintf('%s is not configured.', $name));
    }

    return trim($value);
}

function admissionSmtpReadResponse($socket, array $acceptedCodes): string
{
    $response = '';
    do {
        $line = fgets($socket, 515);
        if ($line === false) {
            throw new RuntimeException('The SMTP server closed the connection unexpectedly.');
        }
        $response .= $line;
    } while (isset($line[3]) && $line[3] === '-');

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $acceptedCodes, true)) {
        throw new RuntimeException('The SMTP server rejected an enrollment email command.');
    }

    return $response;
}

function admissionSmtpWrite($socket, string $data): void
{
    $offset = 0;
    $length = strlen($data);
    while ($offset < $length) {
        $written = fwrite($socket, substr($data, $offset));
        if ($written === false || $written === 0) {
            throw new RuntimeException('The SMTP command could not be sent.');
        }
        $offset += $written;
    }
}

function admissionSmtpCommand($socket, string $command, array $acceptedCodes): string
{
    admissionSmtpWrite($socket, $command . "\r\n");
    return admissionSmtpReadResponse($socket, $acceptedCodes);
}

function admissionSendVerificationEmail(string $recipient, string $firstName, string $verificationUrl): void
{
    applicationSendPlainTextEmail(
        $recipient,
        'Verify your Fullbright College enrollment email',
        implode("\r\n", [
            'Hello ' . $firstName . ',',
            '',
            'Use the link below to verify your email and continue your student application. This link expires in 24 hours.',
            '',
            $verificationUrl,
            '',
            'If you did not start this application, you can ignore this message.',
        ])
    );
}

function applicationSendPlainTextEmail(string $recipient, string $subject, string $body): void
{
    $host = admissionSmtpRequiredSetting('SMTP_HOST');
    $portText = admissionSmtpRequiredSetting('SMTP_PORT');
    $username = admissionSmtpRequiredSetting('SMTP_USERNAME');
    $password = admissionSmtpRequiredSetting('SMTP_PASSWORD');
    $fromAddress = admissionSmtpRequiredSetting('SMTP_FROM_EMAIL');
    $fromName = getenv('SMTP_FROM_NAME') ?: 'Fullbright College';
    $encryption = strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls');
    $port = filter_var($portText, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);

    if ($port === false || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('The SMTP port or email address is invalid.');
    }
    if (!in_array($encryption, ['tls', 'ssl'], true)) {
        throw new RuntimeException('SMTP_ENCRYPTION must be tls or ssl.');
    }

    $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
    $socket = stream_socket_client(
        $transport . $host . ':' . $port,
        $errorNumber,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT,
        stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
            ],
        ])
    );
    if ($socket === false) {
        throw new RuntimeException(sprintf('SMTP connection failed (%d): %s', $errorNumber, $errorMessage));
    }

    stream_set_timeout($socket, 15);
    try {
        admissionSmtpReadResponse($socket, [220]);
        $hostName = gethostname() ?: 'localhost';
        admissionSmtpCommand($socket, 'EHLO ' . preg_replace('/[^A-Za-z0-9.-]/', '', $hostName), [250]);
        if ($encryption === 'tls') {
            admissionSmtpCommand($socket, 'STARTTLS', [220]);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new RuntimeException('SMTP TLS negotiation failed.');
            }
            admissionSmtpCommand($socket, 'EHLO ' . preg_replace('/[^A-Za-z0-9.-]/', '', $hostName), [250]);
        }

        admissionSmtpCommand($socket, 'AUTH LOGIN', [334]);
        admissionSmtpCommand($socket, base64_encode($username), [334]);
        admissionSmtpCommand($socket, base64_encode($password), [235]);
        admissionSmtpCommand($socket, 'MAIL FROM:<' . $fromAddress . '>', [250]);
        admissionSmtpCommand($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        admissionSmtpCommand($socket, 'DATA', [354]);

        $encodedName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $message = implode("\r\n", [
            'From: ' . $encodedName . ' <' . $fromAddress . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . $subject,
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            preg_replace('/(?m)^\./', '..', $body),
        ]);
        admissionSmtpWrite($socket, $message . "\r\n.\r\n");
        admissionSmtpReadResponse($socket, [250]);
        admissionSmtpCommand($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}
