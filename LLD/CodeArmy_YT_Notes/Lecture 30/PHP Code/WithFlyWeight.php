<?php

/*
NOTE (PHP): Even with Flyweight, we still create 1,000,000 small context objects,
which is more than PHP's default 128 MB memory_limit, so we raise it for this demo.
Compare the "Actual PHP peak memory" line with WithoutFlyWeight.php to see the saving.
*/
ini_set('memory_limit', '2048M');

const INTEGER_BYTES = 4; // Java's Integer.BYTES

// Flyweight - Stores INTRINSIC state only
class AsteroidFlyweight
{
    // Intrinsic properties (shared among asteroids of same type)
    private int $length;
    private int $width;
    private int $weight;
    private string $color;
    private string $texture;
    private string $material;

    public function __construct(int $l, int $w, int $wt, string $col, string $tex, string $mat)
    {
        $this->length = $l;
        $this->width = $w;
        $this->weight = $wt;
        $this->color = $col;
        $this->texture = $tex;
        $this->material = $mat;
    }

    public function render(int $posX, int $posY, int $velocityX, int $velocityY): void
    {
        echo "Rendering " . $this->color . ", " . $this->texture . ", " . $this->material
            . " asteroid at (" . $posX . "," . $posY
            . ") Size: " . $this->length . "x" . $this->width
            . " Velocity: (" . $velocityX . ", "
            . $velocityY . ")" . PHP_EOL;
    }

    public static function getMemoryUsage(): int
    {
        return INTEGER_BYTES * 3 +     // length, width, weight
               40 * 3;                 // Approximate string data
    }
}

// Flyweight Factory
class AsteroidFactory
{
    /** @var array<string, AsteroidFlyweight> */
    private static array $flyweights = [];

    public static function getAsteroid(int $length, int $width, int $weight,
                                       string $color, string $texture, string $material): AsteroidFlyweight
    {
        $key = $length . "_" . $width . "_" . $weight . "_" . $color . "_" . $texture . "_" . $material;

        if (!array_key_exists($key, self::$flyweights)) {
            self::$flyweights[$key] = new AsteroidFlyweight($length, $width, $weight, $color, $texture, $material);
        }

        return self::$flyweights[$key];
    }

    public static function getFlyweightCount(): int
    {
        return count(self::$flyweights);
    }

    public static function getTotalFlyweightMemory(): int
    {
        return count(self::$flyweights) * AsteroidFlyweight::getMemoryUsage();
    }

    public static function cleanup(): void
    {
        self::$flyweights = [];
    }
}

// Context - Stores EXTRINSIC state only
class AsteroidContext
{
    private AsteroidFlyweight $flyweight;
    private int $posX;       // position
    private int $posY;
    private int $velocityX;  // velocity
    private int $velocityY;

    public function __construct(AsteroidFlyweight $fw, int $posX, int $posY, int $velX, int $velY)
    {
        $this->flyweight = $fw;
        $this->posX = $posX;
        $this->posY = $posY;
        $this->velocityX = $velX;
        $this->velocityY = $velY;
    }

    public function render(): void
    {
        $this->flyweight->render($this->posX, $this->posY, $this->velocityX, $this->velocityY);
    }

    public static function getMemoryUsage(): int
    {
        return 8 + INTEGER_BYTES * 4; // approximate pointer + ints
    }
}

class SpaceGameWithFlyweight
{
    /** @var AsteroidContext[] */
    private array $asteroids = [];

    public function spawnAsteroids(int $count): void
    {
        echo PHP_EOL . "=== Spawning " . $count . " asteroids ===" . PHP_EOL;

        $colors = ["Red", "Blue", "Gray"];
        $textures = ["Rocky", "Metallic", "Icy"];
        $materials = ["Iron", "Stone", "Ice"];
        $sizes = [25, 35, 45];

        for ($i = 0; $i < $count; $i++) {
            $type = $i % 3;

            $flyweight = AsteroidFactory::getAsteroid(
                $sizes[$type], $sizes[$type], $sizes[$type] * 10,
                $colors[$type], $textures[$type], $materials[$type]
            );

            $this->asteroids[] = new AsteroidContext(
                $flyweight,
                100 + $i * 50, // Simple x: 100, 150, 200, 250...
                200 + $i * 30, // Simple y: 200, 230, 260, 290...
                1, // All move right with velocity 1
                2  // All move down with velocity 2
            );
        }

        echo "Created " . count($this->asteroids) . " asteroid contexts" . PHP_EOL;
        echo "Total flyweight objects: " . AsteroidFactory::getFlyweightCount() . PHP_EOL;
    }

    public function renderAll(): void
    {
        echo PHP_EOL . "--- Rendering first 5 asteroids ---" . PHP_EOL;
        for ($i = 0; $i < min(5, count($this->asteroids)); $i++) {
            $this->asteroids[$i]->render();
        }
    }

    public function calculateMemoryUsage(): int
    {
        $contextMemory = count($this->asteroids) * AsteroidContext::getMemoryUsage();
        $flyweightMemory = AsteroidFactory::getTotalFlyweightMemory();
        return $contextMemory + $flyweightMemory;
    }

    public function getAsteroidCount(): int
    {
        return count($this->asteroids);
    }
}

class WithFlyWeight
{
    public static function main(): void
    {
        $ASTEROID_COUNT = 1_000_000;

        echo PHP_EOL . "TESTING WITH FLYWEIGHT PATTERN" . PHP_EOL;
        $game = new SpaceGameWithFlyweight();

        $game->spawnAsteroids($ASTEROID_COUNT);

        // Show first 5 asteroids to see the pattern
        $game->renderAll();

        // Calculate and display memory usage
        $totalMemory = $game->calculateMemoryUsage();

        echo PHP_EOL . "=== MEMORY USAGE ===" . PHP_EOL;
        echo "Total asteroids: " . $ASTEROID_COUNT . PHP_EOL;
        echo "Memory per asteroid: " . AsteroidContext::getMemoryUsage() . " bytes" . PHP_EOL;
        echo "Total memory used: " . $totalMemory . " bytes" . PHP_EOL;
        echo "Memory in MB: " . ($totalMemory / (1024.0 * 1024.0)) . " MB" . PHP_EOL;

        // Extra (PHP only): what PHP actually allocated
        echo "Actual PHP peak memory: " . round(memory_get_peak_usage(true) / (1024 * 1024), 2) . " MB" . PHP_EOL;
    }
}

WithFlyWeight::main();
