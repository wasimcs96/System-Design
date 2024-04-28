<!-- Single Responsibility Principle (SRP):
A class should have only one reason to change.
Achieve this by ensuring that each class has a clear and well-defined purpose.
Example: -->

<?php
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
?>

