# C13 — Geospatial Indexing

> **Section:** Distributed Systems Patterns | **Level:** Advanced | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Geospatial indexing answers the question "what is near me?" efficiently. Without it, finding all restaurants within 5km would require calculating the distance from every restaurant in the database — extremely slow. Geospatial indexes precompute spatial structure to answer proximity queries in milliseconds.

**Technical:** Geospatial indexing organizes geographic data (latitude/longitude) into data structures that enable efficient spatial queries: nearest-neighbor, radius search, bounding box. Common approaches: Geohash (grid cells), R-Tree (bounding rectangles), S2 (spherical geometry cells).

---

## 2. Real-World Analogy

**Postal code zones:**
- Instead of measuring exact distance to every address, group addresses into zones (postal codes)
- "Find restaurants in my area" = find all postal codes within distance → find all restaurants in those codes
- Geohash works similarly: divide Earth into a grid of cells, each with a short string code

---

## 3. Visual Diagram

```
GEOHASH:
Earth divided into grid, each cell gets a code
Precision: 1 char = ~5000km, 4 chars = ~40km, 6 chars = ~1.2km, 8 chars = ~38m

Mumbai: 18.9388°N, 72.8353°E → Geohash: "te7ud3vg"
Delhi:  28.6517°N, 77.2219°E → Geohash: "ttnfv23r"

Nearby places share common prefix:
"te7ud" area includes: Bandra, Andheri, Juhu (all Mumbai West)

QUERY: "Find restaurants within 5km of me"
1. Compute my geohash: "te7ud3"
2. Find adjacent cells (8 neighbors + self = 9 cells)
3. Query DB: WHERE geohash LIKE 'te7ud%' (index scan, not full scan)
4. Filter by exact distance calculation (refine false positives)

REDIS GEO (Sorted Set with Geohash scores):
GEOADD locations 72.8353 18.9388 "restaurant_A"
GEOSEARCH locations FROMMEMBER restaurant_A BYRADIUS 5 km ASC COUNT 10
→ returns nearest 10 restaurants within 5km radius
```

---

## 4. Deep Technical Explanation

### Approaches

**Geohash:**
- Encode lat/lng as interleaved binary → base32 string
- Nearby points share prefix (usually — edge effects at cell boundaries)
- Variable precision: length = km radius (6 chars ≈ 1.2km radius)
- Used by: Redis GEOADD, MongoDB 2dsphere, ElasticSearch geo_point

**R-Tree:**
- Spatial index storing bounding rectangles
- Queries: "find all points within bounding box"
- PostgreSQL PostGIS uses GIST index (Generalized Inverted Search Tree, R-Tree based)

