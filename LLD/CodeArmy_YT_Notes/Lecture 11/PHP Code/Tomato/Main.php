<?php

// Entry point. Run with:  php Main.php

require_once __DIR__ . '/TomatoApp.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/strategies/UpiPaymentStrategy.php';

use Models\User;
use Strategies\UpiPaymentStrategy;

class Main
{
    public static function main(): void
    {
        // Simulating a happy flow
        // Create TomatoApp Object
        $tomato = new TomatoApp();

        // Simulate a user coming in (Happy Flow)
        $user = new User(101, "Aditya", "Delhi");
        echo "User: " . $user->getName() . " is active." . PHP_EOL;

        // User searches for restaurants by location
        $restaurantList = $tomato->searchRestaurants("Delhi");

        if (empty($restaurantList)) {
            echo "No restaurants found!" . PHP_EOL;
            return;
        }

        echo "Found Restaurants:" . PHP_EOL;
        foreach ($restaurantList as $restaurant) {
            echo " - " . $restaurant->getName() . PHP_EOL;
        }

        // User selects a restaurant
        $tomato->selectRestaurant($user, $restaurantList[0]);
        echo "Selected restaurant: " . $restaurantList[0]->getName() . PHP_EOL;

        // User adds items to the cart
        $tomato->addToCart($user, "P1");
        $tomato->addToCart($user, "P2");

        $tomato->printUserCart($user);

        // User checkout the cart
        $order = $tomato->checkoutNow($user, "Delivery", new UpiPaymentStrategy("1234567890"));

        // User pays for the cart. If payment is successful, notification is sent.
        $tomato->payForOrder($user, $order);
    }
}

Main::main();
