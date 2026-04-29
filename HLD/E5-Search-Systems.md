# E5 — Search Systems

> **Section:** Data Processing | **Level:** Advanced | **Interview Frequency:** ★★★★☆

---

## 1. Concept Definition

**Beginner:** Regular database queries find exact matches ("WHERE name = 'Alice'"). Search systems find relevant matches based on meaning ("find documents about machine learning"). Elasticsearch, Solr, and OpenSearch are the main tools.

**Technical:** Full-text search uses inverted indexes (maps: term -> list of document IDs), relevance scoring (TF-IDF, BM25), tokenization, stemming, and faceting. Elasticsearch is a distributed search engine built on Apache Lucene, providing horizontal scalability and near-real-time indexing.

---

## 2. Real-World Analogy

**Book index vs. reading every page:**
- LIKE '%machine learning%' in SQL = reading every page of every book looking for the phrase.
- Inverted index = book index at the back: "machine learning: pages 12, 45, 78" — go directly to those pages.
- 10,000x faster for large datasets.

---

## 3. Visual Diagram

```
INVERTED INDEX:
Documents:
  Doc 1: "fast database query optimization"
  Doc 2: "database performance tuning"
  Doc 3: "query performance analysis"

Inverted Index (term -> document list):
  "fast"        -> [Doc1]
  "database"    -> [Doc1, Doc2]
  "query"       -> [Doc1, Doc3]
  "optimization"-> [Doc1]
  "performance" -> [Doc2, Doc3]
  "tuning"      -> [Doc2]
  "analysis"    -> [Doc3]

Query: "database performance"
  "database" -> [Doc1, Doc2]
  "performance" -> [Doc2, Doc3]
  Intersection/union -> Doc2 appears in both -> highest relevance
  Scored by BM25: Doc2 = 0.9, Doc1 = 0.6, Doc3 = 0.5

ELASTICSEARCH ARCHITECTURE:
Cluster
  Node 1 [Primary Shard 0] [Replica Shard 1]
  Node 2 [Primary Shard 1] [Replica Shard 2]
  Node 3 [Primary Shard 2] [Replica Shard 0]

Index "products" -> 3 primary shards -> each shard = 1 Lucene index
Query: fan-out to all shards -> merge + rank results
Write: route to primary shard (by doc_id hash) -> replicate
```

---

## 4. Deep Technical Explanation

### Tokenization and Analysis
- **Tokenizer:** split "Quick brown fox" into ["Quick", "brown", "fox"]
- **Lowercase filter:** ["quick", "brown", "fox"]
- **Stop words:** remove "the", "is", "and"
- **Stemmer:** "running" -> "run", "optimization" -> "optim"
- **Analyzer** = tokenizer + filters — must match at index and query time

### TF-IDF vs BM25
- **TF (Term Frequency):** how often term appears in this document
- **IDF (Inverse Document Frequency):** how rare the term is across all documents (rarer = more valuable)
- **TF-IDF score:** TF × IDF — high when term is frequent in doc but rare globally
- **BM25 (Best Match 25):** improvement over TF-IDF, handles long documents better, saturation of TF, is Elasticsearch default

### Elasticsearch Aggregations
- Count, sum, avg, min, max across matching documents
- **Bucket aggregations:** group by field (facets — "count by category")
- **Metric aggregations:** statistics within a group
- Example: "count products by category, for products matching 'laptop'"

### Sync Strategies (DB -> Elasticsearch)
1. **Dual write:** application writes to DB and ES simultaneously (risk: inconsistency on partial failure)
2. **CDC (Change Data Capture):** read MySQL/PostgreSQL WAL -> Kafka -> ES consumer (reliable, low latency)
3. **Polling:** background job reads DB changes since last run -> index to ES (simple, higher latency)
4. **Logstash/Debezium:** dedicated tools for DB -> ES synchronization

---

## 5. Code Example

