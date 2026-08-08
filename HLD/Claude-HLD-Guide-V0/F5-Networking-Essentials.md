# F5 — Networking Essentials

> **Section:** Observability and Security | **Level:** Intermediate | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Networking fundamentals underpin all distributed systems. Understanding TCP vs UDP, HTTP versions, and DNS helps you design faster, more reliable systems and troubleshoot network issues.

**Technical:** Key networking concepts for system design: TCP (reliable, ordered, connection-oriented) vs UDP (unreliable, fast, connectionless), HTTP/1.1 vs HTTP/2 vs HTTP/3 (QUIC), WebSockets for real-time bidirectional communication, DNS resolution, and TCP connection establishment.

---

## 2. Real-World Analogy

**TCP vs UDP:**
- TCP = Registered post: delivery confirmed, ordered, resent if lost. Used for: email, file transfer, web pages.
- UDP = Flyer drop: fast, no confirmation, some may be lost. Used for: video streaming, online gaming, DNS (fast, small packets, retry is cheap).

**HTTP/2 vs HTTP/1.1:**
- HTTP/1.1 = Single-lane road: one car (request) at a time per lane (connection)
- HTTP/2 = Multi-lane highway: multiple requests in parallel on one connection (multiplexing)

---

## 3. Visual Diagram

```
TCP 3-WAY HANDSHAKE:
Client          Server
  |----- SYN ------>|  Client: "I want to connect" (seq=100)
  |<-- SYN-ACK -----|  Server: "OK, and I want to connect" (seq=200, ack=101)
  |----- ACK ------>|  Client: "Got it" (ack=201)
  [Connection established -- ~1 RTT overhead]
  |----- Data ----->|
  |<---- Data ------|

HTTP/1.1 vs HTTP/2:
HTTP/1.1:
  Request 1: GET /html  ──→──→──→──→ Response 1
  Wait...
  Request 2: GET /css   ──→──→──→──→ Response 2
  Wait...
  (Head-of-line blocking: can't send R2 until R1 complete on same connection)
  Solution: browser opens 6 connections per domain (hack)

HTTP/2:
  Request 1: GET /html  ]
  Request 2: GET /css   ]──── multiplexed on 1 connection ───→ Responses interleaved
  Request 3: GET /js    ]

HTTP/3 (QUIC over UDP):
  UDP + built-in reliability + TLS 1.3 + 0-RTT reconnect
  No head-of-line blocking at transport layer (packet loss affects only that stream)
  Faster connection: 0-RTT or 1-RTT (vs TCP+TLS: 2+ RTT)

WebSocket:
  HTTP Upgrade request:
  GET /ws HTTP/1.1
  Upgrade: websocket
  Connection: Upgrade
  Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==
  [Upgraded to full-duplex TCP connection]
  Both sides can send messages at any time
```

---

## 4. Deep Technical Explanation

### TCP vs UDP
| Aspect | TCP | UDP |
|--------|-----|-----|
| Reliability | Guaranteed delivery | Best-effort |
| Order | In-order delivery | Out-of-order possible |
| Connection | 3-way handshake | No connection |
| Overhead | Higher (ACKs, window) | Minimal |
| Use cases | HTTP, SMTP, SSH, FTP | DNS, video streaming, gaming, QUIC |

### HTTP/1.1, HTTP/2, HTTP/3

**HTTP/1.1 (1997):**
- Text protocol, one request per connection (pipelining rarely used)
- Keep-Alive: reuse TCP connection for multiple requests
- Head-of-line blocking: slow request blocks subsequent requests

**HTTP/2 (2015):**
- Binary protocol (smaller, faster to parse)
- Multiplexing: multiple streams on one TCP connection
- Header compression (HPACK)
- Server push: push resources before client requests them
- Still has TCP-level head-of-line blocking (one lost packet stalls all streams)

**HTTP/3 (2022):**
- Built on QUIC (UDP-based)
- 0-RTT connection establishment (first connection: 1-RTT; subsequent: 0-RTT)
- Per-stream loss recovery (lost UDP packet only stalls its stream, not all streams)
- Connection migration: keep connection when switching from WiFi to 4G (new IP)

### WebSockets
- Full-duplex persistent connection (client and server both send at will)
- Use cases: live chat, collaborative editing, real-time notifications, trading dashboards
- Alternative: Server-Sent Events (SSE) — server push only, simpler, over HTTP/2

