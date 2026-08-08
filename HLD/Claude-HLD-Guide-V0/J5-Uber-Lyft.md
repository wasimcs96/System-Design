# J5 — Design Uber / Lyft (Ride-Sharing)

> **Section:** Case Studies | **Difficulty:** Hard | **Interview Frequency:** ★★★★★

---

## 1. Problem Statement

Design the core backend for a ride-sharing app like Uber.

**Functional Requirements:**
- Rider requests a ride (pickup + dropoff location)
- Match rider with nearest available driver
- Driver/rider track each other in real-time during trip
- Calculate fare and process payment
- Trip history

**Non-Functional Requirements:**
- 100M DAU (riders + drivers)
- 10M concurrent trips at peak
- Driver location updates every 4 seconds
- Match rider to driver within 1 second
- 99.99% availability

---

## 2. Capacity Estimation

```
Driver location updates:
  5M active drivers, 1 update per 4 sec = 1.25M location writes/sec
  Each update: driver_id + lat/lng + timestamp = ~50 bytes
  Writes: 1.25M x 50 bytes = 62.5 MB/sec

Ride requests: 10M trips/day / 86400 = ~116 requests/sec (peak: ~1,160)

Location storage (last known only, not history):
  5M drivers x 50 bytes = 250 MB in Redis -- tiny, fits easily

Trip history storage:
  10M trips/day x 1 KB per trip = 10 GB/day (trivial)
```

---

## 3. High-Level Design

```
[Rider App]                        [Driver App]
    |                                    |
    | REST: request ride                 | WebSocket: location stream
    |                                    |
[API Gateway]                    [Location Service]
    |                                    |
[Ride Matching Service] <-------[Driver Location Store (Redis + Geo)]
    |
    +---> [Trip Service] -> [PostgreSQL: trips]
    |
    +---> [Notification Service] -> [Driver: FCM push / WebSocket]
    |
[ETA Service] (route calculation: Google Maps API or in-house)
[Pricing Service] (surge pricing, fare calculation)
[Payment Service] -> [Stripe / Braintree]
```

---

## 4. Driver Location Tracking (Critical Path)

```php
// Driver sends location every 4 seconds via WebSocket
// Location Service receives and stores in Redis GEO

// Store driver location (geospatial index):
$redis->geoAdd(
    'drivers:available',      // key (only available drivers)
    $longitude,               // e.g., -73.9857
    $latitude,                // e.g., 40.7484
    "driver:{$driverId}"      // member
);

// Set TTL: if driver stops sending, remove after 10s (2.5 missed updates)
$redis->set("driver:active:{$driverId}", 1, ['EX' => 10]);

// On driver status change (going offline or on trip):
$redis->geoRem('drivers:available', "driver:{$driverId}");
$redis->geoAdd('drivers:on_trip', $longitude, $latitude, "driver:{$driverId}");

// Find nearest N available drivers within radius:
$nearbyDrivers = $redis->geoRadius(
    'drivers:available',
    $riderLongitude,
    $riderLatitude,
    5,        // 5 km radius
    'km',
    ['COUNT' => 10, 'ASC', 'WITHCOORD', 'WITHDIST']
);
// Returns: [["driver:123", 0.4, [-73.98, 40.75]], ...]
```

---

## 5. Ride Matching Algorithm

```
MATCHING FLOW:
1. Rider requests ride at (lat, lng)
2. Query Redis GEO: find top 10 available drivers within 5km radius, sorted by distance
3. For each driver (ordered by proximity):
   a. Calculate actual ETA (not just distance) via routing service
   b. Send ride request to driver (push notification + WebSocket)
   c. Driver has 15 seconds to accept
   d. If accepted: create trip, notify rider with driver details + ETA
   e. If timeout: try next driver
4. If no driver found within 5km: expand radius to 10km, retry

TRIP STATE MACHINE:
  REQUESTED -> DRIVER_ASSIGNED -> DRIVER_EN_ROUTE ->
  DRIVER_ARRIVED -> IN_PROGRESS -> COMPLETED | CANCELLED

ETA Calculation:
  Use external Maps API (Google Maps, Mapbox) or in-house graph routing
  Factors: distance, traffic, road type, time of day
  Update ETA every 30s during trip using driver's current location
```

---

## 6. Real-Time Tracking During Trip

```
During trip: both rider and driver need each other's live location

Approach: WebSocket rooms / channels
  - On trip start: both apps subscribe to "trip:{trip_id}" channel
  - Driver app publishes location every 4s -> Server broadcasts to rider
  - Server: Redis Pub/Sub or dedicated channel service (Pusher/Ably)

Driver -> Location Service -> Redis PUBLISH "trip:{trip_id}" {lat, lng}
Rider  <- Connection Server <- Redis SUBSCRIBE "trip:{trip_id}"

Scale consideration:
  10M concurrent trips x 1 update/4s = 2.5M publishes/sec
  Redis Pub/Sub max: ~100K msg/sec per instance
  -> Need Redis Cluster or dedicated realtime service (Kafka Streams)
  -> Or: Dedicated WebSocket servers, driver location fan-out service
```