```php
use Elastic\Elasticsearch\ClientBuilder;

class ProductSearchService {
    private $client;
    
    public function __construct() {
        $this->client = ClientBuilder::create()
            ->setHosts(['elasticsearch:9200'])
            ->build();
    }
    
    public function indexProduct(array $product): void {
        $this->client->index([
            'index' => 'products',
            'id'    => $product['id'],
            'body'  => [
                'name'        => $product['name'],
                'description' => $product['description'],
                'category'    => $product['category'],
                'price'       => $product['price'],
                'brand'       => $product['brand'],
                'tags'        => $product['tags'],
                'created_at'  => date('c'),
            ],
        ]);
    }
    
    public function search(string $query, array $filters = [], int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        
        $body = [
            'from'  => $offset,
            'size'  => $perPage,
            'query' => [
                'bool' => [
                    // Full-text search across multiple fields
                    'must'   => [
                        'multi_match' => [
                            'query'  => $query,
                            'fields' => ['name^3', 'brand^2', 'description', 'tags'],
                            // ^3 = boost name field 3x in relevance scoring
                            'type'   => 'best_fields',
                            'fuzziness' => 'AUTO',  // typo tolerance
                        ],
                    ],
                    // Exact filters (don't affect relevance score)
                    'filter' => array_filter([
                        isset($filters['category']) ? ['term' => ['category' => $filters['category']]] : null,
                        isset($filters['price_max']) ? ['range' => ['price' => ['lte' => $filters['price_max']]]] : null,
                    ]),
                ],
            ],
            // Aggregations for faceted navigation
            'aggs' => [
                'by_category' => ['terms' => ['field' => 'category.keyword', 'size' => 20]],
                'price_stats' => ['stats' => ['field' => 'price']],
            ],
            // Highlighted snippets in results
            'highlight' => [
                'fields' => ['name' => new \stdClass(), 'description' => new \stdClass()],
            ],
        ];
        
        $response = $this->client->search(['index' => 'products', 'body' => $body]);
        
        return [
            'total'       => $response['hits']['total']['value'],
            'hits'        => array_map(fn($h) => array_merge($h['_source'], ['highlight' => $h['highlight'] ?? []]),
                                $response['hits']['hits']),
            'aggregations' => $response['aggregations'] ?? [],
        ];
    }
}
```

---

## 7. Interview Q&A

**Q1: How would you keep Elasticsearch in sync with your primary database?**
> Best approach: CDC with Debezium. Debezium reads the MySQL/PostgreSQL WAL (binary log / replication slot), publishes changes to Kafka topics. An Elasticsearch Kafka consumer indexes the changes. Advantages: (1) no dual-write risk (DB is the source of truth); (2) sub-second latency; (3) handles deletes and updates correctly; (4) replayable (rebuild ES index by replaying Kafka from the beginning). Simpler alternative: application writes to both DB and ES in a background job (polling every 30 seconds) -- acceptable for non-realtime search.

**Q2: Why is Elasticsearch not a good replacement for your primary database?**
> ES lacks: (1) ACID transactions (no multi-document atomic writes); (2) Strong consistency (near-real-time, ~1 second indexing delay); (3) Relational joins; (4) Unique constraints; (5) Foreign keys. ES is optimized for search and analytics -- reading documents by full-text query. Primary databases (PostgreSQL) are optimized for transactional writes with consistency guarantees. Use ES as a read replica for search use cases, with your primary DB as the source of truth.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| Inverted index: term -> doc list -- core of full-text search      |
| BM25: relevance scoring (Elasticsearch default, better than TF-IDF)|
| Analyzers must match at index time AND query time                 |
| Aggregations = real-time facets and analytics                     |
| Sync DB->ES: CDC (Debezium) is best -- reliable, low latency      |
| ES is NOT a primary DB -- no ACID, eventual consistency           |
+--------------------------------------------------------------------+
```
