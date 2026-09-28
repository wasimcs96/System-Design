<?php

namespace Managers;

require_once __DIR__ . '/../models/Restaurant.php';

use Models\Restaurant;

// Singleton
class RestaurantManager
{
    /** @var Restaurant[] */
    private array $restaurants = [];
    private static ?RestaurantManager $instance = null;

    private function __construct()
    {
        // private constructor
    }

    public static function getInstance(): RestaurantManager
    {
        if (self::$instance === null) {
            self::$instance = new RestaurantManager();
        }
        return self::$instance;
    }

    public function addRestaurant(Restaurant $r): void
    {
        $this->restaurants[] = $r;
    }

    /** @return Restaurant[] */
    public function searchByLocation(string $loc): array
    {
        $result = [];
        $loc = strtolower($loc);
        foreach ($this->restaurants as $r) {
            $rl = strtolower($r->getLocation());
            if ($rl === $loc) {
                $result[] = $r;
            }
        }
        return $result;
    }
}
