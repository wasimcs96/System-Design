# C9 — Gossip Protocol

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Gossip protocol is how nodes in a distributed system share information with each other — like office gossip. Each node tells a few random neighbors what it knows. Those neighbors tell others. Soon everyone knows.

**Technical:** A gossip (epidemic) protocol is a peer-to-peer communication method where each node periodically selects random peers and exchanges information. Information propagates exponentially — after O(log N) rounds, all N nodes have the information. Used for cluster membership, failure detection, and state synchronization.

---

## 2. Real-World Analogy

**Office rumor spreading:**
1. Alice tells Bob and Charlie a secret
2. Bob tells Dave and Eve; Charlie tells Frank and George
3. Each person tells 2 others every "round"
4. After log₂(N) rounds, everyone in the office knows
5. Even if 30% of people are absent, the secret still spreads (fault tolerant)

---

## 3. Visual Diagram

```
GOSSIP PROPAGATION (10 nodes, fanout=2):

Round 0: Node1 has new info
Round 1: Node1 → Node3, Node7     (3 nodes know)
Round 2: Node1 → Node5, Node9     (5 nodes know)
          Node3 → Node2, Node8
          Node7 → Node4, Node6
Round 3: all nodes know
Total rounds: O(log N) = O(log 10) ≈ 3-4 rounds

CASSANDRA USES GOSSIP FOR:
- Cluster membership (who's alive?)
- Node state (UP, DOWN, LEAVING, JOINING)
- Schema versions
- Token assignments

Each node gossips to 1-3 random peers every second
Information spreads cluster-wide within seconds
```

---

## 4. Deep Technical Explanation

### Gossip Variants
1. **Push:** Node sends its state to random peers
2. **Pull:** Node asks random peers for their state
3. **Push-Pull:** Node sends its state AND asks for peer's state (most efficient)

### Convergence Guarantee
- With N nodes and fanout F, information reaches all nodes in O(log N / log F) rounds
- Very resilient: even with 50% packet loss, information eventually propagates
- No single point of failure — decentralized

### Failure Detection via Gossip
- Each node tracks heartbeat counter for all other nodes
- Gossip disseminates heartbeat counts
- If node X's heartbeat hasn't increased after N seconds → suspected DOWN
- Cassandra uses Phi Accrual Failure Detector: uses probability to compute how likely a node is dead based on heartbeat history

### Used In
| System | Use of Gossip |
|--------|--------------|
| Cassandra | Membership, token ranges, schema versions |
| DynamoDB | Membership (original design) |
| Redis Cluster | Cluster state, slot assignments |
| Consul | Node failure detection |
| Riak | Ring membership |
| Bitcoin | Transaction/block propagation |

### SWIM Protocol (Scalable Weakly-consistent Infection-style Membership)
Modern gossip-based failure detection:
1. A pings B (direct probe)
2. If B doesn't respond → A asks C, D, E to probe B (indirect probe)
3. If none respond → B marked as SUSPECTED
4. After timeout → B marked as FAULTY and gossip notifies cluster
5. B can rejoin by gossiping its alive status

---

## 5. Code Example

```php
// Simplified gossip state exchange
class GossipNode {
    private string $nodeId;
    private array  $memberList;  // nodeId => {status, heartbeat, lastSeen}
    private array  $peers;       // known peer addresses
    
    public function gossipRound(): void {
        // Select 3 random peers
        $randomPeers = array_rand($this->peers, min(3, count($this->peers)));
        
        foreach ((array)$randomPeers as $peerId) {
            $this->exchangeState($peerId);
        }
        
        // Increment own heartbeat
        $this->memberList[$this->nodeId]['heartbeat']++;
        $this->memberList[$this->nodeId]['lastSeen'] = time();
    }
    
    private function exchangeState(string $peerId): void {
        // Send own member list
        $peerState = Http::post("http://{$this->peers[$peerId]}/gossip", [
            'sender'     => $this->nodeId,
            'memberList' => $this->memberList,
        ])->json();
        
        // Merge received state with own state
        foreach ($peerState['memberList'] as $nodeId => $info) {
            if (!isset($this->memberList[$nodeId]) 
                || $info['heartbeat'] > $this->memberList[$nodeId]['heartbeat']) {
                $this->memberList[$nodeId] = $info;
            }
        }
    }
    
    public function detectFailures(): void {
        $now = time();
        foreach ($this->memberList as $nodeId => &$info) {
            if ($nodeId === $this->nodeId) continue;
            
            $timeSinceLastSeen = $now - $info['lastSeen'];
            if ($timeSinceLastSeen > 30) {
                $info['status'] = 'SUSPECTED';
            }
            if ($timeSinceLastSeen > 60) {
                $info['status'] = 'DOWN';
            }
        }
    }
}
```

---

## 7. Interview Q&A

**Q1: Why does Cassandra use gossip instead of a centralized registry?**
> Centralized registry = single point of failure. If the registry goes down, the entire cluster can't discover membership. Gossip is decentralized — every node participates equally. Any node can be asked about cluster state. Adding/removing nodes doesn't require coordination with a central service. Cassandra clusters can have 1000+ nodes — gossip scales O(log N) where a centralized approach would be O(N) communication overhead.

**Q2: How does gossip handle network partitions?**
> During a partition, each side gossips within its own partition. Nodes on either side will eventually mark nodes they can't hear from as SUSPECTED/DOWN. When the partition heals, gossip resumes across both sides. Cassandra uses anti-entropy (Merkle tree comparison) alongside gossip to repair data differences after a partition heals. Gossip itself converges quickly after partition recovery.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Gossip = decentralized info spreading in O(log N) rounds        │
│ ✓ Each node gossips to 1-3 random peers periodically             │
│ ✓ Highly fault-tolerant: works even with 50% message loss         │
│ ✓ Used in Cassandra, Redis Cluster, DynamoDB for membership       │
│ ✓ Failure detection via missing heartbeats — Phi Accrual Detector │
│ ✓ SWIM: direct probe + indirect probe for accurate failure detect │
└────────────────────────────────────────────────────────────────────┘
```
