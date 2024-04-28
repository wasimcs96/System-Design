<!-- Single Responsibility Principle (SRP):
A class should have only one reason to change.
Achieve this by ensuring that each class has a clear and well-defined purpose.
Example: -->

<?php
//https://razabangi.medium.com/solid-principles-in-php-2a4f2e632a5a

class User {
    private $name;
    private $email;

    public function __construct($name, $email) {
        $this->name = $name;
        $this->email = $email;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }
}

class UserRepository {
    public function save(User $user) {
        // Save the user to the database
    }
}

class EmailNotifier {
    public function sendEmail(User $user, $message) {
        // Send the email to the user
    }
}

$user = new User("John Doe", "john.doe@example.com");
$repository = new UserRepository();
$repository->save($user);
$notifier = new EmailNotifier();
$notifier->sendEmail($user, "Welcome to our website!");


//Another Example Without SRP
class Product {
    private string $name;
    private string $color;
    private int $price;
    
    public function __construct(
        $name,
        $color,
        $price
    ) {
        $this->name = $name;
        $this->color = $color;
        $this->price = $price;
    }
    
    public function getPrice() {
        return $this->price;
    }
    
    public function getProductDetails(): void {
        printf(
            "Product name: %s,\nColor: %s,\nPrice: %d", 
            $this->name, 
            $this->color,
            $this->price 
        );
    }
}

class Invoice {
    private Product $product;
    private int $quantity;
    
    public function __construct(Product $product, int $quantity) {
        $this->product = $product;
        $this->quantity = $quantity;
    }
    
    public function calculateTotal(): int {
        return $this->product->getPrice() * $this->quantity;
    }
    
    public function invoicePrint(): void {
        echo "Invoice print.";
    }
    
    public function saveToDB(): void {
        echo "Invoice saved in DB.";
    }
}

//Convert Below code into SRP
class Product {
    private string $name;
    private string $color;
    private int $price;
    
    public function __construct(
        $name,
        $color,
        $price
    ) {
        $this->name = $name;
        $this->color = $color;
        $this->price = $price;
    }
    
    public function getPrice() {
        return $this->price;
    }
    
    public function getProductDetails(): void {
        printf(
            "Product name: %s,\nColor: %s,\nPrice: %d", 
            $this->name, 
            $this->color,
            $this->price 
        );
    }
}

class Invoice {
    private Product $product;
    private int $quantity;
    
    public function __construct(Product $product, int $quantity) {
        $this->product = $product;
        $this->quantity = $quantity;
    }
    
    public function calculateTotal(): int {
        return $this->product->getPrice() * $this->quantity;
    }
}

class InvoicePrinter {
    private Invoice $invoice;
    
    public function __construct(Invoice $invoice) {
        $this->invoice = $invoice;
    }
    
    public function print(): void {
        echo "Invoice print";
    }
}

class InvoiceDB {
    private Invoice $invoice;
    
    public function __construct(Invoice $invoice) {
        $this->invoice = $invoice;
    }
    
    public function save(): void {
        echo "Invoice saved in DB.";
    }
}
?>

