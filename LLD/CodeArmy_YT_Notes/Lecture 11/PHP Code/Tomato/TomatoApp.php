<?php

require_once __DIR__ . '/models/MenuItem.php';
require_once __DIR__ . '/models/Restaurant.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Cart.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/managers/RestaurantManager.php';
require_once __DIR__ . '/managers/OrderManager.php';
require_once __DIR__ . '/strategies/PaymentStrategy.php';
require_once __DIR__ . '/factories/OrderFactory.php';
require_once __DIR__ . '/factories/NowOrderFactory.php';
require_once __DIR__ . '/factories/ScheduledOrderFactory.php';
require_once __DIR__ . '/services/NotificationService.php';

use Factories\NowOrderFactory;
use Factories\OrderFactory;
use Factories\ScheduledOrderFactory;
use Managers\OrderManager;
use Managers\RestaurantManager;
use Models\MenuItem;
use Models\Order;
use Models\Restaurant;
use Models\User;
use Services\NotificationService;
use Strategies\PaymentStrategy;

class TomatoApp
{
    public function __construct()
    {
        $this->initializeRestaurants();
    }

    public function initializeRestaurants(): void
    {
        $restaurant1 = new Restaurant("Bikaner", "Delhi");
        $restaurant1->addMenuItem(new MenuItem("P1", "Chole Bhature", 120));
        $restaurant1->addMenuItem(new MenuItem("P2", "Samosa", 15));

        $restaurant2 = new Restaurant("Haldiram", "Kolkata");
        $restaurant2->addMenuItem(new MenuItem("P1", "Raj Kachori", 80));
        $restaurant2->addMenuItem(new MenuItem("P2", "Pav Bhaji", 100));
        $restaurant2->addMenuItem(new MenuItem("P3", "Dhokla", 50));

        $restaurant3 = new Restaurant("Saravana Bhavan", "Chennai");
        $restaurant3->addMenuItem(new MenuItem("P1", "Masala Dosa", 90));
        $restaurant3->addMenuItem(new MenuItem("P2", "Idli Vada", 60));
        $restaurant3->addMenuItem(new MenuItem("P3", "Filter Coffee", 30));

        $restaurantManager = RestaurantManager::getInstance();
        $restaurantManager->addRestaurant($restaurant1);
        $restaurantManager->addRestaurant($restaurant2);
        $restaurantManager->addRestaurant($restaurant3);
    }

    /** @return Restaurant[] */
    public function searchRestaurants(string $location): array
    {
        return RestaurantManager::getInstance()->searchByLocation($location);
    }

    public function selectRestaurant(User $user, Restaurant $restaurant): void
    {
        $cart = $user->getCart();
        $cart->setRestaurant($restaurant);
    }

    public function addToCart(User $user, string $itemCode): void
    {
        $restaurant = $user->getCart()->getRestaurant();
        if ($restaurant === null) {
            echo "Please select a restaurant first." . PHP_EOL;
            return;
        }
        foreach ($restaurant->getMenu() as $item) {
            if ($item->getCode() === $itemCode) {
                $user->getCart()->addItem($item);
                break;
            }
        }
    }

    public function checkoutNow(User $user, string $orderType, PaymentStrategy $paymentStrategy): ?Order
    {
        return $this->checkout($user, $orderType, $paymentStrategy, new NowOrderFactory());
    }

    public function checkoutScheduled(User $user, string $orderType, PaymentStrategy $paymentStrategy, string $scheduleTime): ?Order
    {
        return $this->checkout($user, $orderType, $paymentStrategy, new ScheduledOrderFactory($scheduleTime));
    }

    public function checkout(User $user, string $orderType, PaymentStrategy $paymentStrategy, OrderFactory $orderFactory): ?Order
    {
        if ($user->getCart()->isEmpty()) return null;

        $userCart = $user->getCart();
        $orderedRestaurant = $userCart->getRestaurant();
        $itemsOrdered = $userCart->getItems();
        $totalCost = $userCart->getTotalCost();

        $order = $orderFactory->createOrder($user, $userCart, $orderedRestaurant, $itemsOrdered, $paymentStrategy, $totalCost, $orderType);
        OrderManager::getInstance()->addOrder($order);
        return $order;
    }

    public function payForOrder(User $user, Order $order): void
    {
        $isPaymentSuccess = $order->processPayment();

        if ($isPaymentSuccess) {
            NotificationService::notify($order);
            $user->getCart()->clear();
        }
    }

    public function printUserCart(User $user): void
    {
        echo "Items in cart:" . PHP_EOL;
        echo "------------------------------------" . PHP_EOL;
        foreach ($user->getCart()->getItems() as $item) {
            echo $item->getCode() . " : " . $item->getName() . " : ₹" . $item->getPrice() . PHP_EOL;
        }
        echo "------------------------------------" . PHP_EOL;
        echo "Grand total : ₹" . $user->getCart()->getTotalCost() . PHP_EOL;
    }
}
