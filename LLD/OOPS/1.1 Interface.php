<?php
interface NotificationService {
    public function sendNotification(string $message, string $recipient): void;
}

class EmailNotificationService implements NotificationService {
    public function sendNotification(string $message, string $recipient): void{
        echo "Sending email to $recipient: $message" . " using EmailNotificationService\n";
    }
}

class SMSNotificationService implements NotificationService {
    public function sendNotification(string $message, string $recipient): void{
        echo "Sending SMS to $recipient: $message" . " using SMSNotificationService\n";
    }
}

class AlertService{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService){
        $this->notificationService = $notificationService;
    }

    public function sendAlert(string $message, string $recipient): void{
        $this->notificationService->sendNotification($message, $recipient);
    }
}


$emailNotificatioService = new EmailNotificationService();
$smsNotificationService = new SMSNotificationService();

$alertServiceForEmail = new AlertService(new EmailNotificationService());
$alertServiceForSMS = new AlertService(new SMSNotificationService());

$alertServiceForEmail->sendAlert("This is an email alert", "user@example.com");
$alertServiceForSMS->sendAlert("This is an SMS alert", "1234567890");

?>