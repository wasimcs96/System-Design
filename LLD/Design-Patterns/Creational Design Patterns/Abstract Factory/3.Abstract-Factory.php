<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #3 — ABSTRACT FACTORY                ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Creational Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★☆                                             ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You need to create FAMILIES of related objects that     │
 * │ MUST be used together, without knowing their concrete classes.   │
 * │                                                                  │
 * │ Example:                                                         │
 * │  - UI toolkit: Light theme {Button, Checkbox, Input}            │
 * │                Dark theme  {Button, Checkbox, Input}            │
 * │    You must never mix Light-Button with Dark-Checkbox!           │
 * │                                                                  │
 * │  - Cross-platform: Windows {Button, Scrollbar}                  │
 * │                    macOS   {Button, Scrollbar}                   │
 * │                                                                  │
 * │ Abstract Factory solves this by having a factory interface       │
 * │ with one creation method per product type in the family.         │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ KEY DIFFERENCE: Factory Method vs Abstract Factory               │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Factory Method: Creates ONE type of product (payment gateway)  │
 * │  Abstract Factory: Creates a FAMILY of related products         │
 * │                    (button + checkbox + textfield — all matching)│
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  AbstractFactory                                                  │
 * │  ├─ createButton(): Button                                       │
 * │  └─ createCheckbox(): Checkbox                                   │
 * │        │                                                          │
 * │        ├── LightThemeFactory → LightButton + LightCheckbox      │
 * │        └── DarkThemeFactory  → DarkButton  + DarkCheckbox       │
 * │                                                                   │
 * │  Client uses AbstractFactory interface → always gets a          │
 * │  matching family, never mixes themes                             │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE ABSTRACT FACTORY                     │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Identify the "product families" (Light/Dark, Win/Mac)   │
 * │ STEP 2: Define interfaces for each product type (Button, Input) │
 * │ STEP 3: Create concrete products per family                      │
 * │         (LightButton, DarkButton, LightCheckbox, DarkCheckbox)  │
 * │ STEP 4: Define the AbstractFactory interface (one method/product)│
 * │ STEP 5: Create ConcreteFactory per family                        │
 * │ STEP 6: Client receives a factory — creates products through it  │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// REAL-WORLD EXAMPLE: Cloud Infrastructure Provisioning
// AWS Factory creates: S3Bucket + EC2Server + RDSDatabase (AWS family)
// Azure Factory creates: BlobStorage + VM + AzureSQL   (Azure family)
// You must NEVER mix AWS S3 with Azure VM in the same deployment.
// ═══════════════════════════════════════════════════════════════

// ── STEP 2: Product Interfaces (one per resource type) ──────────────────────

interface ObjectStorage
{
    public function createBucket(string $name): string;
    public function uploadFile(string $bucket, string $file): string;
    public function getProvider(): string;
}

interface ComputeInstance
{
    public function launch(string $instanceType): string;
    public function terminate(string $instanceId): void;
    public function getProvider(): string;
}

interface ManagedDatabase
{
    public function createDatabase(string $name, string $engine): string;
    public function getProvider(): string;
}

// ── STEP 3: Concrete Products — AWS Family ───────────────────────────────────

class AWSS3 implements ObjectStorage
{
    public function createBucket(string $name): string
    {
        echo "  [AWS S3] Creating bucket: $name\n";
        return "s3://$name";
    }
    public function uploadFile(string $bucket, string $file): string
    {
        return "https://$bucket.s3.amazonaws.com/$file";
    }
    public function getProvider(): string { return 'AWS'; }
}

class AWSEC2 implements ComputeInstance
{
    public function launch(string $instanceType): string
    {
        $id = 'i-' . substr(md5(microtime()), 0, 8);
        echo "  [AWS EC2] Launching $instanceType → $id\n";
        return $id;
    }
    public function terminate(string $instanceId): void
    {
        echo "  [AWS EC2] Terminating $instanceId\n";
    }
    public function getProvider(): string { return 'AWS'; }
}

class AWSRds implements ManagedDatabase
{
    public function createDatabase(string $name, string $engine): string
    {
        echo "  [AWS RDS] Creating $engine database: $name\n";
        return "$name.rds.amazonaws.com";
    }
    public function getProvider(): string { return 'AWS'; }
}

// ── STEP 3: Concrete Products — Azure Family ─────────────────────────────────

class AzureBlobStorage implements ObjectStorage
{
    public function createBucket(string $name): string
    {
        echo "  [Azure Blob] Creating container: $name\n";
        return "https://myaccount.blob.core.windows.net/$name";
    }
    public function uploadFile(string $bucket, string $file): string
    {
        return "https://myaccount.blob.core.windows.net/$bucket/$file";
    }
    public function getProvider(): string { return 'Azure'; }
}

class AzureVM implements ComputeInstance
{
    public function launch(string $instanceType): string
    {
        $id = 'vm-' . substr(md5(microtime()), 0, 6);
        echo "  [Azure VM] Launching $instanceType → $id\n";
        return $id;
    }
    public function terminate(string $instanceId): void
    {
        echo "  [Azure VM] Deallocating $instanceId\n";
    }
    public function getProvider(): string { return 'Azure'; }
}