---

## 7. Surge Pricing

```php
// Dynamic pricing based on supply/demand ratio in a geographic area

// Geohash an area into cells (geohash precision 5 = ~5km x 5km)
function getGeohashCell(float $lat, float $lng, int $precision = 5): string {
    return Geohash::encode($lat, $lng, $precision);
}

// Every 30 seconds, compute surge for each active geohash cell:
$availableDrivers = $redis->geoRadius('drivers:available', $cellCenterLng, $cellCenterLat, 5, 'km', ['COUNT' => 1000]);
$pendingRiders    = $redis->zCount("pending_riders:{$geohashCell}", '-inf', '+inf');

$supplyDemandRatio = count($availableDrivers) / max(1, $pendingRiders);
$surgeMultiplier   = match(true) {
    $supplyDemandRatio >= 1.5 => 1.0,    // plenty of drivers
    $supplyDemandRatio >= 1.0 => 1.5,    // slight shortage
    $supplyDemandRatio >= 0.5 => 2.0,    // moderate surge
    default                   => 3.0,    // severe shortage
};

$redis->setEx("surge:{$geohashCell}", 30, $surgeMultiplier);
```

---

## 8. Database Schema

```sql
-- Trips (PostgreSQL)
CREATE TABLE trips (
    id              BIGINT       PRIMARY KEY,
    rider_id        BIGINT       NOT NULL,
    driver_id       BIGINT,
    status          VARCHAR(30)  NOT NULL DEFAULT 'REQUESTED',
    pickup_lat      DECIMAL(10,7),
    pickup_lng      DECIMAL(10,7),
    dropoff_lat     DECIMAL(10,7),
    dropoff_lng     DECIMAL(10,7),
    fare_amount     DECIMAL(10,2),
    surge_multiplier DECIMAL(4,2) DEFAULT 1.0,
    requested_at    TIMESTAMP    DEFAULT NOW(),
    completed_at    TIMESTAMP
);

CREATE INDEX idx_trips_rider  ON trips(rider_id, requested_at DESC);
CREATE INDEX idx_trips_driver ON trips(driver_id, requested_at DESC);
CREATE INDEX idx_trips_status ON trips(status) WHERE status NOT IN ('COMPLETED','CANCELLED');
```

---

## 9. Trade-offs

| Decision | Choice | Reason |
|----------|--------|--------|
| Location storage | Redis GEO (in-memory) | Sub-ms geospatial queries at 1.25M writes/sec |
| Location updates | WebSocket (not HTTP polling) | Persistent, low overhead, real-time |
| Matching radius | Expand dynamically 5->10->20km | Balance speed vs quality |
| Real-time tracking | Redis Pub/Sub -> rider | Simple fan-out per trip channel |
| Surge pricing | Geohash cells, 30s refresh | Coarse granularity, real-time enough |

---

## 10. Interview Q&A

**Q: How do you handle a driver who lost connectivity for 30 seconds?**
> The location service monitors driver heartbeats. If no update received for 10s (2.5 missed 4s updates), the driver key expires in Redis and they're removed from the 'drivers:available' GEO set. If they're on a trip: the trip continues -- we use the last known location for display and show "GPS signal lost" to the rider. On reconnect, the driver app sends its latest location and reconnects to the trip WebSocket channel. The trip state (COMPLETED, etc.) is the source of truth in PostgreSQL, not the WebSocket connection.

**Q: How do you prevent two riders from matching to the same driver?**
> Use an optimistic locking / atomic reservation: when the matching service selects driver D for rider R, it runs: SET driver:reserved:{driverId} {tripId} NX EX 20 (Redis atomic set-if-not-exists). If another request concurrently picked the same driver, one of them will fail (NX = only set if not exists). The losing request immediately tries the next best driver. This is more efficient than database-level locks and works across distributed matching servers.

---

## 11. Key Takeaways

```
+--------------------------------------------------------------------+
| Redis GEO = geospatial queries in O(N+log M) at 1.25M writes/sec |
| Driver location TTL: auto-remove offline drivers (no manual GC)  |
| Matching: nearest 10, send requests serially with 15s timeout    |
| Surge: geohash cells + supply/demand ratio, refresh every 30s    |
| Redis SET NX = atomic driver reservation, prevents double-booking |
+--------------------------------------------------------------------+
```
