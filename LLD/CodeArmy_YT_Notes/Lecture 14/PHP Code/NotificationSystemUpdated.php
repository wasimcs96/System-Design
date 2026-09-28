<?php

// PHP version of the Notification System (updated): observers now attach themselves
// to the NotificationService's observable inside their own constructors.

/*============================
      Notification & Decorators
=============================*/

interface INotification
{
    public function getContent(): string;
}

// Concrete Notification: simple text notification.
class SimpleNotification implements INotification
{
    private string $text;

    public function __construct(string $msg)
    {
        $this->text = $msg;
    }

    public function getContent(): string
    {
        return $this->text;
    }
}

// Abstract Decorator: wraps a Notification object.
abstract class INotificationDecorator implements INotification
{
    protected INotification $notification;

    public function __construct(INotification $n)
    {
        $this->notification = $n;
    }
}

// Decorator to add a timestamp to the content.
class TimestampDecorator extends INotificationDecorator
{
    public function __construct(INotification $n)
    {
        parent::__construct($n);
    }

    public function getContent(): string
    {
        return "[2025-04-13 14:22:00] " . $this->notification->getContent();
    }
}

// Decorator to append a signature to the content.
class SignatureDecorator extends INotificationDecorator
{
    private string $signature;

    public function __construct(INotification $n, string $sig)
    {
        parent::__construct($n);
        $this->signature = $sig;
    }

    public function getContent(): string
    {
        return $this->notification->getContent() . "\n-- " . $this->signature . "\n\n";
    }
}

/*============================
  Observer Pattern Components
=============================*/

// Observer interface: each observer gets an update when a new Notification arrives.
interface IObserver
{
    public function update(): void;
}

interface IObservable
{
    public function addObserver(IObserver $observer): void;
    public function removeObserver(IObserver $observer): void;
    public function notifyObservers(): void;
}

// Concrete Observable
class NotificationObservable implements IObservable
{
    /** @var IObserver[] */
    private array $observers = [];
    private ?INotification $currentNotification = null;

    public function addObserver(IObserver $obs): void
    {
        $this->observers[] = $obs;
    }

    public function removeObserver(IObserver $obs): void
    {
        $index = array_search($obs, $this->observers, true);
        if ($index !== false) {
            unset($this->observers[$index]);
            $this->observers = array_values($this->observers);
        }
    }

    public function notifyObservers(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update();
        }
    }

    public function setNotification(INotification $notification): void
    {
        $this->currentNotification = $notification;
        $this->notifyObservers();
    }

    public function getNotification(): ?INotification
    {
        return $this->currentNotification;
    }

    public function getNotificationContent(): string
    {
        return $this->currentNotification->getContent();
    }
}

/*============================
       NotificationService
=============================*/

// The NotificationService manages notifications. It keeps track of notifications.
// Any client code will interact with this service.

// Singleton class
class NotificationService
{
    private NotificationObservable $observable;
    private static ?NotificationService $instance = null;
    /** @var INotification[] */
    private array $notifications = [];

    private function __construct()
    {
        $this->observable = new NotificationObservable();
    }

    public static function getInstance(): NotificationService
    {
        if (self::$instance === null) {
            self::$instance = new NotificationService();
        }
        return self::$instance;
    }

    // Expose the observable so observers can attach.
    public function getObservable(): NotificationObservable
    {
        return $this->observable;
    }

    // Creates a new Notification and notifies observers.
    public function sendNotification(INotification $notification): void
    {
        $this->notifications[] = $notification;
        $this->observable->setNotification($notification);
    }
}

/*============================
       ConcreteObservers
=============================*/
class Logger implements IObserver
{
    private NotificationObservable $notificationObservable;

    // Java had two constructors: Logger() and Logger(NotificationObservable).
    // PHP has no constructor overloading, so we use one optional parameter.
    public function __construct(?NotificationObservable $observable = null)
    {
        $this->notificationObservable = $observable ?? NotificationService::getInstance()->getObservable();
        $this->notificationObservable->addObserver($this);
    }

    public function update(): void
    {
        echo "Logging New Notification : \n" . $this->notificationObservable->getNotificationContent() . PHP_EOL;
    }
}

/*============================
  Strategy Pattern Components (Concrete Observer 2)
=============================*/

interface INotificationStrategy
{
    public function sendNotification(string $content): void;
}

class EmailStrategy implements INotificationStrategy
{
    private string $emailId;

    public function __construct(string $emailId)
    {
        $this->emailId = $emailId;
    }

    public function sendNotification(string $content): void
    {
        // Simulate the process of sending an email notification,
        // representing the dispatch of messages to users via email.
        echo "Sending email Notification to: " . $this->emailId . "\n" . $content . PHP_EOL;
    }
}

class SMSStrategy implements INotificationStrategy
{
    private string $mobileNumber;

    public function __construct(string $mobileNumber)
    {
        $this->mobileNumber = $mobileNumber;
    }

    public function sendNotification(string $content): void
    {
        // Simulate the process of sending an SMS notification,
        // representing the dispatch of messages to users via SMS.
        echo "Sending SMS Notification to: " . $this->mobileNumber . "\n" . $content . PHP_EOL;
    }
}

class PopUpStrategy implements INotificationStrategy
{
    public function sendNotification(string $content): void
    {
        // Simulate the process of sending popup notification.
        echo "Sending Popup Notification: \n" . $content . PHP_EOL;
    }
}

class NotificationEngine implements IObserver
{
    private NotificationObservable $notificationObservable;
    /** @var INotificationStrategy[] */
    private array $notificationStrategies = [];

    // Java: NotificationEngine() attaches itself to the service's observable;
    //       NotificationEngine(observable) only stores it.
    public function __construct(?NotificationObservable $observable = null)
    {
        if ($observable === null) {
            $this->notificationObservable = NotificationService::getInstance()->getObservable();
            $this->notificationObservable->addObserver($this);
        } else {
            $this->notificationObservable = $observable;
        }
    }

    public function addNotificationStrategy(INotificationStrategy $ns): void
    {
        $this->notificationStrategies[] = $ns;
    }

    // Can have RemoveNotificationStrategy as well.

    public function update(): void
    {
        $notificationContent = $this->notificationObservable->getNotificationContent();
        foreach ($this->notificationStrategies as $strategy) {
            $strategy->sendNotification($notificationContent);
        }
    }
}

class NotificationSystemUpdated
{
    public static function main(): void
    {
        // Create NotificationService.
        $notificationService = NotificationService::getInstance();

        // Create Logger Observer (attaches itself)
        $logger = new Logger();

        // Create NotificationEngine observers (attaches itself).
        $notificationEngine = new NotificationEngine();

        $notificationEngine->addNotificationStrategy(new EmailStrategy("random.person@gmail.com"));
        $notificationEngine->addNotificationStrategy(new SMSStrategy("+91 9876543210"));
        $notificationEngine->addNotificationStrategy(new PopUpStrategy());

        // Create a notification with decorators.
        /** @var INotification $notification */
        $notification = new SimpleNotification("Your order has been shipped!");
        $notification = new TimestampDecorator($notification);
        $notification = new SignatureDecorator($notification, "Customer Care");

        $notificationService->sendNotification($notification);
    }
}

NotificationSystemUpdated::main();