**S2 (Google's approach):**
- Maps Earth onto a cube, projects onto sphere
- 31 levels of precision (level 30 = 0.2mm²)
- No edge effects (unlike Geohash)
- Used by: Google Maps, Uber, Lyft, DynamoDB

**H3 (Uber's hexagonal grid):**
- Hexagonal cells (better spatial properties than square cells)
- 15 levels of precision
- Open-source
- Used by: Uber, Foursquare, Airbnb

### Quadtree
- Recursively divide 2D space into 4 quadrants
- Each leaf contains at most N points
- Good for non-uniform distributions (dense cities + sparse rural)
- Used for: map rendering LOD, spatial databases

---

## 5. Code Example

```php
// Geohash encoding/decoding (simplified)
class Geohash {
    private const BASE32 = '0123456789bcdefghjkmnpqrstuvwxyz';
    
    public function encode(float $lat, float $lng, int $precision = 7): string {
        [$minLat, $maxLat] = [-90, 90];
        [$minLng, $maxLng] = [-180, 180];
        
        $hash  = '';
        $bits  = 0;
        $even  = true;  // alternate between lng and lat
        $code  = 0;
        
        while (strlen($hash) < $precision) {
            if ($even) {
                $mid = ($minLng + $maxLng) / 2;
                if ($lng > $mid) {
                    $code  = ($code << 1) + 1;
                    $minLng = $mid;
                } else {
                    $code  = $code << 1;
                    $maxLng = $mid;
                }
            } else {
                $mid = ($minLat + $maxLat) / 2;
                if ($lat > $mid) {
                    $code  = ($code << 1) + 1;
                    $minLat = $mid;
                } else {
                    $code  = $code << 1;
                    $maxLat = $mid;
                }
            }
            
            $even = !$even;
            $bits++;
            
            if ($bits === 5) {
                $hash  .= self::BASE32[$code];
                $bits  = 0;
                $code  = 0;
            }
        }
        
        return $hash;
    }
}

// Redis-based nearby restaurant search (using Redis GEO commands via Predis)
class NearbyRestaurantService {
    private \Predis\Client $redis;
    
    public function addRestaurant(int $id, float $lat, float $lng): void {
        $this->redis->geoadd('restaurants', $lng, $lat, "restaurant:{$id}");
    }
    
    public function findNearby(float $lat, float $lng, int $radiusKm = 5, int $limit = 20): array {
        // GEOSEARCH restaurants FROMLONLAT <lng> <lat> BYRADIUS <km> km ASC COUNT <n> WITHCOORD WITHDIST
        $results = $this->redis->geosearch(
            'restaurants',
            ['FROMLONLAT', $lng, $lat],
            ['BYRADIUS', $radiusKm, 'km'],
            ['ASC'],
            ['COUNT', $limit],
            'WITHCOORD',
            'WITHDIST'
        );
        
        return array_map(function($r) {
            return [
                'id'       => str_replace('restaurant:', '', $r[0]),
                'distance' => (float) $r[1],  // km
                'lng'      => (float) $r[2][0],
                'lat'      => (float) $r[2][1],
            ];
        }, $results);
    }
}

// PostgreSQL PostGIS (for complex spatial queries)
// Find all restaurants within 5km, sorted by distance
// SELECT id, name, 
//   ST_Distance(location::geography, ST_MakePoint(72.8353, 18.9388)::geography) AS dist_m
// FROM restaurants
// WHERE ST_DWithin(location::geography, ST_MakePoint(72.8353, 18.9388)::geography, 5000)
// ORDER BY dist_m
// LIMIT 20;
```

---

## 7. Interview Q&A

**Q1: How would you design a "nearby drivers" feature for a ride-sharing app?**
> Drivers update their location every 5 seconds → stored in Redis GEO (geohash under the hood). Rider requests pickup → GEOSEARCH within 5km radius → top 10 nearest drivers returned instantly (Redis GEO is O(N+log M) for radius search). For city-scale: partition by city (separate Redis key per city). For global scale: shard by geohash prefix (all drivers in "te7" cell on same shard). Driver location history: write to Kafka → persist to Cassandra time-series for analytics.

**Q2: Why is Geohash sometimes insufficient for boundary queries?**
> Geohash cells are square. At cell boundaries, two physically adjacent points may have completely different geohash prefixes (e.g., on opposite sides of a cell border). A 5km radius search may need to check 8 neighboring cells in addition to the target cell to catch all nearby points near the cell boundary. This is called the "edge effect." Google's S2 and Uber's H3 have better spatial properties that reduce this issue.

---

## 8. Key Takeaways

```
┌────────────────────────────────────────────────────────────────────┐
│ ✓ Geohash: encode lat/lng as string prefix = nearby points        │
│ ✓ Always check 8 neighboring cells to handle boundary edge effects │
│ ✓ Redis GEO: built-in GEOADD/GEOSEARCH with geohash internally    │
│ ✓ PostGIS: full spatial SQL — complex polygon, routing queries    │
│ ✓ S2/H3: better spherical accuracy, no edge effects               │
│ ✓ For nearby search: geohash filter + exact distance refinement   │
└────────────────────────────────────────────────────────────────────┘
```
