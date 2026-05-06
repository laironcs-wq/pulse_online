<?php

declare(strict_types=1);

const WS_HOST = '0.0.0.0';
const WS_PORT = 8081;
const BACKEND_HOST = '0.0.0.0';
const BACKEND_PORT = 8091;
define('WS_TLS_ENABLED', (getenv('PULSE_WS_TLS_ENABLED') ?: '0') === '1');
define('WS_CERT_FILE', __DIR__ . '/certs/ws-cert.pem');
define('WS_KEY_FILE', __DIR__ . '/certs/ws-key.pem');
define('WS_KEY_PASSPHRASE', (string) (getenv('PULSE_WS_KEY_PASSPHRASE') ?: ''));

set_time_limit(0);
error_reporting(E_ALL);

if (WS_TLS_ENABLED) {
    if (!is_file(WS_CERT_FILE) || !is_file(WS_KEY_FILE)) {
        fwrite(STDERR, "TLS enabled but certificate files not found:\n" .
            "CERT: " . WS_CERT_FILE . "\nKEY: " . WS_KEY_FILE . "\n");
        exit(1);
    }
    $sslOptions = [
        'local_cert' => WS_CERT_FILE,
        'local_pk' => WS_KEY_FILE,
        'allow_self_signed' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
    ];
    if (WS_KEY_PASSPHRASE !== '') {
        $sslOptions['passphrase'] = WS_KEY_PASSPHRASE;
    }
    $wsContext = stream_context_create(['ssl' => $sslOptions]);
    $wsServer = stream_socket_server(
        'tls://' . WS_HOST . ':' . WS_PORT,
        $wsErrNo,
        $wsErrStr,
        STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        $wsContext
    );
} else {
    $wsServer = stream_socket_server(
        'tcp://' . WS_HOST . ':' . WS_PORT,
        $wsErrNo,
        $wsErrStr
    );
}
if ($wsServer === false) {
    fwrite(STDERR, "WS server start failed: {$wsErrStr} ({$wsErrNo})\n");
    exit(1);
}
stream_set_blocking($wsServer, false);

$backendServer = stream_socket_server(
    'tcp://' . BACKEND_HOST . ':' . BACKEND_PORT,
    $beErrNo,
    $beErrStr
);
if ($backendServer === false) {
    fwrite(STDERR, "Backend channel start failed: {$beErrStr} ({$beErrNo})\n");
    exit(1);
}
stream_set_blocking($backendServer, false);

$clients = [];
$socketToClient = [];
$backendPeers = [];

echo "Pulse WS server listening on " . (WS_TLS_ENABLED ? 'wss://' : 'ws://') . WS_HOST . ':' . WS_PORT . PHP_EOL;
echo "Backend event channel on tcp://" . BACKEND_HOST . ':' . BACKEND_PORT . PHP_EOL;

while (true) {
    $read = [$wsServer, $backendServer];
    foreach ($clients as $client) {
        $read[] = $client['socket'];
    }
    foreach ($backendPeers as $peer) {
        $read[] = $peer['socket'];
    }

    $write = null;
    $except = null;
    $changed = @stream_select($read, $write, $except, 0, 200000);
    if ($changed === false) {
        usleep(100000);
        continue;
    }

    foreach ($read as $sock) {
        if ($sock === $wsServer) {
            $clientSock = @stream_socket_accept($wsServer, 0);
            if ($clientSock !== false) {
                stream_set_blocking($clientSock, false);
                $id = (int) $clientSock;
                $clients[$id] = [
                    'id' => $id,
                    'socket' => $clientSock,
                    'handshake_done' => false,
                    'handshake_buffer' => '',
                    'buffer' => '',
                    'user_id' => null,
                    'watch_chat' => null,
                ];
                $socketToClient[$id] = $id;
            }
            continue;
        }

        if ($sock === $backendServer) {
            $peerSock = @stream_socket_accept($backendServer, 0);
            if ($peerSock !== false) {
                stream_set_blocking($peerSock, false);
                $peerId = (int) $peerSock;
                $backendPeers[$peerId] = [
                    'id' => $peerId,
                    'socket' => $peerSock,
                    'buffer' => '',
                ];
            }
            continue;
        }

        $id = (int) $sock;
        if (isset($socketToClient[$id])) {
            handleClientRead($clients, $socketToClient, $id);
        } elseif (isset($backendPeers[$id])) {
            handleBackendRead($backendPeers, $id, $clients);
        }
    }

}

