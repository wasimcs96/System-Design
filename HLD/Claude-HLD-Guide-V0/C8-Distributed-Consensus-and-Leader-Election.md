# C8 — Distributed Consensus & Leader Election

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** When multiple servers need to agree on a decision (who is the leader, what value to store), they use a consensus algorithm to reach agreement — even if some servers crash or messages are delayed.

**Technical:** Distributed consensus is the process of getting a group of nodes in a distributed system to agree on a single value, despite failures. Leader election is a common application — consensus determines which node becomes the authoritative leader. Paxos and Raft are the dominant consensus algorithms.

---

## 2. Real-World Analogy

**Jury deliberation:**
- Multiple jurors (nodes) must reach unanimous (majority) verdict (consensus)
- If 3 of 12 jurors leave early (node failure), the remaining 9 can still reach verdict
- One juror leads discussion (leader); if they leave, a new leader is elected
- Messages may be delayed (slow mail) but must eventually be delivered for consensus

---

## 3. Visual Diagram

```
RAFT CONSENSUS — LEADER ELECTION:

Normal operation:
Leader ──→ sends heartbeats ──→ Follower1
                             ──→ Follower2
                             ──→ Follower3

Leader fails (no heartbeat for timeout):
Follower1: "Timeout! I'm starting election."
Follower1 becomes Candidate:
  → RequestVote to Follower2: "Vote for me! My term=2, log up-to-date"
  → RequestVote to Follower3: "Vote for me!"
Follower2 votes YES (hasn't seen higher term)
Follower3 votes YES
Follower1 wins majority (2/3 votes) → Becomes new Leader (term=2)

RAFT LOG REPLICATION:
Leader receives write → appends to own log → sends AppendEntries to followers
All followers append → reply OK
Leader receives majority OK → commits → responds to client
Followers learn of commit on next AppendEntries heartbeat

SPLIT BRAIN PREVENTION:
5-node cluster partitioned into 2+3:
Partition A (2 nodes): can't reach majority — no new writes
Partition B (3 nodes): elects leader — continues serving (majority maintained)
```

---

## 4. Deep Technical Explanation

### Raft Algorithm (Modern, easier to understand)
1. **Leader Election:**
   - Nodes start as followers with random election timeout (150-300ms)
   - If no heartbeat received → candidate → requests votes from all nodes
   - Wins if majority votes received → becomes leader
   - Each term is a monotonically increasing integer — guarantees one leader per term

2. **Log Replication:**
   - All writes go to leader
   - Leader appends to own log → replicates to followers (AppendEntries RPC)
   - Once majority confirm → entry committed → applied to state machine
   - Followers learn about commits via subsequent AppendEntries

