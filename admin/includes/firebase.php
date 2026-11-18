<?php
/**
 * Tiny Firebase client. Pure PHP (curl + openssl). No composer needed.
 * Uses service account → Realtime Database REST, Identity Toolkit (Auth admin), FCM v1.
 *
 * Public API kept same as old Firestore version:
 *   getDoc / createDoc / setDoc / deleteDoc / query
 * "Doc" = RTDB node. "Collection" = parent node. 'id' = node key.
 */
class FirebaseException extends Exception {}

class Firebase
{
    private array $sa;
    private string $pid;
    private string $dbUrl;
    private string $cacheFile;

    public function __construct(string $saPath, string $projectId, string $storageDir, string $dbUrl)
    {
        if (!is_file($saPath)) {
            throw new FirebaseException('service-account.json not found at: ' . $saPath);
        }
        $this->sa = json_decode(file_get_contents($saPath), true) ?: [];
        if (empty($this->sa['private_key']) || empty($this->sa['client_email'])) {
            throw new FirebaseException('service-account.json is invalid.');
        }
        $this->pid = $projectId;
        $this->dbUrl = rtrim($dbUrl, '/');
        // v2 name: old cache (Firestore scopes) must not be reused.
        $this->cacheFile = rtrim($storageDir, '/') . '/token-cache-rtdb.json';
    }

    // ───────────── OAuth token (cached) ─────────────

    private static function b64(string $d): string
    {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }

