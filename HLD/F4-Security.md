# F4 — Security

> **Section:** Observability and Security | **Level:** Intermediate → Advanced | **Interview Frequency:** ★★★★★

---

## 1. Concept Definition

**Beginner:** Security in system design means protecting your system from unauthorized access, data theft, and attacks. Common topics: authentication (who are you?), authorization (what can you do?), encryption, and preventing common attack patterns (SQL injection, XSS, CSRF).

**Technical:** System design security covers: authentication (JWT, sessions, OAuth2/OIDC), authorization (RBAC, ABAC), transport security (TLS), data encryption (at rest, in transit), and protection against OWASP Top 10 vulnerabilities.

---

## 2. Real-World Analogy

**Hotel security layers:**
1. **Front door lock** = TLS (transport encryption — no one can eavesdrop in transit)
2. **Check-in ID verification** = Authentication (prove who you are)
3. **Room key** = JWT/session token (proves you checked in, gives you access to your room)
4. **Floor access card** = Authorization (cleaning staff can't access executive floor)
5. **Safe in room** = Encryption at rest (even hotel staff can't read your documents)

---

## 3. Visual Diagram

```
AUTHENTICATION FLOWS:

SESSION-BASED (traditional):
User --[login: user+pass]--> Server
Server --[create session]--> Session Store (Redis)
Server --[Set-Cookie: session_id=abc123]--> User
User --[Cookie: session_id=abc123]--> Server (subsequent requests)
Server --[validate session_id in Redis]--> Allow/Deny

JWT (stateless):
User --[login: user+pass]--> Auth Server
Auth Server --[sign JWT with secret/private key]--> JWT token
User --[Authorization: Bearer <jwt>]--> Any Server
Server --[verify JWT signature (no DB lookup)]--> Allow/Deny (stateless)

OAUTH2 / OIDC (third-party login):
User --> App --> "Login with Google"
    --> Google Auth Server --> User consents
    --> Authorization Code --> App backend
    --> App exchanges code for tokens
    --> Access Token (for Google APIs) + ID Token (user info)

JWT STRUCTURE: header.payload.signature
Header:  {"alg":"RS256","typ":"JWT"}
Payload: {"sub":"user_123","role":"admin","exp":1700000000,"iat":1699996400}
Signature: RS256(base64(header) + "." + base64(payload), private_key)
```

---

## 4. Deep Technical Explanation

### JWT vs Sessions

| Aspect | JWT | Session |
|--------|-----|---------|
| Storage | Client (stateless) | Server (session store) |
| Scalability | Perfect (no shared state) | Needs sticky sessions or Redis |
| Revocation | Hard (must wait for expiry) | Easy (delete from store) |
| Size | Larger (~200 bytes) | Small (just session ID) |
| Use case | Microservices, mobile | Monolith, web apps |

### JWT Security Best Practices
1. Use RS256 (asymmetric) not HS256 (symmetric) for multi-service systems — public key verification, private key only on auth server
2. Short expiry: 15 minutes access token + long-lived refresh token
3. Store access token in memory (not localStorage — XSS risk), store refresh token in HttpOnly cookie
4. Validate: signature, expiry (exp), issuer (iss), audience (aud)
5. Never store sensitive data in JWT payload — it's base64 encoded, not encrypted (anyone can read it)

### OWASP Top 10 (2021)
1. **Broken Access Control** — users accessing other users' data
2. **Cryptographic Failures** — weak encryption, plaintext passwords
3. **Injection** (SQL, LDAP, Command) — untrusted input interpreted as code
4. **Insecure Design** — security not built in from the start
5. **Security Misconfiguration** — default passwords, open S3 buckets, debug mode in prod
6. **Vulnerable Components** — outdated packages with known CVEs
7. **Authentication Failures** — weak passwords, no MFA, exposed tokens
8. **Integrity Failures** — unsigned software updates, insecure deserialization
9. **Security Logging Failures** — no audit trail of sensitive actions
10. **SSRF** (Server-Side Request Forgery) — tricking server to make requests to internal services

### Encryption
- **In transit:** TLS 1.3 (HTTPS, mutual TLS for service-to-service)
- **At rest:** AES-256 for sensitive database columns or full disk encryption
- **Key management:** AWS KMS, HashiCorp Vault — never hardcode keys
- **Password hashing:** Argon2id (winner of Password Hashing Competition), bcrypt — NEVER MD5/SHA1

---

## 5. Code Example

```php
// SQL injection prevention -- always use prepared statements
// INSECURE (SQL injection):
// $query = "SELECT * FROM users WHERE email = '$email'";  // NEVER do this

// SECURE: parameterized query (user input never interpolated into SQL)
class UserRepository {
    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT id, name, email FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    // INSECURE: dynamic ORDER BY (injection risk for identifiers)
    // SECURE: whitelist allowed columns
    public function findAll(string $orderBy = 'created_at', string $direction = 'desc'): array {
        $allowedColumns    = ['id', 'name', 'email', 'created_at'];
        $allowedDirections = ['asc', 'desc'];
        
        if (!in_array($orderBy, $allowedColumns, true)) {
            throw new InvalidArgumentException("Invalid sort column");
        }
        if (!in_array($direction, $allowedDirections, true)) {
            throw new InvalidArgumentException("Invalid sort direction");
        }
        
        // Identifier cannot be parameterized -- whitelist is the safe approach
        $stmt = $this->pdo->query("SELECT * FROM users ORDER BY {$orderBy} {$direction}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Password hashing with Argon2id
class AuthService {
    public function register(string $email, string $plainPassword): void {
        // Hash with Argon2id (PHP 7.2+)
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,   // 64 MB
            'time_cost'   => 4,       // 4 iterations
            'threads'     => 3,       // 3 threads
        ]);
        
        $this->userRepository->create($email, $hash);
    }
    
    public function login(string $email, string $plainPassword): ?string {
        $user = $this->userRepository->findByEmail($email);
        
        if ($user === null || !password_verify($plainPassword, $user['password_hash'])) {
            // Constant-time comparison (password_verify does this already)
            // Don't reveal whether email or password is wrong (timing attack + enumeration)
            return null;
        }
        
        return $this->jwtService->issue($user['id'], $user['role']);
    }
}

// JWT issuance + validation
class JwtService {
    private string $privateKeyPath;
    private string $publicKeyPath;
    
    public function issue(int $userId, string $role): string {
        $now     = time();
        $payload = [
            'iss' => 'https://api.example.com',
            'aud' => 'https://api.example.com',
            'sub' => (string) $userId,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + 900,  // 15 minutes
        ];
        
        // Sign with RS256 (private key only on auth server)
        return JWT::encode($payload, $this->getPrivateKey(), 'RS256');
    }
    
    public function validate(string $token): array {
        try {
            $decoded = JWT::decode($token, new Key($this->getPublicKey(), 'RS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new AuthException('Token expired');
        } catch (\Exception $e) {
            throw new AuthException('Invalid token');
        }
    }
}

// SSRF prevention -- validate URLs before making requests
class SafeHttpClient {
    private const BLOCKED_IP_RANGES = ['10.', '172.16.', '192.168.', '127.', '169.254.'];
    
    public function get(string $url): string {
        $parsed = parse_url($url);
        
        // Only allow http/https
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            throw new SecurityException("Disallowed URL scheme");
        }
        
        // Resolve hostname and check for private IP
        $ip = gethostbyname($parsed['host']);
        foreach (self::BLOCKED_IP_RANGES as $range) {
            if (str_starts_with($ip, $range)) {
                throw new SecurityException("SSRF: private IP address blocked");
            }
        }
        
        return file_get_contents($url);
    }
}
```

---

## 7. Interview Q&A

**Q1: How do you handle JWT token revocation (logout)?**
> JWT tokens are stateless — once issued, they're valid until expiry. To revoke: (1) Short expiry: 15-minute access tokens. At logout, the token is invalid within 15 minutes anyway; (2) Blocklist: store revoked JWT IDs (jti claim) in Redis with TTL matching token expiry. Check Redis on each request. This adds a DB lookup but enables immediate revocation; (3) Refresh token rotation: long-lived refresh tokens stored in DB — delete on logout. Short-lived access tokens are derived from refresh tokens; (4) Version-based: store user token version in DB. Include version in JWT. On logout, increment version. On validation, check version matches. Any old token with outdated version is rejected.

**Q2: What is CSRF and how do you prevent it?**
> CSRF (Cross-Site Request Forgery): attacker tricks a user's browser into making authenticated requests to your site (e.g., via an img tag or form on malicious site). Browser automatically includes cookies. Prevention: (1) CSRF tokens: include unpredictable token in forms; validate on server (not in cookie -- that's accessible to attacker's site); (2) SameSite cookie: Set-Cookie: session_id=abc; SameSite=Strict (or Lax) -- browser won't send cookie with cross-site requests; (3) Custom request header: "X-Requested-With: XMLHttpRequest" -- only same-origin JS can set custom headers; (4) Double Submit Cookie: send CSRF token both in cookie and header; attacker can't read cookie from different origin.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| JWT: stateless auth -- RS256 signature, short expiry + refresh    |
| Sessions: stateful -- easy revocation, needs shared session store  |
| SQL injection: always parameterized queries -- NEVER string concat |
| Passwords: Argon2id (not bcrypt, never MD5/SHA1/SHA256)           |
| Encryption at rest: AES-256; in transit: TLS 1.3                  |
| SSRF: validate/blocklist URLs before making server-side HTTP calls |
| CSRF: SameSite=Strict cookies + CSRF tokens for state changes     |
+--------------------------------------------------------------------+
```