3. **Safety:**
   - Leader always has most complete log (votes refused if candidate's log is behind)
   - At most one leader per term (majority vote requirement)

### Paxos (Original, more complex)
- Multi-Paxos: used by Google Chubby, Apache Zookeeper (Zab is Paxos-like)
- More complex to implement and reason about than Raft
- Three phases: Prepare, Promise, Accept

### Quorum
With N nodes, system needs `N/2 + 1` (majority) to:
- Elect a leader
- Commit a log entry
- Make any decision

**Why?** Two majorities always intersect — they share at least one node. That node ensures consistency (no two conflicting decisions can both get majority).

| Cluster Size | Majority Needed | Fault Tolerance |
|-------------|----------------|----------------|
| 3 nodes | 2 | 1 failure |
| 5 nodes | 3 | 2 failures |
| 7 nodes | 4 | 3 failures |

**Use odd number of nodes** to avoid split vote.

### Tools Using Consensus
| Tool | Algorithm | Use |
|------|-----------|-----|
| **etcd** | Raft | Kubernetes state store, service discovery |
| **ZooKeeper** | Zab (Paxos-like) | Kafka coordination, Hadoop YARN |
| **Consul** | Raft | Service discovery, KV store |
| **CockroachDB** | Raft | Distributed SQL |
| **TiKV** | Raft | Distributed KV (TiDB backend) |

---

## 5. Code Example

```php
// Leader election using etcd (via HTTP API)
class LeaderElection {
    private string $etcdUrl;
    private string $serviceId;
    private string $leaseId;
    
    public function acquireLeadership(string $electionKey): bool {
        // 1. Create a lease (TTL = 15 seconds)
        $response  = Http::post("{$this->etcdUrl}/v3/lease/grant", ['TTL' => 15]);
        $this->leaseId = $response->json()['ID'];
        
        // 2. Try to put value with lease (fails if key already exists)
        $key     = base64_encode($electionKey);
        $value   = base64_encode($this->serviceId);
        $leaseId = $this->leaseId;
        
        $txnResponse = Http::post("{$this->etcdUrl}/v3/kv/txn", [
            'compare' => [
                ['target' => 'VERSION', 'key' => $key, 'version' => 0]
            ],
            'success' => [
                ['requestPut' => ['key' => $key, 'value' => $value, 'lease' => $leaseId]]
            ],
            'failure' => [],
        ]);
        
        $succeeded = $txnResponse->json()['succeeded'] ?? false;
        
        if ($succeeded) {
            // 3. Start keepalive — send heartbeats before TTL expires
            $this->startKeepalive();
        }
        
        return $succeeded;
    }
    
    private function startKeepalive(): void {
        // Send lease keepalive every TTL/3 seconds
        // In production: run in a background coroutine/fiber
        Http::post("{$this->etcdUrl}/v3/lease/keepalive", ['ID' => $this->leaseId]);
    }
    
    public function resign(): void {
        // Revoke lease → key deleted → other nodes can acquire
        Http::post("{$this->etcdUrl}/v3/lease/revoke", ['ID' => $this->leaseId]);
    }
}
```

---

## 6. Trade-offs

| Algorithm | Complexity | Throughput | Fault Tolerance | Use Case |
|-----------|-----------|-----------|----------------|---------|
| Paxos | Very High | Medium | N/2 failures | Chubby, Zookeeper |
| Raft | High | Medium | N/2 failures | etcd, Consul, CockroachDB |
| 2PC | Medium | High (no faults) | 0 (coordinator SPOF) | Legacy distributed DBs |
| ZAB | High | High | N/2 failures | ZooKeeper |

---

## 7. Interview Q&A

**Q1: What is split-brain and how does consensus prevent it?**
> Split-brain occurs when a network partition divides a cluster into two partitions, each thinking the other is dead and electing its own leader — resulting in two leaders with conflicting writes. Consensus (Raft/Paxos) prevents this by requiring a majority quorum. With 5 nodes partitioned 2+3: the 2-node partition can't reach majority (needs 3) so no leader elected there. Only the 3-node partition elects a leader and continues. The minority side stops accepting writes.

**Q2: Why should you use an odd number of nodes in a Raft cluster?**
> Fault tolerance formula: with N nodes, can tolerate (N-1)/2 failures. With 4 nodes, you can only tolerate 1 failure (same as 3 nodes but costs more). With 5 nodes, you can tolerate 2 failures. Even numbers provide no advantage over the next lower odd number while adding more cost and complexity. Odd clusters also avoid split votes in leader election (with 4 nodes, you could have 2 vs 2 votes).

**Q3: How is leader election different from distributed locking?**
> Distributed locking is for short-duration mutual exclusion (milliseconds to seconds). Leader election is for long-duration primary designation (minutes to hours). Leader election is typically used for: deciding which node handles writes (database primary), which process runs a scheduled job (prevent duplication), or which service instance serves as authoritative. Distributed locks have shorter TTLs and are acquired/released frequently; leaders hold their role until they fail or resign.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Raft: leader election + log replication via majority quorum     │
│ ✓ Quorum = N/2+1; with 5 nodes: tolerate 2 failures              │
│ ✓ Use odd number of nodes to avoid split votes                    │
│ ✓ Split-brain prevented: minority partition can't reach majority  │
│ ✓ etcd (Raft) powers Kubernetes state management                  │
│ ✓ Leader holds lease with TTL; keepalive extends it               │
└────────────────────────────────────────────────────────────────────┘
```