    private function token(): string
    {
        if (is_file($this->cacheFile)) {
            $c = json_decode(@file_get_contents($this->cacheFile), true);
            if (!empty($c['token']) && ($c['exp'] ?? 0) > time() + 120) {
                return $c['token'];
            }
        }

        $now = time();
        $unsigned = self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' .
            self::b64(json_encode([
                'iss' => $this->sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.database '
                    . 'https://www.googleapis.com/auth/userinfo.email '
                    . 'https://www.googleapis.com/auth/cloud-platform '
                    . 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

        if (!openssl_sign($unsigned, $sig, $this->sa['private_key'], 'sha256')) {
            throw new FirebaseException('Could not sign JWT (openssl).');
        }

        $res = $this->rawHttp('POST', 'https://oauth2.googleapis.com/token', [
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . self::b64($sig),
        ]));

        $j = json_decode($res['body'], true);
        if ($res['status'] !== 200 || empty($j['access_token'])) {
            throw new FirebaseException('Token error: ' . ($j['error_description'] ?? $res['body']));
        }

        @file_put_contents($this->cacheFile, json_encode([
            'token' => $j['access_token'],
            'exp' => $now + (int)($j['expires_in'] ?? 3500),
        ]), LOCK_EX);

        return $j['access_token'];
    }

    // ───────────── HTTP ─────────────

    private function rawHttp(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $out = curl_exec($ch);
        if ($out === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new FirebaseException('Network error: ' . $err);
        }
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => (string)$out];
    }

    /**
     * @param array $extraHeaders extra raw headers
     * @return array{0:int,1:mixed} [status, decoded JSON (may be null)]
     */
    private function api(string $method, string $url, $json = null, array $extraHeaders = []): array
    {
        $res = $this->rawHttp($method, $url, array_merge([
            'Authorization: Bearer ' . $this->token(),
            'Content-Type: application/json',
        ], $extraHeaders), $json === null ? null : json_encode($json, JSON_UNESCAPED_UNICODE));

        $data = json_decode($res['body'], true);

        if ($res['status'] >= 400) {
            $msg = 'HTTP ' . $res['status'];
            if (is_array($data) && isset($data['error'])) {
                $msg = is_array($data['error']) ? ($data['error']['message'] ?? $msg) : (string)$data['error'];
            }
            throw new FirebaseException($msg, $res['status']);
        }
        return [$res['status'], $data];
    }

    // ───────────── Realtime Database ─────────────

    /** Timestamps → ms since epoch (matches ServerValue.timestamp in app). Nulls dropped. */
    private static function enc($v)
    {
        if ($v instanceof DateTimeInterface) return $v->getTimestamp() * 1000;
        if (is_array($v)) {
            $out = [];
            foreach ($v as $k => $x) $out[$k] = self::enc($x);
            return $out;
        }
        return $v;
    }

    private function url(string $path): string
    {
        $segs = array_map('rawurlencode', array_filter(explode('/', $path), fn($s) => $s !== ''));
        return $this->dbUrl . '/' . implode('/', $segs) . '.json';
    }

    /** Returns assoc array (with 'id') or null if missing. */
    public function getDoc(string $path): ?array
    {
        [, $d] = $this->api('GET', $this->url($path));
        if (!is_array($d)) return null;
        $d['id'] = basename($path);
        return $d;
    }

    /**
     * Create. With $id → throws code 409 if it already exists (race-safe, ETag precondition).
     * Without $id → auto push-id.
     */
    public function createDoc(string $collection, array $data, ?string $id = null): array
    {
        $data = self::enc($data);
        if ($id !== null && $id !== '') {
            try {
                $this->api('PUT', $this->url($collection . '/' . $id), $data, ['if-match: null_etag']);
            } catch (FirebaseException $e) {
                if ($e->getCode() === 412) throw new FirebaseException('Already exists', 409);
                throw $e;
            }
            $data['id'] = $id;
            return $data;
        }
        [, $r] = $this->api('POST', $this->url($collection), $data);
        $data['id'] = is_array($r) ? (string)($r['name'] ?? '') : '';
        return $data;
    }

    /** Create or merge fields (only given keys are touched). */
    public function setDoc(string $path, array $data): void
    {
        $this->api('PATCH', $this->url($path), self::enc($data));
    }

    public function deleteDoc(string $path): void
    {
        $this->api('DELETE', $this->url($path));
    }

    /** List a collection, sorted by field (default newest first). Sorting done here → no .indexOn needed. */
    public function query(string $collection, ?string $orderBy = null, bool $desc = true, int $limit = 500): array
    {
        [, $d] = $this->api('GET', $this->url($collection));
        $out = [];
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                if (!is_array($v)) continue;
                $v['id'] = (string)$k;
                $out[] = $v;
            }
        }
        if ($orderBy) {
            usort($out, function ($a, $b) use ($orderBy, $desc) {
                $x = $a[$orderBy] ?? 0;
                $y = $b[$orderBy] ?? 0;
                return $desc ? ($y <=> $x) : ($x <=> $y);
            });
        }
        return array_slice($out, 0, $limit);
    }

    // ───────────── Firebase Auth (admin) ─────────────

    private function idt(): string
    {
        return 'https://identitytoolkit.googleapis.com/v1/projects/' . $this->pid;
    }

    public function authSetDisabled(string $uid, bool $disabled): void
    {
        $this->api('POST', $this->idt() . '/accounts:update', [
            'localId' => $uid,
            'disableUser' => $disabled,
        ]);
    }

    /** Create email+password user in Firebase Authentication. Returns uid. */
    public function authCreateUser(string $email, string $password, string $name): string
    {
        [, $r] = $this->api('POST', $this->idt() . '/accounts', [
            'email' => $email,
            'password' => $password,
            'displayName' => $name,
            'emailVerified' => true,
        ]);
        return is_array($r) ? (string)($r['localId'] ?? '') : '';
    }

    public function authFindByEmail(string $email): ?string
    {
        [, $r] = $this->api('POST', $this->idt() . '/accounts:lookup', ['email' => [$email]]);
        return is_array($r) ? ($r['users'][0]['localId'] ?? null) : null;
    }

    public function authDelete(string $uid): void
    {
        try {
            $this->api('POST', $this->idt() . '/accounts:delete', ['localId' => $uid]);
        } catch (FirebaseException $e) {
            // Already gone = fine
            if (stripos($e->getMessage(), 'USER_NOT_FOUND') === false) throw $e;
        }
    }

    // ───────────── FCM push ─────────────

    /** FCM data values must all be strings. */
    private static function fcmData(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if ($v === null) continue;
            $out[(string)$k] = (string)$v;
        }
        return $out;
    }

    private function fcmSend(array $target, string $title, string $body, array $data): void
    {
        $msg = $target + [
            'notification' => ['title' => $title, 'body' => $body],
            'android' => ['priority' => 'HIGH'],
        ];
        $d = self::fcmData($data);
        if ($d) $msg['data'] = $d;
        $this->api('POST', 'https://fcm.googleapis.com/v1/projects/' . $this->pid . '/messages:send', [
            'message' => $msg,
        ]);
    }

    /** Send to everyone subscribed to a topic. $data e.g. ['screen' => 'privacy']. */
    public function pushToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $this->fcmSend(['topic' => $topic], $title, $body, $data);
    }

    /** Send to one device token. Throws FirebaseException (code 404 = token no longer valid). */
    public function pushToToken(string $token, string $title, string $body, array $data = []): void
    {
        $this->fcmSend(['token' => $token], $title, $body, $data);
    }
}