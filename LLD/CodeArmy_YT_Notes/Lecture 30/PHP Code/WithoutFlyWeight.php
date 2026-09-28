<?php

/*
NOTE (PHP): 1,000,000 full objects need far more than PHP's default 128 MB
memory_limit (each PHP object with 10 properties is ~200+ bytes). That memory
blow-up is exactly the problem the Flyweight pattern solves, so we raise the
limit for this demo and also print the REAL memory PHP used at the end.
*/
ini_set('memory_limit', '2048M');

const INTEGER_BYTES = 4; // Java's Integer.BYTES

class Asteroid
{
    // Intrinsic properties (same for many asteroids) - DUPLICATED FOR EACH OBJECT
    private int $length;
    private int $width;
    private int $weight;
    private string $color;
    private string $texture;
    private string $material;

    // Extrinsic properties (unique for each asteroid)
    private int $posX;
    private int $posY;
    private int $velocityX;
    private int $velocityY;

    public function __construct(int $l, int $w, int $wt, string $col, string $tex,
                                string $mat, int $posX, int $posY, int $velX, int $velY)
    {
        $this->length = $l;
        $this->width = $w;
        $this->weight = $wt;
        $this->color = $col;
        $this->texture = $tex;
        $this->material = $mat;
        $this->posX = $posX;
        $this->posY = $posY;
        $this->velocityX = $velX;
        $this->velocityY = $velY;
    }

    public function render(): void
    {
        echo "Rendering " . $this->color . ", " . $this->texture . ", " . $this->material
            . " asteroid at (" . $this->posX . "," . $this->posY
            . ") Size: " . $this->length . "x" . $this->width
            . " Velocity: (" . $this->velocityX . ", "
            . $this->velocityY . ")" . PHP_EOL;
    }

    // Calculate approximate memory usage per object (same formula as the Java lecture)
    public static function getMemoryUsage(): int
    {
        return INTEGER_BYTES * 7 +     // length, width, weight, x, y, velocityX, velocityY
               40 * 3;                 // Approximate string data (assuming average 10 chars each)
    }
}

class SpaceGame
{
    /** @var Asteroid[] */
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

            $this->asteroids[] = new Asteroid(
                $sizes[$type], $sizes[$type], $sizes[$type] * 10,
                $colors[$type], $textures[$type], $materials[$type],
                100 + $i * 50,         // Simple x: 100, 150, 200, 250...
                200 + $i * 30,         // Simple y: 200, 230, 260, 290...
                1,                     // All move right with velocity 1
                2                      // All move down with velocity 2
            );
        }

        echo "Created " . count($this->asteroids) . " asteroid objects" . PHP_EOL;
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
        return count($this->asteroids) * Asteroid::getMemoryUsage();
    }

    public function getAsteroidCount(): int
    {
        return count($this->asteroids);
    }
}

class WithoutFlyWeight
{
    public static function main(): void
    {
        $ASTEROID_COUNT = 1_000_000;

        echo PHP_EOL . " TESTING WITHOUT FLYWEIGHT PATTERN" . PHP_EOL;
        $game = new SpaceGame();

        $game->spawnAsteroids($ASTEROID_COUNT);

        // Show first 5 asteroids to see the pattern
        $game->renderAll();

        // Calculate and display memory usage
        $totalMemory = $game->calculateMemoryUsage();

        echo PHP_EOL . "=== MEMORY USAGE ===" . PHP_EOL;
        echo "Total asteroids: " . $ASTEROID_COUNT . PHP_EOL;
        echo "Memory per asteroid: " . Asteroid::getMemoryUsage() . " bytes" . PHP_EOL;
        echo "Total memory used: " . $totalMemory . " bytes" . PHP_EOL;
        echo "Memory in MB: " . ($totalMemory / (1024.0 * 1024.0)) . " MB" . PHP_EOL;

        // Extra (PHP only): what PHP actually allocated
        echo "Actual PHP peak memory: " . round(memory_get_peak_usage(true) / (1024 * 1024), 2) . " MB" . PHP_EOL;
    }
}

WithoutFlyWeight::main();