function handleClientRead(array &$clients, array &$socketToClient, int $id): void
{
    if (!isset($clients[$id])) {
        return;
    }
    $sock = $clients[$id]['socket'];
    $chunk = @fread($sock, 8192);
    if ($chunk === '' || $chunk === false) {
        if (feof($sock)) {
            disconnectClient($clients, $socketToClient, $id);
        }
        return;
    }

    if (!$clients[$id]['handshake_done']) {
        $clients[$id]['handshake_buffer'] .= $chunk;
        $headerEndPos = strpos($clients[$id]['handshake_buffer'], "\r\n\r\n");
        if ($headerEndPos === false) {
            return;
        }

        $rawHandshake = substr($clients[$id]['handshake_buffer'], 0, $headerEndPos + 4);
        $remaining = substr($clients[$id]['handshake_buffer'], $headerEndPos + 4);
        if (!performHandshake($sock, $rawHandshake)) {
            disconnectClient($clients, $socketToClient, $id);
            return;
        }
        $clients[$id]['handshake_done'] = true;
        $clients[$id]['handshake_buffer'] = '';
        if ($remaining !== '') {
            $clients[$id]['buffer'] .= $remaining;
        }
    } else {
        $clients[$id]['buffer'] .= $chunk;
    }

    $messages = decodeWsFramesFromBuffer($clients[$id]['buffer']);
    foreach ($messages as $message) {
        processClientMessage($clients, $id, $message);
    }
}

function handleBackendRead(array &$backendPeers, int $peerId, array &$clients): void
{
    if (!isset($backendPeers[$peerId])) {
        return;
    }
    $sock = $backendPeers[$peerId]['socket'];
    $chunk = @fread($sock, 8192);
    if ($chunk === '' || $chunk === false) {
        if (feof($sock)) {
            @fclose($sock);
            unset($backendPeers[$peerId]);
        }
        return;
    }

    $backendPeers[$peerId]['buffer'] .= $chunk;
    while (($pos = strpos($backendPeers[$peerId]['buffer'], "\n")) !== false) {
        $line = trim(substr($backendPeers[$peerId]['buffer'], 0, $pos));
        $backendPeers[$peerId]['buffer'] = substr($backendPeers[$peerId]['buffer'], $pos + 1);
        if ($line === '') {
            continue;
        }
        $event = json_decode($line, true);
        if (is_array($event)) {
            dispatchEvent($clients, $event);
        }
    }
}

function processClientMessage(array &$clients, int $clientId, string $raw): void
{
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return;
    }

    $type = $payload['type'] ?? '';
    if ($type === 'auth') {
        $userId = trim((string) ($payload['user_id'] ?? ''));
        if ($userId === '') {
            return;
        }
        $clients[$clientId]['user_id'] = $userId;
        sendToClient($clients[$clientId]['socket'], [
            'type' => 'auth_ok',
            'user_id' => $userId,
            'timestamp' => time(),
        ]);
        broadcastPresence($clients, $userId, true, $clientId);
        return;
    }

    if ($type === 'watch_chat') {
        $chatId = trim((string) ($payload['chat_id'] ?? ''));
        $clients[$clientId]['watch_chat'] = $chatId !== '' ? $chatId : null;
        return;
    }

    if ($type === 'typing' || $type === 'rtc_signal') {
        $fromUserId = $clients[$clientId]['user_id'] ?? null;
        $toUserId = trim((string) ($payload['to_user_id'] ?? ''));
        if ($fromUserId === null || $toUserId === '') {
            return;
        }
        $forward = $payload;
        $forward['from_user_id'] = $fromUserId;
        $forward['recipient_ids'] = [$toUserId];
        $forward['timestamp'] = time();
        dispatchEvent($clients, $forward);
    }
}

function dispatchEvent(array &$clients, array $event): void
{
    $recipientIds = normalizeIds($event['recipient_ids'] ?? []);
    $chatId = isset($event['chat_id']) ? trim((string) $event['chat_id']) : '';
    $isTargetedByChat = $chatId !== '' && empty($recipientIds);

    foreach ($clients as $client) {
        if (!$client['handshake_done']) {
            continue;
        }
        $userId = $client['user_id'] ?? null;
        if ($userId === null) {
            continue;
        }

        if (!empty($recipientIds) && !in_array($userId, $recipientIds, true)) {
            continue;
        }

        if ($isTargetedByChat) {
            $watchChat = $client['watch_chat'] ?? null;
            if ($watchChat !== $chatId) {
                continue;
            }
        }

        sendToClient($client['socket'], $event);
    }
}

