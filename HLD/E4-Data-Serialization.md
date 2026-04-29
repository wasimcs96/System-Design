# E4 — Data Serialization

> **Section:** Data Processing | **Level:** Intermediate | **Interview Frequency:** ★★★☆☆

---

## 1. Concept Definition

**Beginner:** Data serialization converts data structures (objects, arrays) into a format for sending over the network or storing to disk. Formats trade off: human-readability, size, speed, and schema evolution (adding new fields without breaking old readers).

**Technical:** Key formats: JSON (text, human-readable, no schema), Protocol Buffers (binary, schema required, compact), Apache Avro (binary, schema in message, best for evolution), MessagePack (binary JSON). Schema registries (Confluent Schema Registry) manage schema versions for Kafka.

---

## 2. Real-World Analogy

**JSON = English:** Everyone understands it, but verbose ("the customer's name is John Smith").
**Protobuf = Morse code:** Compact (field numbers, not names), requires codebook (schema), much smaller and faster.
**Avro = Telegram with codebook attached:** Schema travels with data — receiver can decode without prior setup.

---

## 3. Visual Diagram

```
JSON vs Protobuf wire size:
JSON:
{"user_id":12345,"name":"Alice","email":"alice@example.com","age":30}
= 67 bytes (human-readable, keys + quotes overhead)

Protobuf (same data):
[field1_varint=12345][field2_string=Alice][field3_string=alice@example.com][field4_varint=30]
~= 42 bytes (binary, field numbers not names, no quotes)

For 1 billion Kafka messages/day:
  JSON:  1B x 67 bytes = 67 GB/day
  Proto: 1B x 42 bytes = 42 GB/day
  Savings: 37% less bandwidth + lower CPU (no JSON parsing)

SCHEMA EVOLUTION:
v1 schema: {user_id, name, email}
v2 schema: {user_id, name, email, age}  <- new optional field added

Backward compatible: v2 reader can read v1 data (age defaults to 0)
Forward compatible:  v1 reader can read v2 data (ignores unknown age field)
Full compatible:     both directions work -- gold standard
```

---

## 4. Deep Technical Explanation

### Format Comparison

| Format | Type | Schema | Size | Speed | Schema Evolution |
|--------|------|--------|------|-------|-----------------|
| JSON | Text | None | Large | Slow | Flexible |
| XML | Text | Optional (XSD) | Very large | Slowest | Flexible |
| Protobuf | Binary | Required (.proto) | Smallest | Fastest | Good (field numbers) |
| Avro | Binary | Required (in data) | Small | Fast | Excellent |
| MessagePack | Binary | None | Medium | Fast | Like JSON |
| Thrift | Binary | Required (.thrift) | Small | Fast | Good |

### Protobuf Schema Evolution Rules
1. Never change field numbers — they define the wire encoding
2. New fields must be optional — old code ignores unknown fields
3. Never change field types (int32 -> int64 can break decoders)
4. Can add fields; can deprecate (mark reserved) but never reuse numbers

### Confluent Schema Registry (for Kafka)
- Stores Avro/Protobuf/JSON Schema schemas centrally
- Wire format: [magic byte 0x00][schema_id 4 bytes][serialized payload]
- Consumer: reads schema_id -> fetches schema from registry -> deserializes
- Registry enforces compatibility checks before allowing schema updates

---

## 5. Code Example

```php
// Protobuf usage in PHP
// composer require google/protobuf
// Generate from .proto: protoc --php_out=. user.proto
// Proto: message User { int32 user_id = 1; string name = 2; string email = 3; }

use Google\Protobuf\Internal\Message;

// Serialize
$user = new Proto\User();
$user->setUserId(12345);
$user->setName('Alice');
$user->setEmail('alice@example.com');
$serialized = $user->serializeToString();  // compact binary bytes

// Deserialize
$decoded = new Proto\User();
$decoded->mergeFromString($serialized);
echo $decoded->getName();  // Alice

// JSON comparison
$json = json_encode(['user_id' => 12345, 'name' => 'Alice', 'email' => 'alice@example.com']);
echo strlen($json);        // ~64 bytes
echo strlen($serialized);  // ~30 bytes (protobuf)

// Confluent Schema Registry wire format (Avro message)
class AvroSerializer {
    public function serialize(int $schemaId, array $data): string {
        // Header: [magic_byte=0x00][schema_id 4-byte big-endian]
        $header  = pack('cN', 0, $schemaId);
        $payload = $this->avroEncode($schemaId, $data);  // implementation omitted
        return $header . $payload;
    }

    public function deserialize(string $bytes): array {
        // magic byte at offset 0, schema_id at offset 1-4, payload at offset 5+
        $schemaId = unpack('N', substr($bytes, 1, 4))[1];
        $payload  = substr($bytes, 5);
        return $this->avroDecode($schemaId, $payload);  // implementation omitted
    }
}

// JSON Schema validation (for REST APIs)
$schema = json_decode(file_get_contents('user-schema.json'), true);
$data   = json_decode($requestBody, true);
$validator = new JsonSchema\Validator();
$validator->validate($data, (object)$schema);
if (!$validator->isValid()) {
    throw new ValidationException($validator->getErrors());
}
```

---

## 6. Trade-offs

| Format | Readability | Size | Speed | Schema Enforcement | Use Case |
|--------|------------|------|-------|-------------------|---------|
| JSON | High | Large | Slow | None | REST APIs, configs |
| Protobuf | None | Small | Fastest | Required | Microservice RPC |
| Avro | None | Small | Fast | Required + evolution | Kafka events |
| MessagePack | None | Medium | Fast | None | High-perf JSON replacement |

---

## 7. Interview Q&A

**Q1: When would you choose Avro over Protobuf for Kafka messages?**
> Avro with Confluent Schema Registry is preferred for Kafka because: (1) Schema registry enforces compatibility before any producer can publish breaking changes; (2) Schema evolution is first-class -- add fields without breaking existing consumers; (3) Dynamic schema discovery -- consumer fetches schema at runtime by schema_id. Protobuf is better when: (1) strongly typed generated code stubs are preferred; (2) both producer and consumer codebases are controlled; (3) RPC (gRPC) rather than messaging is the use case.

**Q2: What is the risk of changing a Protobuf field number?**
> Field numbers ARE the wire encoding in Protobuf -- field names are not transmitted. Changing field number 2 from `string name` to `int32 age`: old data (string bytes for field 2) is now decoded as int32 -> data corruption. Rule: field numbers are permanent. To remove a field: mark it `reserved 2;` in the proto file to prevent accidental reuse of that number by future fields.

---

## 8. Key Takeaways

```
+--------------------------------------------------------------------+
| JSON: human-readable, slow, large -- good for APIs/debugging      |
| Protobuf: compact, fast, schema required -- microservice RPC      |
| Avro: schema evolution, Kafka standard with Schema Registry       |
| Backward compat: new reader handles old data                      |
| Forward compat: old reader handles new data (ignores new fields)  |
| Never reuse Protobuf field numbers -- they define wire encoding   |
+--------------------------------------------------------------------+
```