### DNS Resolution
1. Browser cache (TTL-based)
2. OS cache / /etc/hosts
3. Recursive resolver (ISP or 8.8.8.8)
4. Root nameserver -> .com TLD -> authoritative nameserver
5. Authoritative nameserver returns IP address
- TTL: low (60s) = fast propagation changes, high (86400s) = faster resolution (cached)
- Route53 health checks: automatically remove unhealthy IPs from DNS response

---

## 5. Code Example

```php
// TCP socket server (demonstrates connection handling)
$server = stream_socket_server('tcp://0.0.0.0:8080', $errno, $errstr);

while ($client = stream_socket_accept($server)) {
    // Read HTTP request
    $request = fread($client, 4096);
    
    // Parse first line: "GET /path HTTP/1.1"
    [$method, $path, $version] = explode(' ', strtok($request, "
"));
    
    // Send HTTP response
    $body    = json_encode(['status' => 'ok', 'path' => $path]);
    $headers = implode("
", [
        "HTTP/1.1 200 OK",
        "Content-Type: application/json",
        "Content-Length: " . strlen($body),
        "Connection: close",
        "",
        "",
    ]);
    
    fwrite($client, $headers . $body);
    fclose($client);
}

// WebSocket in PHP with Swoole/Ratchet
// Using Ratchet for WebSocket server:
// composer require cboden/ratchet

class RealTimeHandler implements MessageComponentInterface {
    protected SplObjectStorage $clients;
    
    public function onOpen(ConnectionInterface $conn): void {
        $this->clients->attach($conn);
        echo "New connection: {$conn->resourceId}";
    }
    
    public function onMessage(ConnectionInterface $from, string $msg): void {
        $data = json_decode($msg, true);
        
        // Broadcast to all connected clients
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send(json_encode([
                    'from'    => $from->resourceId,
                    'message' => $data['message'],
                    'time'    => time(),
                ]));
            }
        }
    }
    
    public function onClose(ConnectionInterface $conn): void {
        $this->clients->detach($conn);
    }
    
    public function onError(ConnectionInterface $conn, Exception $e): void {
        $conn->close();
    }
}

// DNS lookup (useful for service discovery and debugging)
$ips = dns_get_record('api.example.com', DNS_A);
foreach ($ips as $record) {
    echo $record['ip'] . PHP_EOL;  // multiple A records = round-robin DNS LB
}
```

---

## 7. Interview Q&A

**Q1: Why does HTTP/3 use UDP instead of TCP? Isn't UDP unreliable?**
> HTTP/2 over TCP has a fundamental flaw: TCP treats all bytes as one ordered stream. One lost packet stalls ALL HTTP/2 streams until the packet is retransmitted (TCP-level head-of-line blocking). HTTP/3 uses QUIC (UDP-based) which reimplements TCP's reliability at the application level, but per-stream. A lost packet only stalls the specific stream it belongs to -- other streams continue. Additionally: QUIC integrates TLS 1.3 (0-RTT connections), supports connection migration (IP address changes don't drop the connection), and is faster to iterate on (user-space vs kernel-space TCP).

**Q2: When would you use WebSockets over HTTP polling or SSE?**
> WebSockets: true bidirectional, low latency, both sides send messages at will. Use for: live collaboration (Google Docs), multiplayer gaming, live trading dashboards. SSE (Server-Sent Events): server push only, simpler (plain HTTP/2, no upgrade), automatic reconnect, built into browser EventSource API. Use for: live notifications, news feeds, dashboards (client only receives). HTTP polling: simplest, any infrastructure. Use for: low-frequency updates (check every 30 seconds) where simplicity matters more than latency. Long-polling: server holds request until there's data -- compromise between polling and WebSocket.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| TCP: reliable, ordered, connection overhead (3-way handshake)     |
| UDP: fast, unreliable -- DNS, streaming, QUIC (HTTP/3)            |
| HTTP/2: multiplexing on one TCP connection, binary protocol       |
| HTTP/3: QUIC (UDP) + 0-RTT + per-stream loss recovery            |
| WebSocket: full-duplex persistent -- live chat, real-time apps    |
| DNS: low TTL for fast failover; high TTL for faster resolution     |
+--------------------------------------------------------------------+
```