class AzureSQLDatabase implements ManagedDatabase
{
    public function createDatabase(string $name, string $engine): string
    {
        echo "  [Azure SQL] Creating database: $name\n";
        return "$name.database.windows.net";
    }
    public function getProvider(): string { return 'Azure'; }
}

// ── STEP 4: Abstract Factory interface ──────────────────────────────────────

/**
 * This is the ABSTRACT FACTORY.
 * One method per product type in the family.
 * Client code ONLY uses this interface — never knows if it's AWS or Azure.
 */
interface CloudProviderFactory
{
    public function createStorage(): ObjectStorage;
    public function createCompute(): ComputeInstance;
    public function createDatabase(): ManagedDatabase;
}

// ── STEP 5: Concrete Factories ───────────────────────────────────────────────

class AWSFactory implements CloudProviderFactory
{
    public function createStorage(): ObjectStorage    { return new AWSS3(); }
    public function createCompute(): ComputeInstance   { return new AWSEC2(); }
    public function createDatabase(): ManagedDatabase  { return new AWSRds(); }
}

class AzureFactory implements CloudProviderFactory
{
    public function createStorage(): ObjectStorage    { return new AzureBlobStorage(); }
    public function createCompute(): ComputeInstance   { return new AzureVM(); }
    public function createDatabase(): ManagedDatabase  { return new AzureSQLDatabase(); }
}

// ── STEP 6: Client — only depends on abstract factory/interfaces ─────────────

class InfrastructureProvisioner
{
    private ObjectStorage     $storage;
    private ComputeInstance   $compute;
    private ManagedDatabase   $database;

    /**
     * Client receives factory via constructor injection.
     * It creates ALL resources from the SAME factory → guaranteed consistency.
     * Switching from AWS to Azure = just pass AzureFactory().
     */
    public function __construct(CloudProviderFactory $factory)
    {
        // All from same family → no mixing!
        $this->storage  = $factory->createStorage();
        $this->compute  = $factory->createCompute();
        $this->database = $factory->createDatabase();
    }

    public function deployApplication(string $appName): void
    {
        echo "  Provider: {$this->storage->getProvider()}\n";
        $bucket     = $this->storage->createBucket("$appName-assets");
        $instanceId = $this->compute->launch('t3.medium');
        $dbEndpoint = $this->database->createDatabase($appName, 'mysql');
        echo "  Deployed: bucket=$bucket, instance=$instanceId, db=$dbEndpoint\n";
    }
}

// ─── Factory selector based on config ────────────────────────────────────────

function getCloudFactory(string $provider): CloudProviderFactory
{
    return match (strtolower($provider)) {
        'aws'   => new AWSFactory(),
        'azure' => new AzureFactory(),
        default => throw new \InvalidArgumentException("Unknown cloud provider: $provider"),
    };
}

// ─── DRIVER CODE ─────────────────────────────────────────────────────────────

echo "=== ABSTRACT FACTORY PATTERN DEMO ===\n\n";

$providers = ['aws', 'azure'];
foreach ($providers as $provider) {
    echo "--- Deploying on " . strtoupper($provider) . " ---\n";
    $factory     = getCloudFactory($provider);
    $provisioner = new InfrastructureProvisioner($factory);
    $provisioner->deployApplication('myapp');
    echo "\n";
}

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What problem does Abstract Factory solve?                    │
 * │ A: When you need families of related objects that must be used  │
 * │    together. It ensures consistency — you never mix products     │
 * │    from different families (no AWS S3 + Azure VM).               │
 * │                                                                  │
 * │ Q2: Difference between Abstract Factory and Factory Method?      │
 * │ A: Factory Method: ONE factory method → creates ONE product type │
 * │    Abstract Factory: MULTIPLE factory methods → a FAMILY of     │
 * │    related product types (Storage + Compute + Database together).│
 * │                                                                  │
 * │ Q3: How do you add a new cloud provider (e.g., GCP)?            │
 * │ A: 1. Create GCPStorage, GCPCompute, GCPDatabase classes.       │
 * │    2. Create GCPFactory implementing CloudProviderFactory.       │
 * │    3. Add 'gcp' to the factory selector.                         │
 * │    ZERO changes to existing classes (OCP ✓).                    │
 * │                                                                  │
 * │ Q4: How do you add a new resource type (e.g., MessageQueue)?     │
 * │ A: This is the weakness. You must:                               │
 * │    1. Add createQueue() to CloudProviderFactory interface.       │
 * │    2. Implement it in ALL existing factories (AWS, Azure, GCP). │
 * │    → Breaks OCP for the factory interface itself.               │
 * │                                                                  │
 * │ Q5: Real-world examples?                                         │
 * │ A: - PHP's PDO (MySQLFactory, PgSQLFactory both return          │
 * │      PDOStatement, PDOConnection families)                       │
 * │    - UI toolkits (GTK, Qt — each provides button + dialog)      │
 * │    - Laravel's DatabaseManager (MySQL vs SQLite full families)   │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Unknown provider → throw exception with clear message         │
 * │ ✓ If factory creation is expensive, cache factory instances      │
 * │ ✓ Use with DI container — bind factory to interface at startup   │
 * └─────────────────────────────────────────────────────────────────┘
 */
