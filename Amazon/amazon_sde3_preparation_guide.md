# 🚀 Amazon SDE 3 (L6) — Complete Interview Preparation Guide

> **Goal:** Follow this document blindly. No other resource hunting needed.
> **Target Role:** SDE 3 / L6 at Amazon
> **Author:** Wasim Akram | Auto-generated preparation blueprint

---

## Table of Contents

1. [Understanding the SDE 3 (L6) Role](#1-understanding-the-sde-3-l6-role)
2. [Interview Process & Rounds](#2-interview-process--rounds)
3. [Preparation Timelines (4 / 8 / 12 Weeks)](#3-preparation-timelines)
4. [DSA — Data Structures & Algorithms](#4-dsa--data-structures--algorithms)
5. [LLD — Low-Level Design](#5-lld--low-level-design)
6. [HLD — High-Level Design (System Design)](#6-hld--high-level-design-system-design)
7. [Behavioral / Leadership Principles Round](#7-behavioral--leadership-principles-round)
8. [Bar Raiser Round](#8-bar-raiser-round)
9. [Resume & Application Strategy](#9-resume--application-strategy)
10. [Salary & Compensation (L6)](#10-salary--compensation-l6)
11. [Day-of-Interview Checklist](#11-day-of-interview-checklist)
12. [Cheat Sheets](#12-cheat-sheets)
13. [Resources & Tools](#13-resources--tools)

---

## 1. Understanding the SDE 3 (L6) Role

### What Amazon Expects at L6

| Dimension | Expectation |
|-----------|-------------|
| **Scope** | Own and deliver large features / services end-to-end |
| **Influence** | Mentor SDEs, drive technical direction for team |
| **Ambiguity** | Operate comfortably with ambiguous requirements |
| **Design** | Architect systems that scale to millions of users |
| **Code** | Write production-quality, testable, maintainable code |
| **Leadership** | Lead without authority; raise the bar for the team |

### L5 vs L6 — Key Differences

| Aspect | L5 (SDE 2) | L6 (SDE 3) |
|--------|-----------|-----------|
| Ownership | Feature-level | Service / system-level |
| Design | Contributes to design | Drives design decisions |
| Mentorship | Occasional | Active & expected |
| Cross-team | Rare | Frequent collaboration |
| Ambiguity | Handles with guidance | Handles independently |
| Bar Raiser | Not expected | Expected to raise the bar |

---

## 2. Interview Process & Rounds

### Typical Amazon SDE 3 Loop

```
Phone Screen (1 round, 60 min)
    ↓
Onsite / Virtual Loop (4-5 rounds, 60 min each)
    ↓
Debrief & Hiring Committee
    ↓
Offer
```

### Round Breakdown

| # | Round | Duration | Focus |
|---|-------|----------|-------|
| 0 | **Online Assessment** (sometimes) | 90 min | 2 coding problems + work simulation |
| 1 | **Phone Screen** | 60 min | 1 medium/hard coding + LP questions |
| 2 | **DSA / Coding** | 60 min | 1-2 problems (medium-hard), working code |
| 3 | **LLD (Object-Oriented Design)** | 60 min | Design a class hierarchy, APIs, schema |
| 4 | **HLD (System Design)** | 60 min | Design a distributed system end-to-end |
| 5 | **Behavioral / LP Deep Dive** | 60 min | 4-6 LP questions using STAR method |
| 6 | **Bar Raiser** | 60 min | Mix of coding/design + LP (hardest round) |

> **Note:** Sometimes coding & LP are combined in the same round. Expect LP questions in EVERY round.

---

## 3. Preparation Timelines

### 🟢 12-Week Plan (Recommended — 2-3 hrs/day)

| Week | Mon-Tue | Wed-Thu | Fri | Sat-Sun |
|------|---------|---------|-----|---------|
| 1-2 | DSA Fundamentals (Arrays, Strings, HashMap) | DSA Practice (Easy/Med) | LLD Theory (SOLID, Patterns) | Behavioral: Write 5 STAR stories |
| 3-4 | DSA (Trees, Graphs) | DSA Practice (Med) | LLD Practice (Parking Lot, Cache) | Behavioral: Refine stories for 5 LPs |
| 5-6 | DSA (DP, Greedy) | DSA Practice (Med/Hard) | HLD Theory (Scalability, DB, Caching) | Behavioral: Write 5 more STAR stories |
| 7-8 | DSA (Advanced: Trie, Union-Find, Segment Tree) | DSA Practice (Hard) | HLD Practice (URL Shortener, Chat) | Behavioral: Practice out loud |
| 9-10 | Mixed DSA revision | LLD Practice (2 new problems) | HLD Practice (2 new problems) | Mock Interviews (1 DSA + 1 System Design) |
| 11 | Mock Interview (Full loop simulation) | Weak-area deep dive | HLD + LLD revision | Behavioral: Record yourself answering |
| 12 | Light revision only | Mock interview | Rest | **Interview Day** |

### 🟡 8-Week Plan (Aggressive — 3-4 hrs/day)

| Week | Focus |
|------|-------|
| 1-2 | DSA foundations + 40 problems + SOLID principles + 8 STAR stories |
| 3-4 | DSA (Trees, Graphs, DP) + 40 problems + LLD (3 problems) |
| 5-6 | DSA Hard + HLD (4 systems) + Behavioral practice |
| 7 | Full mock interviews (2 DSA + 2 Design + 1 Behavioral) |
| 8 | Revision + light practice + rest before interview |

### 🔴 4-Week Plan (Emergency — 4-5 hrs/day)

| Week | Focus |
|------|-------|
| 1 | DSA: Top 50 Amazon-tagged problems + SOLID + 8 STAR stories |
| 2 | LLD (3 problems) + HLD (3 systems) + 20 more DSA problems |
| 3 | Mock interviews daily + weak-area deep dive |
| 4 | Revision + 2 mock interviews + rest |

### Daily Schedule Template

```
Morning (1 hr):   2 LeetCode problems (timed, 25 min each)
Afternoon (1 hr): LLD or HLD study/practice (alternate days)
Evening (30 min): 1 LP story — write, refine, speak out loud
Weekend (3 hrs):  Mock interview + revision
```

---

## 4. DSA — Data Structures & Algorithms

### 4.1 Topic Priority Matrix

| Priority | Topic | Amazon Frequency | Target Problems |
|----------|-------|-----------------|-----------------|
| **P0** | Arrays & Strings | ★★★★★ | 15 |
| **P0** | Hash Maps & Sets | ★★★★★ | 10 |
| **P0** | Trees (Binary, BST) | ★★★★★ | 12 |
| **P0** | Graphs (BFS, DFS) | ★★★★☆ | 10 |
| **P0** | Dynamic Programming | ★★★★☆ | 12 |
| **P1** | Linked Lists | ★★★☆☆ | 6 |
| **P1** | Stacks & Queues | ★★★☆☆ | 6 |
| **P1** | Sorting & Searching | ★★★★☆ | 8 |
| **P1** | Sliding Window / Two Pointer | ★★★★☆ | 8 |
| **P1** | Heap / Priority Queue | ★★★☆☆ | 6 |
| **P2** | Trie | ★★★☆☆ | 4 |
| **P2** | Union-Find | ★★★☆☆ | 4 |
| **P2** | Monotonic Stack/Queue | ★★☆☆☆ | 3 |
| **P2** | Bit Manipulation | ★★☆☆☆ | 3 |
| **P2** | Backtracking | ★★★☆☆ | 5 |

### 4.2 Amazon-Tagged LeetCode Problems (Top 75)

#### Arrays & Strings
| # | Problem | Difficulty | Pattern |
|---|---------|-----------|---------|
| 1 | [Two Sum](https://leetcode.com/problems/two-sum/) | Easy | HashMap |
| 2 | [Best Time to Buy and Sell Stock](https://leetcode.com/problems/best-time-to-buy-and-sell-stock/) | Easy | Kadane's variant |
| 3 | [Product of Array Except Self](https://leetcode.com/problems/product-of-array-except-self/) | Medium | Prefix/Suffix |
| 4 | [Maximum Subarray](https://leetcode.com/problems/maximum-subarray/) | Medium | Kadane's |
| 5 | [Container With Most Water](https://leetcode.com/problems/container-with-most-water/) | Medium | Two Pointer |
| 6 | [3Sum](https://leetcode.com/problems/3sum/) | Medium | Two Pointer + Sort |
| 7 | [Longest Substring Without Repeating Characters](https://leetcode.com/problems/longest-substring-without-repeating-characters/) | Medium | Sliding Window |
| 8 | [Minimum Window Substring](https://leetcode.com/problems/minimum-window-substring/) | Hard | Sliding Window |
| 9 | [Group Anagrams](https://leetcode.com/problems/group-anagrams/) | Medium | HashMap + Sort |
| 10 | [Trapping Rain Water](https://leetcode.com/problems/trapping-rain-water/) | Hard | Two Pointer / Stack |
| 11 | [Merge Intervals](https://leetcode.com/problems/merge-intervals/) | Medium | Sort + Greedy |
| 12 | [Insert Interval](https://leetcode.com/problems/insert-interval/) | Medium | Intervals |
| 13 | [Spiral Matrix](https://leetcode.com/problems/spiral-matrix/) | Medium | Simulation |
| 14 | [String to Integer (atoi)](https://leetcode.com/problems/string-to-integer-atoi/) | Medium | String Parsing |
| 15 | [Integer to English Words](https://leetcode.com/problems/integer-to-english-words/) | Hard | String + Recursion |

#### Trees
| # | Problem | Difficulty | Pattern |
|---|---------|-----------|---------|
| 16 | [Binary Tree Level Order Traversal](https://leetcode.com/problems/binary-tree-level-order-traversal/) | Medium | BFS |
| 17 | [Validate Binary Search Tree](https://leetcode.com/problems/validate-binary-search-tree/) | Medium | DFS + Range |
| 18 | [Lowest Common Ancestor of a Binary Tree](https://leetcode.com/problems/lowest-common-ancestor-of-a-binary-tree/) | Medium | DFS |
| 19 | [Binary Tree Zigzag Level Order Traversal](https://leetcode.com/problems/binary-tree-zigzag-level-order-traversal/) | Medium | BFS |
| 20 | [Serialize and Deserialize Binary Tree](https://leetcode.com/problems/serialize-and-deserialize-binary-tree/) | Hard | BFS/DFS |
| 21 | [Diameter of Binary Tree](https://leetcode.com/problems/diameter-of-binary-tree/) | Easy | DFS |
| 22 | [Maximum Depth of Binary Tree](https://leetcode.com/problems/maximum-depth-of-binary-tree/) | Easy | DFS/BFS |
| 23 | [Subtree of Another Tree](https://leetcode.com/problems/subtree-of-another-tree/) | Easy | DFS |
| 24 | [Construct Binary Tree from Preorder and Inorder](https://leetcode.com/problems/construct-binary-tree-from-preorder-and-inorder-traversal/) | Medium | Recursion |
| 25 | [Binary Tree Maximum Path Sum](https://leetcode.com/problems/binary-tree-maximum-path-sum/) | Hard | DFS |
| 26 | [Kth Smallest Element in a BST](https://leetcode.com/problems/kth-smallest-element-in-a-bst/) | Medium | Inorder |
| 27 | [Word Search II](https://leetcode.com/problems/word-search-ii/) | Hard | Trie + DFS |

#### Graphs
| # | Problem | Difficulty | Pattern |
|---|---------|-----------|---------|
| 28 | [Number of Islands](https://leetcode.com/problems/number-of-islands/) | Medium | BFS/DFS |
| 29 | [Clone Graph](https://leetcode.com/problems/clone-graph/) | Medium | BFS/DFS + HashMap |
| 30 | [Course Schedule](https://leetcode.com/problems/course-schedule/) | Medium | Topological Sort |
| 31 | [Course Schedule II](https://leetcode.com/problems/course-schedule-ii/) | Medium | Topological Sort |
| 32 | [Word Ladder](https://leetcode.com/problems/word-ladder/) | Hard | BFS |
| 33 | [Pacific Atlantic Water Flow](https://leetcode.com/problems/pacific-atlantic-water-flow/) | Medium | Multi-source BFS/DFS |
| 34 | [Graph Valid Tree](https://leetcode.com/problems/graph-valid-tree/) | Medium | Union-Find / DFS |
| 35 | [Number of Connected Components](https://leetcode.com/problems/number-of-connected-components-in-an-undirected-graph/) | Medium | Union-Find |
| 36 | [Accounts Merge](https://leetcode.com/problems/accounts-merge/) | Medium | Union-Find |
| 37 | [Shortest Path in Binary Matrix](https://leetcode.com/problems/shortest-path-in-binary-matrix/) | Medium | BFS |

#### Dynamic Programming
| # | Problem | Difficulty | Pattern |
|---|---------|-----------|---------|
| 38 | [Climbing Stairs](https://leetcode.com/problems/climbing-stairs/) | Easy | 1D DP |
| 39 | [Coin Change](https://leetcode.com/problems/coin-change/) | Medium | Unbounded Knapsack |
| 40 | [Longest Increasing Subsequence](https://leetcode.com/problems/longest-increasing-subsequence/) | Medium | 1D DP + Binary Search |
| 41 | [Word Break](https://leetcode.com/problems/word-break/) | Medium | 1D DP + Trie |
| 42 | [Combination Sum IV](https://leetcode.com/problems/combination-sum-iv/) | Medium | 1D DP |
| 43 | [Decode Ways](https://leetcode.com/problems/decode-ways/) | Medium | 1D DP |
| 44 | [Unique Paths](https://leetcode.com/problems/unique-paths/) | Medium | 2D DP |
| 45 | [Longest Common Subsequence](https://leetcode.com/problems/longest-common-subsequence/) | Medium | 2D DP |
| 46 | [Edit Distance](https://leetcode.com/problems/edit-distance/) | Medium | 2D DP |
| 47 | [Partition Equal Subset Sum](https://leetcode.com/problems/partition-equal-subset-sum/) | Medium | 0/1 Knapsack |
| 48 | [Regular Expression Matching](https://leetcode.com/problems/regular-expression-matching/) | Hard | 2D DP |
| 49 | [Burst Balloons](https://leetcode.com/problems/burst-balloons/) | Hard | Interval DP |

#### Stacks, Heaps, Linked Lists & Others
| # | Problem | Difficulty | Pattern |
|---|---------|-----------|---------|
| 50 | [Valid Parentheses](https://leetcode.com/problems/valid-parentheses/) | Easy | Stack |
| 51 | [Min Stack](https://leetcode.com/problems/min-stack/) | Medium | Stack Design |
| 52 | [Daily Temperatures](https://leetcode.com/problems/daily-temperatures/) | Medium | Monotonic Stack |
| 53 | [Largest Rectangle in Histogram](https://leetcode.com/problems/largest-rectangle-in-histogram/) | Hard | Monotonic Stack |
| 54 | [Top K Frequent Elements](https://leetcode.com/problems/top-k-frequent-elements/) | Medium | Heap / Bucket Sort |
| 55 | [Find Median from Data Stream](https://leetcode.com/problems/find-median-from-data-stream/) | Hard | Two Heaps |
| 56 | [Merge K Sorted Lists](https://leetcode.com/problems/merge-k-sorted-lists/) | Hard | Heap / Divide-Conquer |
| 57 | [LRU Cache](https://leetcode.com/problems/lru-cache/) | Medium | HashMap + DLL |
| 58 | [LFU Cache](https://leetcode.com/problems/lfu-cache/) | Hard | HashMap + DLL |
| 59 | [Reverse Linked List](https://leetcode.com/problems/reverse-linked-list/) | Easy | Iteration/Recursion |
| 60 | [Merge Two Sorted Lists](https://leetcode.com/problems/merge-two-sorted-lists/) | Easy | Two Pointer |
| 61 | [Copy List with Random Pointer](https://leetcode.com/problems/copy-list-with-random-pointer/) | Medium | HashMap |
| 62 | [Reorder List](https://leetcode.com/problems/reorder-list/) | Medium | Fast/Slow + Reverse |
| 63 | [Search in Rotated Sorted Array](https://leetcode.com/problems/search-in-rotated-sorted-array/) | Medium | Binary Search |
| 64 | [Find Minimum in Rotated Sorted Array](https://leetcode.com/problems/find-minimum-in-rotated-sorted-array/) | Medium | Binary Search |
| 65 | [Median of Two Sorted Arrays](https://leetcode.com/problems/median-of-two-sorted-arrays/) | Hard | Binary Search |
| 66 | [Kth Largest Element in an Array](https://leetcode.com/problems/kth-largest-element-in-an-array/) | Medium | Quickselect / Heap |
| 67 | [Design Add and Search Words Data Structure](https://leetcode.com/problems/design-add-and-search-words-data-structure/) | Medium | Trie + DFS |
| 68 | [Implement Trie](https://leetcode.com/problems/implement-trie-prefix-tree/) | Medium | Trie |
| 69 | [Alien Dictionary](https://leetcode.com/problems/alien-dictionary/) | Hard | Topological Sort |
| 70 | [Meeting Rooms II](https://leetcode.com/problems/meeting-rooms-ii/) | Medium | Heap / Sweep Line |
| 71 | [Task Scheduler](https://leetcode.com/problems/task-scheduler/) | Medium | Greedy / Heap |
| 72 | [Maximal Square](https://leetcode.com/problems/maximal-square/) | Medium | 2D DP |
| 73 | [Min Cost to Connect All Points](https://leetcode.com/problems/min-cost-to-connect-all-points/) | Medium | MST (Prim's/Kruskal's) |
| 74 | [Design Hit Counter](https://leetcode.com/problems/design-hit-counter/) | Medium | Queue / Circular Array |
| 75 | [Sliding Window Maximum](https://leetcode.com/problems/sliding-window-maximum/) | Hard | Monotonic Deque |

### 4.3 Pattern Templates

#### Sliding Window Template
```python
def sliding_window(s, k):
    window = {}
    left = 0
    result = 0
    for right in range(len(s)):
        # Expand: add s[right] to window
        window[s[right]] = window.get(s[right], 0) + 1
        # Shrink: while window is invalid
        while window_is_invalid(window):
            window[s[left]] -= 1
            if window[s[left]] == 0:
                del window[s[left]]
            left += 1
        # Update result
        result = max(result, right - left + 1)
    return result
```

#### BFS Template
```python
from collections import deque
def bfs(graph, start):
    visited = {start}
    queue = deque([start])
    while queue:
        node = queue.popleft()
        for neighbor in graph[node]:
            if neighbor not in visited:
                visited.add(neighbor)
                queue.append(neighbor)
```

#### DFS Template
```python
def dfs(graph, node, visited):
    visited.add(node)
    for neighbor in graph[node]:
        if neighbor not in visited:
            dfs(graph, neighbor, visited)
```

#### Topological Sort (Kahn's)
```python
from collections import deque
def topological_sort(num_nodes, edges):
    in_degree = [0] * num_nodes
    adj = [[] for _ in range(num_nodes)]
    for u, v in edges:
        adj[u].append(v)
        in_degree[v] += 1
    queue = deque([i for i in range(num_nodes) if in_degree[i] == 0])
    order = []
    while queue:
        node = queue.popleft()
        order.append(node)
        for neighbor in adj[node]:
            in_degree[neighbor] -= 1
            if in_degree[neighbor] == 0:
                queue.append(neighbor)
    return order if len(order) == num_nodes else []  # cycle detection
```

#### Binary Search Template
```python
def binary_search(nums, target):
    lo, hi = 0, len(nums) - 1
    while lo <= hi:
        mid = (lo + hi) // 2
        if nums[mid] == target:
            return mid
        elif nums[mid] < target:
            lo = mid + 1
        else:
            hi = mid - 1
    return -1  # or lo for insertion point
```

#### Union-Find Template
```python
class UnionFind:
    def __init__(self, n):
        self.parent = list(range(n))
        self.rank = [0] * n
    
    def find(self, x):
        if self.parent[x] != x:
            self.parent[x] = self.find(self.parent[x])  # path compression
        return self.parent[x]
    
    def union(self, x, y):
        px, py = self.find(x), self.find(y)
        if px == py:
            return False
        if self.rank[px] < self.rank[py]:
            px, py = py, px
        self.parent[py] = px
        if self.rank[px] == self.rank[py]:
            self.rank[px] += 1
        return True
```

#### DP Template (Bottom-Up)
```python
def dp_bottom_up(n):
    dp = [0] * (n + 1)
    dp[0] = base_case
    for i in range(1, n + 1):
        dp[i] = transition(dp, i)  # dp[i] = min/max(dp[i-1], dp[i-2], ...)
    return dp[n]
```

### 4.4 Complexity Cheat Sheet

| Data Structure | Access | Search | Insert | Delete |
|---------------|--------|--------|--------|--------|
| Array | O(1) | O(n) | O(n) | O(n) |
| HashMap | — | O(1) avg | O(1) avg | O(1) avg |
| BST (balanced) | O(log n) | O(log n) | O(log n) | O(log n) |
| Heap | — | O(n) | O(log n) | O(log n) |
| Trie | — | O(L) | O(L) | O(L) |
| Stack/Queue | — | O(n) | O(1) | O(1) |
| Linked List | O(n) | O(n) | O(1) | O(1) |

| Algorithm | Time | Space |
|-----------|------|-------|
| Binary Search | O(log n) | O(1) |
| Merge Sort | O(n log n) | O(n) |
| Quick Sort | O(n log n) avg | O(log n) |
| BFS/DFS | O(V + E) | O(V) |
| Dijkstra | O(E log V) | O(V) |
| Topological Sort | O(V + E) | O(V) |
| Union-Find | O(α(n)) ≈ O(1) | O(n) |

---

## 5. LLD — Low-Level Design

### 5.1 What Amazon Expects in LLD (SDE 3)

- **SOLID Principles** applied correctly
- **Design Patterns** used where appropriate (not forced)
- **Clear class hierarchy** with proper relationships
- **API design** (method signatures, interfaces)
- **Schema design** (if asked)
- **Concurrency** awareness (thread safety, locks)
- **Extensibility** — "what if we need to add feature X?"
- **Trade-off discussions** — why this design over another?

### 5.2 SOLID Principles (Memorize These)

| Principle | Full Name | One-Liner | Example |
|-----------|-----------|-----------|---------|
| **S** | Single Responsibility | A class should have only one reason to change | `PaymentProcessor` should not also send emails |
| **O** | Open/Closed | Open for extension, closed for modification | Use `Strategy` pattern instead of if-else chains |
| **L** | Liskov Substitution | Subtypes must be substitutable for base types | `Square extends Rectangle` violates this if width/height are coupled |
| **I** | Interface Segregation | No client should be forced to depend on unused methods | Split `IWorker` into `IWorkable` and `IFeedable` |
| **D** | Dependency Inversion | Depend on abstractions, not concretions | `OrderService` depends on `IPaymentGateway`, not `StripeGateway` |

### 5.3 Essential Design Patterns for Amazon

| Pattern | Category | When to Use | Amazon LLD Use Case |
|---------|----------|------------|-------------------|
| **Strategy** | Behavioral | Multiple algorithms, pick at runtime | Payment methods, pricing strategies |
| **Observer** | Behavioral | Event-driven notifications | Order status updates, stock alerts |
| **Factory** | Creational | Create objects without specifying exact class | Vehicle types in parking lot, notification channels |
| **Singleton** | Creational | Global single instance | Configuration manager, connection pool |
| **Builder** | Creational | Complex object construction | Query builder, order builder |
| **Decorator** | Structural | Add behavior dynamically | Toppings on pizza, middleware in pipeline |
| **Chain of Responsibility** | Behavioral | Pass request along a chain | Vending machine, approval workflow |
| **State** | Behavioral | Object behavior changes with state | Elevator, vending machine, order lifecycle |
| **Command** | Behavioral | Encapsulate request as object | Undo/redo, task queue |
| **Iterator** | Behavioral | Sequential access without exposing structure | Custom collection traversal |

### 5.4 LLD Problem Set (20 Problems — Solve All)

#### Tier 1: Must-Do (Practice these first)

**Problem 1: Parking Lot System**
```
Requirements:
- Multiple floors, each floor has multiple spots
- Spot types: Compact, Regular, Large
- Vehicle types: Motorcycle, Car, Bus (bus needs 5 consecutive large spots)
- Entry/exit panels, ticketing, payment
- Capacity tracking per floor

Key Classes:
- ParkingLot (Singleton)
- ParkingFloor
- ParkingSpot (abstract) → CompactSpot, RegularSpot, LargeSpot
- Vehicle (abstract) → Car, Motorcycle, Bus
- Ticket
- Payment (Strategy: CashPayment, CardPayment)
- EntryPanel, ExitPanel
- DisplayBoard

Key Patterns: Singleton, Strategy, Factory
```

**Problem 2: LRU Cache**
```
Requirements:
- get(key) → return value in O(1), move to most recent
- put(key, value) → insert/update in O(1), evict LRU if at capacity
- Thread-safe version (bonus)

Key Classes:
- LRUCache
- DoublyLinkedListNode
- DoublyLinkedList (addToHead, removeTail, moveToHead)

Data Structures: HashMap<Key, Node> + Doubly Linked List
```

**Problem 3: Design a Pub-Sub / Message Queue**
```
Requirements:
- Topics, Publishers, Subscribers
- Publishers publish messages to topics
- Subscribers subscribe to topics and receive messages
- Support fan-out (all subscribers get the message)
- Message ordering per topic

Key Classes:
- Topic
- Message
- Publisher
- Subscriber (interface) → ConcreteSubscriber
- MessageBroker (Singleton)

Key Patterns: Observer, Singleton
```

**Problem 4: Design an Elevator System**
```
Requirements:
- Multiple elevators in a building
- Handle up/down requests from any floor
- Internal requests (floor buttons inside elevator)
- Elevator states: IDLE, MOVING_UP, MOVING_DOWN, DOOR_OPEN
- Scheduling algorithm (LOOK, SCAN, or simple nearest)

Key Classes:
- Building
- Elevator
- ElevatorController
- Request (ExternalRequest, InternalRequest)
- Door
- Display
- ElevatorState (enum)
- SchedulingStrategy (interface) → LOOKStrategy, SCANStrategy

Key Patterns: State, Strategy, Observer
```

**Problem 5: Design Amazon Online Shopping System**
```
Requirements:
- User registration & authentication
- Product catalog with search & filter
- Shopping cart
- Order placement & tracking
- Payment processing
- Notifications (email, SMS)

Key Classes:
- User (abstract) → Customer, Admin, Seller
- Product, ProductCategory
- ShoppingCart, CartItem
- Order, OrderItem, OrderStatus (enum)
- Payment (interface) → CreditCardPayment, UPIPayment
- Address, Shipment
- NotificationService (Strategy: EmailNotification, SMSNotification)
- SearchService

Key Patterns: Strategy, Observer, Factory, Builder
```

#### Tier 2: High-Priority

| # | Problem | Key Concepts |
|---|---------|-------------|
| 6 | **Tic-Tac-Toe** | Board, Player, GameState, O(1) win check |
| 7 | **Library Management System** | Book, Member, Reservation, Fine calculation |
| 8 | **Vending Machine** | State pattern, inventory, coin change |
| 9 | **Snake and Ladder Game** | Board, Dice, Player, Game loop |
| 10 | **Design a Logging Framework** | Singleton, Chain of Responsibility, log levels |
| 11 | **Design a Rate Limiter** | Sliding window, token bucket, leaky bucket |
| 12 | **File System** | Composite pattern, File/Directory hierarchy |
| 13 | **Hotel Booking System** | Room types, reservations, payment, calendar |
| 14 | **Chess Game** | Piece hierarchy, move validation, game state |
| 15 | **Design Splitwise** | User, Group, Expense, balance simplification |

#### Tier 3: Good-to-Have

| # | Problem | Key Concepts |
|---|---------|-------------|
| 16 | **Movie Ticket Booking (BookMyShow)** | Seat selection, concurrency, payment |
| 17 | **ATM Machine** | State pattern, transaction types, cash dispenser |
| 18 | **Car Rental System** | Vehicle types, reservation, billing |
| 19 | **Design Cricbuzz** | Match, Team, Player, Score, Commentary |
| 20 | **Task Scheduler / Cron Job System** | Scheduling strategies, priority queue |

### 5.5 LLD Interview Framework (Follow This Structure)

```
Step 1: Clarify Requirements (3-5 min)
  → Ask functional & non-functional requirements
  → Identify actors (who uses the system?)
  → Identify core use cases (top 3-4)
  → State assumptions explicitly

Step 2: Identify Core Objects / Classes (5 min)
  → Nouns from requirements → Classes
  → Verbs from requirements → Methods
  → Identify relationships (is-a, has-a, uses-a)

Step 3: Define Class Diagram (10 min)
  → Draw classes with attributes and methods
  → Show inheritance, composition, aggregation
  → Apply SOLID principles
  → Introduce design patterns where natural

Step 4: Define APIs / Key Methods (10 min)
  → Write method signatures with parameters and return types
  → Show the flow for the primary use case
  → Discuss access modifiers, interfaces, abstract classes

Step 5: Walk Through a Use Case (5 min)
  → Pick the most important use case
  → Walk through the code flow step by step
  → Show how objects interact

Step 6: Discuss Extensibility & Trade-offs (5 min)
  → "What if we add feature X?" — show how design extends
  → Discuss trade-offs in your design choices
  → Mention concurrency if applicable
```

---

## 6. HLD — High-Level Design (System Design)

### 6.1 What Amazon Expects in HLD (SDE 3)

| Aspect | Expectation |
|--------|-------------|
| **Scope** | Drive the entire design conversation independently |
| **Depth** | Deep dive into 2-3 components (not just breadth) |
| **Trade-offs** | Articulate why you chose X over Y |
| **Scale** | Design for 100M+ users, millions of QPS |
| **Data** | Strong schema design, SQL vs NoSQL justification |
| **Availability** | CAP theorem awareness, SLA targets |
| **Real-world** | Reference AWS services (DynamoDB, SQS, S3, Kinesis, etc.) |

### 6.2 System Design Framework (Use This Every Time)

```
┌────────────────────────────────────────────────────────────┐
│  STEP 1: REQUIREMENTS (3-5 min)                           │
│  ├── Functional Requirements (list 4-5)                   │
│  ├── Non-Functional Requirements                          │
│  │   ├── Scale (DAU, QPS, storage)                        │
│  │   ├── Latency (p99 < X ms)                             │
│  │   ├── Availability (99.99%)                             │
│  │   └── Consistency (strong vs eventual)                  │
│  └── Out of Scope (explicitly state)                      │
├────────────────────────────────────────────────────────────┤
│  STEP 2: BACK-OF-ENVELOPE ESTIMATION (3-5 min)            │
│  ├── DAU → QPS (read & write separately)                  │
│  ├── Storage (per record size × records × retention)      │
│  ├── Bandwidth                                            │
│  └── Cache size (80/20 rule)                              │
├────────────────────────────────────────────────────────────┤
│  STEP 3: API DESIGN (3-5 min)                             │
│  ├── REST endpoints for each feature                      │
│  ├── Request/Response schemas                             │
│  └── Authentication (API key, OAuth)                      │
├────────────────────────────────────────────────────────────┤
│  STEP 4: DATA MODEL & SCHEMA (5 min)                      │
│  ├── Entity-relationship diagram                          │
│  ├── SQL vs NoSQL decision with justification             │
│  ├── Table schemas with indexes                           │
│  └── Partitioning / sharding key                          │
├────────────────────────────────────────────────────────────┤
│  STEP 5: HIGH-LEVEL ARCHITECTURE (10-15 min)              │
│  ├── Draw components: Client → LB → App Server → DB      │
│  ├── Add Cache layer (Redis/Memcached)                    │
│  ├── Add Message Queue (SQS/Kafka)                        │
│  ├── Add CDN (for static content)                         │
│  ├── Add Object Storage (S3)                              │
│  └── Show data flow with arrows                           │
├────────────────────────────────────────────────────────────┤
│  STEP 6: DEEP DIVE (10-15 min)                            │
│  ├── Pick 2-3 interesting components                      │
│  ├── Discuss algorithms (consistent hashing, etc.)        │
│  ├── Handle edge cases                                    │
│  ├── Discuss replication, failover                        │
│  └── Rate limiting, security                              │
├────────────────────────────────────────────────────────────┤
│  STEP 7: BOTTLENECKS & IMPROVEMENTS (3-5 min)             │
│  ├── Single points of failure                             │
│  ├── Scaling bottlenecks                                  │
│  ├── Monitoring & alerting                                │
│  └── Future improvements                                  │
└────────────────────────────────────────────────────────────┘
```

### 6.3 Back-of-Envelope Numbers (Memorize These)

| Metric | Value |
|--------|-------|
| 1 day | 86,400 seconds ≈ 100K seconds |
| 1 million requests/day | ~12 QPS |
| 1 billion requests/day | ~12,000 QPS |
| 1 char | 1 byte (ASCII) / 2 bytes (Unicode) |
| 1 UUID | 128 bits = 16 bytes |
| 1 URL | ~100 bytes |
| 1 tweet | ~300 bytes |
| 1 image (compressed) | ~200 KB |
| 1 short video | ~5 MB |
| Read from memory (RAM) | 100 ns |
| Read from SSD | 100 μs |
| Read from HDD | 10 ms |
| Send 1 KB over network | 10 μs (datacenter) |
| Round trip within datacenter | 0.5 ms |
| Round trip cross-continent | 150 ms |
| 1 server handles | ~1,000 concurrent connections |
| 1 Redis instance | ~100K QPS |
| 1 MySQL instance | ~10K QPS (reads) |

### 6.4 HLD Problem Set (20 Systems — Solve All)

#### Tier 1: Must-Do (Amazon asks these frequently)

**Problem 1: Design URL Shortener (TinyURL)**
```
Key Points:
- Write: Generate short URL → store mapping → return
- Read: Receive short URL → lookup → 301 redirect
- Encoding: Base62 (a-z, A-Z, 0-9) → 62^7 = 3.5 trillion URLs
- Storage: NoSQL (DynamoDB) → key: shortURL, value: longURL, userId, createdAt
- Caching: Redis for hot URLs (80/20 rule)
- Scale: 100M URLs/day write, 10:1 read/write ratio → 1B reads/day
- Partitioning: Hash-based on shortURL
- Collision handling: Check-and-retry or counter-based

Architecture:
Client → CDN → Load Balancer → App Service → Cache (Redis) → DB (DynamoDB)
                                      ↓
                              ID Generator (Snowflake/Counter)
```

**Problem 2: Design a Distributed Cache (Redis-like)**
```
Key Points:
- Operations: GET, SET, DELETE, TTL, eviction
- Eviction policies: LRU, LFU, TTL-based
- Partitioning: Consistent hashing across cache nodes
- Replication: Master-slave for read scaling
- Failure handling: Hash ring re-balancing
- Client-side vs server-side sharding
- Cache patterns: Cache-aside, Write-through, Write-behind

Architecture:
Client → Cache Client Library → Consistent Hash Ring
                                   ├── Node 1 (Master + Replica)
                                   ├── Node 2 (Master + Replica)
                                   └── Node 3 (Master + Replica)
```

**Problem 3: Design Amazon's Order Processing System**
```
Key Points:
- Services: Order, Inventory, Payment, Shipping, Notification
- Saga pattern for distributed transactions
- Event-driven architecture (SQS/SNS/Kafka)
- Idempotency for payment retries
- Order state machine: PLACED → CONFIRMED → SHIPPED → DELIVERED
- Inventory: Pessimistic vs optimistic locking
- CQRS: Separate read/write models for order history

Architecture:
Client → API Gateway → Order Service → SQS → Inventory Service
                                             → Payment Service
                                             → Shipping Service
                                             → Notification Service (SNS → Email/SMS)
         All services → Event Bus (Kafka) → Analytics / Audit
```

**Problem 4: Design Twitter / News Feed**
```
Key Points:
- Two approaches: Fan-out-on-write vs Fan-out-on-read
- Fan-out-on-write: On tweet, push to all followers' feeds (good for avg users)
- Fan-out-on-read: On feed request, pull from all followees (good for celebrities)
- Hybrid: Fan-out-on-write for normal users, fan-out-on-read for celebrities
- Feed storage: Pre-computed feed in Redis (sorted set by timestamp)
- Tweet storage: NoSQL (Cassandra) partitioned by userId

Architecture:
Client → LB → Tweet Service → Fanout Service → Feed Cache (Redis)
                    ↓                                    ↑
              Tweet Storage (Cassandra)         Feed Service
                    ↓
              Media Service → S3 + CDN
```

**Problem 5: Design a Chat System (WhatsApp/Messenger)**
```
Key Points:
- Real-time: WebSocket connections
- 1:1 and group messaging
- Online/offline status
- Message storage: Cassandra (partition by chatId, cluster by timestamp)
- Delivery: Sent → Delivered → Read receipts
- Offline messages: Store and forward
- Group: Fan-out to all members
- Push notifications for offline users (APNs/FCM)
- End-to-end encryption (bonus)

Architecture:
Client ←WebSocket→ Chat Server ←→ Message Queue (Kafka)
                        ↓                    ↓
                  Session Service      Message Storage (Cassandra)
                  (Redis: userId→serverId)
                        ↓
                  Presence Service (heartbeat)
                        ↓
                  Push Notification Service (SNS)
```

#### Tier 2: High-Priority

| # | System | Key Concepts |
|---|--------|-------------|
| 6 | **Design Uber/Lyft** | Geo-spatial indexing (QuadTree/Geohash), real-time matching, ETA, surge pricing |
| 7 | **Design YouTube/Netflix** | Video upload pipeline, transcoding, adaptive bitrate, CDN, recommendation |
| 8 | **Design Google Search** | Web crawler, inverted index, PageRank, serving layer |
| 9 | **Design Notification System** | Multi-channel (email, SMS, push), template engine, rate limiting, priority queue |
| 10 | **Design Rate Limiter** | Token bucket, sliding window, distributed (Redis), rules engine |
| 11 | **Design Instagram** | Photo upload → S3, feed generation (fan-out), like/comment counters, explore page |
| 12 | **Design Dropbox/Google Drive** | File chunking, dedup, sync (operational transform), versioning |
| 13 | **Design a Web Crawler** | BFS with politeness, URL frontier, dedup (Bloom filter), robots.txt |
| 14 | **Design a Key-Value Store** | Consistent hashing, WAL, SSTable, LSM-tree, gossip protocol |
| 15 | **Design an API Rate Limiter** | Fixed window, sliding window log, sliding window counter, token bucket |

#### Tier 3: Good-to-Have

| # | System | Key Concepts |
|---|--------|-------------|
| 16 | **Design Typeahead / Autocomplete** | Trie (distributed), top-K, offline data pipeline |
| 17 | **Design a Ticket Booking System** | Seat locking, distributed lock, idempotency, payment |
| 18 | **Design Metrics/Monitoring (Datadog)** | Time-series DB, aggregation pipeline, alerting |
| 19 | **Design a Payment System** | Idempotency, reconciliation, ledger, double-entry bookkeeping |
| 20 | **Design Amazon S3** | Object storage, metadata service, erasure coding, replication |

### 6.5 Key Distributed System Concepts

| Concept | What It Is | When to Use |
|---------|-----------|-------------|
| **Consistent Hashing** | Distribute data across nodes; minimal re-mapping on node add/remove | Cache clusters, DB sharding |
| **CAP Theorem** | Can only have 2 of 3: Consistency, Availability, Partition Tolerance | Every system design discussion |
| **PACELC Theorem** | Extension of CAP: even without Partition, trade Latency vs Consistency | DynamoDB (PA/EL), MySQL (PC/EC) |
| **Leader Election** | One node acts as leader (Raft, Paxos, ZooKeeper) | DB primary, task scheduler |
| **Bloom Filter** | Probabilistic set membership (false positives, no false negatives) | URL dedup in crawler, cache miss optimization |
| **Gossip Protocol** | Peer-to-peer state dissemination | Node failure detection, membership |
| **CQRS** | Separate read and write models | High-read systems, event sourcing |
| **Event Sourcing** | Store events, not state; replay to reconstruct | Order systems, audit logs |
| **Saga Pattern** | Distributed transaction via sequence of local transactions + compensations | Order → Payment → Inventory |
| **Circuit Breaker** | Stop calling failing service, fallback/fail fast | Microservice resilience |
| **Idempotency** | Same request multiple times = same result | Payment processing, API retries |
| **Sharding** | Split data across multiple DBs | Horizontal scaling |
| **Replication** | Copy data to multiple nodes | Fault tolerance, read scaling |
| **CDN** | Cache static content at edge locations | Images, videos, JS/CSS |
| **Message Queue** | Async communication between services | Decoupling, buffering, retries |

### 6.6 AWS Services to Reference in Design

| Category | Service | Use Case |
|----------|---------|----------|
| Compute | EC2, ECS, Lambda | App servers, microservices, serverless |
| Database | RDS, DynamoDB, Aurora, ElastiCache | Relational, NoSQL, caching |
| Storage | S3, EBS, EFS | Object storage, block storage |
| Messaging | SQS, SNS, Kinesis, MSK (Kafka) | Queue, pub/sub, streaming |
| Search | OpenSearch (Elasticsearch) | Full-text search, log analytics |
| CDN | CloudFront | Static content delivery |
| LB | ALB, NLB | Load balancing |
| DNS | Route 53 | DNS, health checks, routing |
| Auth | Cognito, IAM | User auth, service auth |
| Monitoring | CloudWatch, X-Ray | Metrics, tracing |

---

## 7. Behavioral / Leadership Principles Round

### 7.1 Amazon's 16 Leadership Principles

> **At SDE 3 level, you MUST demonstrate depth in ALL of these.**

| # | Leadership Principle | One-Liner | SDE 3 Signal |
|---|---------------------|-----------|-------------|
| 1 | **Customer Obsession** | Start with the customer and work backwards | Led customer-impacting decisions, used data to improve UX |
| 2 | **Ownership** | Think long-term, don't sacrifice for short-term | Owned services end-to-end, including ops and oncall |
| 3 | **Invent and Simplify** | Innovate and find ways to simplify | Proposed and built novel solutions; simplified architecture |
| 4 | **Are Right, A Lot** | Good judgment, seek diverse perspectives | Made correct technical decisions, listened to others |
| 5 | **Learn and Be Curious** | Never stop learning | Learned new tech stacks, explored beyond comfort zone |
| 6 | **Hire and Develop the Best** | Raise the bar, mentor others | Mentored junior engineers, improved interview process |
| 7 | **Insist on the Highest Standards** | Relentlessly high standards | Code review culture, operational excellence |
| 8 | **Think Big** | Create bold direction, inspire results | Proposed ambitious technical roadmaps |
| 9 | **Bias for Action** | Speed matters, take calculated risks | Made decisions quickly with imperfect information |
| 10 | **Frugality** | Do more with less | Optimized costs, efficient resource usage |
| 11 | **Earn Trust** | Listen, speak candidly, treat others respectfully | Transparent communication, admitted mistakes |
| 12 | **Dive Deep** | Stay connected to details, audit frequently | Debugged complex production issues, knew the metrics |
| 13 | **Have Backbone; Disagree and Commit** | Challenge decisions, then commit wholly | Pushed back respectfully on bad technical decisions |
| 14 | **Deliver Results** | Focus on key inputs, deliver with quality and timely | Shipped projects on time despite obstacles |
| 15 | **Strive to be Earth's Best Employer** | Lead with empathy, create safe environment | Supported team wellbeing, inclusive practices |
| 16 | **Success and Scale Bring Broad Responsibility** | Make better every day, for customers, employees, community | Considered operational impact, security, sustainability |

### 7.2 STAR Method (Use This for Every Answer)

```
S - Situation: Set the scene. Where were you? What was the context?
T - Task:      What was your specific responsibility?
A - Action:    What did YOU do? (Use "I", not "we")
R - Result:    What was the measurable outcome?
    + Reflection: What did you learn?
```

**SDE 3 Tips:**
- Show **leadership & influence** (you led, mentored, convinced)
- Show **technical depth** (architecture decisions, debugging complex issues)
- Show **cross-team collaboration** (worked with PM, other teams)
- Quantify results (latency reduced by 40%, saved $200K/year, zero downtime)

### 7.3 Story Bank Template

> **Prepare 12-15 stories. Each story should map to 2-3 LPs.**

| Story # | Title | LPs Covered | STAR Summary |
|---------|-------|------------|--------------|
| 1 | Redesigned payment service architecture | Ownership, Think Big, Deliver Results | Led migration from monolith to microservices, reduced latency 60% |
| 2 | Mentored 3 junior engineers | Hire & Develop Best, Earn Trust | Created onboarding program, all 3 promoted within 1 year |
| 3 | Disagreed with manager on database choice | Backbone, Are Right A Lot | Presented data showing NoSQL was better fit, team agreed, saved $100K |
| 4 | Debugged production outage at 2AM | Dive Deep, Customer Obsession | Found race condition in distributed lock, fixed in 2 hours |
| 5 | Simplified complex deployment pipeline | Invent & Simplify, Frugality | Reduced deployment time from 4 hours to 20 minutes |
| 6 | Took ownership of legacy service nobody wanted | Ownership, Bias for Action | Modernized service, improved reliability from 99.5% to 99.99% |
| 7 | Pushed back on unrealistic deadline | Backbone, Highest Standards | Negotiated scope, delivered quality product on adjusted timeline |
| 8 | Learned Kubernetes to solve scaling problem | Learn & Be Curious, Deliver Results | Self-taught K8s, containerized services, auto-scaling saved infra costs |
| 9 | _(Fill with your own story)_ | | |
| 10 | _(Fill with your own story)_ | | |
| 11 | _(Fill with your own story)_ | | |
| 12 | _(Fill with your own story)_ | | |

### 7.4 Top 30 Behavioral Questions (Expect 4-6 per round)

**Customer Obsession**
1. Tell me about a time you went above and beyond for a customer.
2. Describe a time when you had to balance customer needs with business constraints.
3. Give me an example of when you used customer feedback to drive a technical decision.

**Ownership**
4. Tell me about a project you owned end-to-end.
5. Describe a time you took on something outside your responsibility.
6. Tell me about a time you saw a problem and proactively fixed it.

**Invent and Simplify**
7. Tell me about an innovative solution you came up with.
8. Describe a time you simplified a complex system or process.

**Are Right, A Lot**
9. Tell me about a time you had to make a decision with incomplete information.
10. Describe a situation where you changed your mind based on new data.

**Learn and Be Curious**
11. Tell me about a time you learned something new to solve a problem.
12. How do you stay current with technology trends?

**Hire and Develop the Best**
13. Tell me about a time you mentored someone.
14. Describe how you helped improve your team's engineering practices.

**Insist on the Highest Standards**
15. Tell me about a time you refused to cut corners.
16. Describe a time you raised the quality bar for your team.

**Think Big**
17. Tell me about the most ambitious technical project you've proposed.
18. Describe a time you thought about long-term impact over short-term gain.

**Bias for Action**
19. Tell me about a time you made a quick decision that paid off.
20. Describe a time when waiting would have been costly.

**Frugality**
21. Tell me about a time you achieved more with fewer resources.
22. Describe a cost optimization you implemented.

**Earn Trust**
23. Tell me about a time you had to deliver bad news.
24. Describe a situation where you had to rebuild trust with a stakeholder.

**Dive Deep**
25. Tell me about a complex bug you debugged.
26. Describe a time when you had to analyze data to find the root cause.

**Have Backbone; Disagree and Commit**
27. Tell me about a time you disagreed with your manager or team.
28. Describe a time when the team went a different direction and you committed.

**Deliver Results**
29. Tell me about a time you delivered a project under a tight deadline.
30. Describe a time when you had to overcome significant obstacles to deliver.

### 7.5 How to Practice

```
Week 1-2: Write all 12 stories using STAR template
Week 3-4: Practice each story out loud (2 min per story)
Week 5+:  Do mock behavioral interviews with a friend
          Record yourself and review
          Time yourself (each answer: 2-3 minutes)
```

---

## 8. Bar Raiser Round

### 8.1 What is the Bar Raiser?

- **Who:** A specially trained interviewer from a DIFFERENT team
- **Purpose:** Ensure the candidate raises the bar (is better than 50% of current SDE 3s at Amazon)
- **Format:** Usually a mix of coding/design + behavioral
- **Power:** Can veto a hire even if all other interviewers say yes

### 8.2 What Makes It Different

| Aspect | Regular Round | Bar Raiser Round |
|--------|--------------|-----------------|
| Focus | Technical skills | Holistic assessment (technical + LP + culture) |
| Questions | Standard LP questions | Deeper follow-ups, "peel the onion" |
| Probing | Moderate | Very deep — "tell me more", "what specifically did YOU do?" |
| Expectations | Meet the bar | RAISE the bar |

### 8.3 How to Prepare

1. **Expect deep follow-ups:** They will ask "why?" 3-4 times. Know your stories deeply.
2. **Be specific:** Don't say "we improved performance." Say "I identified the N+1 query problem in the order service, added batch loading, and reduced p99 latency from 800ms to 120ms."
3. **Show leadership at scale:** Cross-team influence, mentoring, raising standards.
4. **Be honest:** If you don't know something, say so. Bar raisers detect BS easily.
5. **Show growth mindset:** Discuss failures and what you learned.

### 8.4 Bar Raiser Question Patterns

```
1. "Tell me about a time you failed. What did you learn?"
   → Tests: Earn Trust, Learn and Be Curious

2. "Tell me about the most complex technical problem you've solved."
   → Tests: Dive Deep, Deliver Results
   → They will ask for EXTREME detail

3. "Tell me about a time you influenced a decision across teams."
   → Tests: Ownership, Have Backbone, Think Big
   → SDE 3 specific — must show influence without authority

4. "Walk me through a system you designed. Why did you make each choice?"
   → Tests: Are Right A Lot, Invent and Simplify
   → Be ready for "what would you do differently?"

5. "Tell me about a time you had to make a trade-off between quality and speed."
   → Tests: Highest Standards, Bias for Action, Deliver Results
```

---

## 9. Resume & Application Strategy

### 9.1 Resume Format for Amazon SDE 3

```
┌──────────────────────────────────────────────┐
│  WASIM AKRAM                                 │
│  Senior Software Engineer                    │
│  Email | Phone | LinkedIn | GitHub           │
├──────────────────────────────────────────────┤
│  SUMMARY (2-3 lines)                         │
│  8+ years of experience in building          │
│  scalable distributed systems. Led design    │
│  and delivery of services handling 10M+      │
│  daily requests. Mentored 5+ engineers.      │
├──────────────────────────────────────────────┤
│  EXPERIENCE                                  │
│                                              │
│  Company A — Senior Software Engineer        │
│  Date - Present                              │
│  • Led design of [system], reducing latency  │
│    by 60% and handling 5M daily requests     │
│  • Mentored 3 engineers; all promoted in 1yr │
│  • Drove migration from monolith to          │
│    microservices, improving deploy frequency  │
│    from weekly to 10x/day                    │
│  • (Use METRICS in every bullet)             │
│                                              │
│  Company B — Software Engineer               │
│  Date - Date                                 │
│  • Built [feature], increasing revenue by 15%│
│  • Designed data pipeline processing 1TB/day │
├──────────────────────────────────────────────┤
│  SKILLS                                      │
│  Languages: Java, Python, Go, TypeScript     │
│  Databases: PostgreSQL, DynamoDB, Redis      │
│  Cloud: AWS (EC2, S3, SQS, Lambda, ECS)     │
│  Tools: Docker, Kubernetes, Kafka, CI/CD     │
├──────────────────────────────────────────────┤
│  EDUCATION                                   │
│  B.Tech/M.Tech in CS — University — Year     │
└──────────────────────────────────────────────┘
```

**Rules:**
- 1-2 pages max
- Every bullet starts with action verb + includes metric
- Tailor for Amazon: mention scale, ownership, customer impact
- No buzzword stuffing — be specific

### 9.2 Application Strategy

```
1. Get a REFERRAL (50% higher callback rate)
   → LinkedIn: Connect with Amazon employees, ask for referral
   → Ask ex-colleagues who moved to Amazon

2. Apply to 2-3 teams simultaneously
   → Amazon allows multiple team applications

3. Target teams hiring for L6 specifically
   → Check Amazon.jobs, LinkedIn, Blind

4. Timing: Apply 10-12 weeks before your target interview date

5. Recruiter call:
   → Be ready to explain your background in 2 minutes
   → Ask about team, scope, expectations for L6
   → Confirm interview format and timeline
```

---

## 10. Salary & Compensation (L6)

### 10.1 Amazon L6 Compensation Breakdown (India)

| Component | Range (INR) | Notes |
|-----------|-------------|-------|
| Base Salary | ₹45L - ₹75L /yr | Capped at ₹1.6Cr globally for L6 |
| Sign-on Bonus | ₹15L - ₹40L | Paid over 1-2 years (higher in Y1-Y2) |
| RSU (Stock) | ₹30L - ₹80L /yr (at vest) | 4-year vest: 5/15/40/40 |
| **Total Comp** | **₹90L - ₹1.8Cr /yr** | Varies by team and negotiation |

### 10.2 Amazon L6 Compensation Breakdown (USA)

| Component | Range (USD) | Notes |
|-----------|-------------|-------|
| Base Salary | $150K - $210K /yr | |
| Sign-on Bonus | $50K - $150K | Year 1 & 2 (to offset RSU vesting) |
| RSU (Stock) | $100K - $400K (total grant) | 4-year vest: 5/15/40/40 |
| **Total Comp** | **$250K - $450K /yr** | |

### 10.3 Negotiation Tips

```
1. Always negotiate — Amazon expects it
2. Leverage competing offers (Google, Microsoft, Meta)
3. Focus on RSU and sign-on (base has a hard cap)
4. Ask for more RSUs if you believe in Amazon stock
5. Request relocation bonus if applicable
6. Don't reveal current salary — state "expected compensation"
7. Use levels.fyi for data-driven negotiation
```

---

## 11. Day-of-Interview Checklist

### Pre-Interview (1 Week Before)
- [ ] Confirm interview schedule, format, and interviewer names
- [ ] Test your webcam, mic, and internet (for virtual)
- [ ] Have a whiteboard or paper + pen ready
- [ ] Review your STAR stories (read once daily)
- [ ] Do 1 easy LeetCode problem daily (confidence building)
- [ ] Prepare your "tell me about yourself" (2 min pitch)
- [ ] Sleep well — no late-night cramming

### Day-of
- [ ] Eat a good meal 1-2 hours before
- [ ] Have water nearby
- [ ] Keep your resume printed (or open)
- [ ] Have a notepad for jotting interviewer questions
- [ ] Log in 5 minutes early
- [ ] Wear professional but comfortable attire
- [ ] Smile and be energetic

### During Each Round
- [ ] Clarify the problem before jumping in (2-3 minutes)
- [ ] Think out loud — explain your thought process
- [ ] For coding: state brute force → optimize → code → test
- [ ] For design: follow the framework (Requirements → Estimation → API → Schema → Architecture → Deep Dive)
- [ ] For behavioral: use STAR, be specific, quantify results
- [ ] Ask 1-2 thoughtful questions at the end of each round

### Post-Interview
- [ ] Send thank-you email to recruiter
- [ ] Note down questions asked (for future reference)
- [ ] Don't overthink — you can't change what happened
- [ ] Follow up with recruiter after 1 week if no response

---

## 12. Cheat Sheets

### 12.1 Complexity Quick Reference

```
O(1)        → HashMap lookup, array access
O(log n)    → Binary search, balanced BST operations
O(n)        → Linear scan, single loop
O(n log n)  → Merge sort, heap sort, sorting-based problems
O(n²)       → Nested loops, brute force on 2D
O(2ⁿ)       → Subsets, recursive without memoization
O(n!)       → Permutations
```

### 12.2 System Design Numbers Quick Reference

```
1 Million  = 10^6  → ~12 QPS (for daily requests)
1 Billion  = 10^9  → ~12,000 QPS
1 Trillion = 10^12

1 KB = 1,000 bytes
1 MB = 10^6 bytes
1 GB = 10^9 bytes
1 TB = 10^12 bytes
1 PB = 10^15 bytes

1 server: ~500 concurrent connections (conservative)
1 Redis:  ~100,000 QPS
1 MySQL:  ~10,000 read QPS (indexed queries)
1 Cassandra node: ~10,000-50,000 QPS

SLA:
99%    → 3.65 days downtime/year
99.9%  → 8.76 hours downtime/year
99.99% → 52.6 minutes downtime/year
99.999%→ 5.26 minutes downtime/year
```

### 12.3 LP Quick Reference Card

```
When telling a story, mentally check:
✓ Did I say "I" (not "we")?
✓ Did I explain the SITUATION clearly?
✓ Did I state MY specific TASK?
✓ Did I describe MY ACTIONS in detail?
✓ Did I give MEASURABLE RESULTS?
✓ Did I mention what I LEARNED?
✓ Is my answer 2-3 minutes? (not 30 seconds, not 10 minutes)
```

### 12.4 SQL vs NoSQL Decision Guide

```
Choose SQL (RDS, Aurora, PostgreSQL) when:
  ✓ Data has complex relationships (joins)
  ✓ Strong consistency is required (financial transactions)
  ✓ ACID transactions are needed
  ✓ Data schema is well-defined and stable

Choose NoSQL when:
  ✓ High write throughput needed (DynamoDB, Cassandra)
  ✓ Flexible schema (documents vary in structure)
  ✓ Horizontal scaling is critical
  ✓ Key-value or document access patterns
  ✓ Eventual consistency is acceptable

Choose Redis (in-memory) when:
  ✓ Sub-millisecond latency needed
  ✓ Caching layer
  ✓ Session storage, leaderboards, rate limiting
  ✓ Pub/sub messaging
```

### 12.5 Common System Design Components

```
Client → CDN (static assets)
      → DNS (Route 53)
      → Load Balancer (ALB)
      → API Gateway (rate limiting, auth)
      → Application Servers (stateless, auto-scaled)
      → Cache (Redis/Memcached)
      → Database (SQL / NoSQL)
      → Object Storage (S3)
      → Message Queue (SQS/Kafka)
      → Search Engine (Elasticsearch)
      → Notification Service (SNS)
      → Monitoring (CloudWatch)
```

---

## 13. Resources & Tools

### Books
| Book | For |
|------|-----|
| **Designing Data-Intensive Applications** (Martin Kleppmann) | HLD — The Bible of system design |
| **System Design Interview** Vol 1 & 2 (Alex Xu) | HLD — Structured problem walkthroughs |
| **Head First Design Patterns** | LLD — Design patterns made easy |
| **Clean Code** (Robert C. Martin) | LLD — Code quality |
| **Cracking the Coding Interview** (Gayle McDowell) | DSA + Behavioral |
| **Elements of Programming Interviews** | DSA — Hard problems |

### Online Platforms
| Platform | Purpose | Link |
|----------|---------|------|
| LeetCode | DSA practice | leetcode.com |
| NeetCode | Curated DSA roadmap + video explanations | neetcode.io |
| System Design Primer | HLD study | github.com/donnemartin/system-design-primer |
| Educative — Grokking System Design | HLD course | educative.io |
| Educative — Grokking OOD | LLD course | educative.io |
| Exponent | Mock interviews + LP prep | tryexponent.com |
| Pramp | Free mock interviews | pramp.com |
| levels.fyi | Salary benchmarks | levels.fyi |
| Blind | Anonymous engineer discussions | teamblind.com |

### YouTube Channels
| Channel | Best For |
|---------|----------|
| **NeetCode** | DSA problem walkthroughs |
| **Gaurav Sen** | System design (HLD) |
| **Tech Dummies (Tushar Roy)** | DP and graph problems |
| **Exponent** | Behavioral + system design mock interviews |
| **Concept && Coding** | LLD problems in Java |
| **sudoCODE** | System design (HLD) |
| **ByteByteGo** (Alex Xu) | System design animations |

### Mock Interview Platforms
| Platform | Cost | Format |
|----------|------|--------|
| Pramp | Free | Peer-to-peer |
| Interviewing.io | $$$ | Real engineers from FAANG |
| Exponent | $$ | Recorded + peer |
| Meetapro | $$ | 1-on-1 with Amazon interviewers |

---

## Final Words

```
┌────────────────────────────────────────────────────┐
│                                                    │
│   The secret to cracking Amazon SDE 3:             │
│                                                    │
│   1. Consistency > Intensity                       │
│      (2 hrs daily beats 10 hrs on weekends)        │
│                                                    │
│   2. Practice out loud                             │
│      (Coding, design, and behavioral)              │
│                                                    │
│   3. Mock interviews are non-negotiable            │
│      (Do at least 5 full mocks before the real)    │
│                                                    │
│   4. Show OWNERSHIP and INFLUENCE                  │
│      (This is what separates L5 from L6)           │
│                                                    │
│   5. Be yourself. Be specific. Be honest.          │
│                                                    │
│   You've got this. Now go execute. 🚀              │
│                                                    │
└────────────────────────────────────────────────────┘
```

---

*Last updated: March 2026*
*Document version: 1.0*
*Author: Wasim Akram — Amazon SDE 3 Preparation Guide*