function normalizeIds($ids): array
{
    if (!is_array($ids)) {
        return [];
    }
    $normalized = [];
    foreach ($ids as $id) {
        $value = trim((string) $id);
        if ($value !== '') {
            $normalized[] = $value;
        }
    }
    return array_values(array_unique($normalized));
}

function performHandshake($socket, string $request): bool
{
    if (!preg_match('/Sec-WebSocket-Key:\s*(.+)\r\n/i', $request, $matches)) {
        return false;
    }
    $key = trim($matches[1]);
    if ($key === '') {
        return false;
    }

    $accept = base64_encode(
        sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)
    );

    $response = "HTTP/1.1 101 Switching Protocols\r\n"
        . "Upgrade: websocket\r\n"
        . "Connection: Upgrade\r\n"
        . "Sec-WebSocket-Accept: {$accept}\r\n\r\n";

    return @fwrite($socket, $response) !== false;
}

function decodeWsFramesFromBuffer(string &$buffer): array
{
    $messages = [];
    $offset = 0;
    $length = strlen($buffer);

    while ($offset + 2 <= $length) {
        $b1 = ord($buffer[$offset]);
        $b2 = ord($buffer[$offset + 1]);
        $fin = ($b1 & 0x80) === 0x80;
        $opcode = $b1 & 0x0F;
        $masked = ($b2 & 0x80) === 0x80;
        $payloadLen = $b2 & 0x7F;
        $frameHead = 2;

        if ($payloadLen === 126) {
            if ($offset + 4 > $length) {
                break;
            }
            $payloadLen = unpack('n', substr($buffer, $offset + 2, 2))[1];
            $frameHead = 4;
        } elseif ($payloadLen === 127) {
            if ($offset + 10 > $length) {
                break;
            }
            $parts = unpack('N2', substr($buffer, $offset + 2, 8));
            $payloadLen = ($parts[1] << 32) + $parts[2];
            $frameHead = 10;
        }

        $maskLen = $masked ? 4 : 0;
        $frameSize = $frameHead + $maskLen + $payloadLen;
        if ($offset + $frameSize > $length) {
            break;
        }

        $payload = substr($buffer, $offset + $frameHead + $maskLen, $payloadLen);
        if ($masked) {
            $mask = substr($buffer, $offset + $frameHead, 4);
            $decoded = '';
            for ($i = 0; $i < $payloadLen; $i++) {
                $decoded .= $payload[$i] ^ $mask[$i % 4];
            }
            $payload = $decoded;
        }

        if ($opcode === 0x1 && $fin) {
            $messages[] = $payload;
        } elseif ($opcode === 0x8) {
            break;
        }

        $offset += $frameSize;
    }

    if ($offset > 0) {
        $buffer = (string) substr($buffer, $offset);
    }

    return $messages;
}

function encodeWsFrame(string $payload): string
{
    $len = strlen($payload);
    $frame = chr(0x81);

    if ($len <= 125) {
        $frame .= chr($len);
    } elseif ($len <= 65535) {
        $frame .= chr(126) . pack('n', $len);
    } else {
        $frame .= chr(127) . pack('NN', 0, $len);
    }
    return $frame . $payload;
}

function sendToClient($socket, array $payload): void
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return;
    }
    @fwrite($socket, encodeWsFrame($json));
}

function disconnectClient(array &$clients, array &$socketToClient, int $id): void
{
    if (!isset($clients[$id])) {
        return;
    }
    $userId = $clients[$id]['user_id'] ?? null;
    @fclose($clients[$id]['socket']);
    unset($clients[$id], $socketToClient[$id]);

    if ($userId !== null && !isUserConnected($clients, $userId)) {
        broadcastPresence($clients, $userId, false, null);
    }
}

function isUserConnected(array $clients, string $userId): bool
{
    foreach ($clients as $client) {
        if (($client['user_id'] ?? null) === $userId) {
            return true;
        }
    }
    return false;
}

function broadcastPresence(array &$clients, string $userId, bool $isOnline, ?int $exceptClientId): void
{
    $event = [
        'type' => 'user_presence',
        'user_id' => $userId,
        'is_online' => $isOnline,
        'timestamp' => time(),
    ];

    foreach ($clients as $clientId => $client) {
        if (!$client['handshake_done'] || empty($client['user_id'])) {
            continue;
        }
        if ($exceptClientId !== null && $clientId === $exceptClientId) {
            continue;
        }
        sendToClient($client['socket'], $event);
    }
}
