<?php

interface ISubscriber 
{
    public function update(): void;
}

// Observable interface: a YouTube channel interface
interface IChannel
{
    public function subscribe(ISubscriber $subscriber): void;
    public function unsubscribe(ISubscriber $subscriber): void;
    public function notifySubscribers(): void;
}

// Concrete Subject: a YouTube channel that observers can subscribe to
class Channel implements IChannel
{
    /** @var ISubscriber[] */
    private array $subscribers;
    private string $name;
    private string $latestVideo = "";

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->subscribers = [];
    }

    public function subscribe(ISubscriber $subscriber): void
    {
        if (!in_array($subscriber, $this->subscribers, true)) {
            $this->subscribers[] = $subscriber;
        }
    }

    public function unsubscribe(ISubscriber $subscriber): void
    {
        $index = array_search($subscriber, $this->subscribers, true);
        if ($index !== false) {
            unset($this->subscribers[$index]);
            $this->subscribers = array_values($this->subscribers); // re-index
        }
    }

    public function notifySubscribers(): void
    {
        foreach ($this->subscribers as $sub) {
            $sub->update();
        }
    }

    public function uploadVideo(string $title): void
    {
        $this->latestVideo = $title;
        echo PHP_EOL . "[" . $this->name . " uploaded \"" . $title . "\"]" . PHP_EOL;
        $this->notifySubscribers();
    }

    public function getVideoData(): string
    {
        return "\nCheckout our new Video : " . $this->latestVideo . "\n";
    }
}

// Concrete Observer: represents a subscriber to the channel
class Subscriber implements ISubscriber
{
    private string $name;
    private Channel $channel;

    public function __construct(string $name, Channel $channel)
    {
        $this->name = $name;
        $this->channel = $channel;
    }

    public function update(): void
    {
        echo "Hey " . $this->name . "," . $this->channel->getVideoData() . PHP_EOL;
    }
}

class ObserverDesignPattern
{
    public static function main(): void
    {
        // Create a channel and subscribers
        $channel = new Channel("CoderArmy");

        $subs1 = new Subscriber("Varun", $channel);
        $subs2 = new Subscriber("Tarun", $channel);

        // Varun and Tarun subscribe to CoderArmy
        $channel->subscribe($subs1);
        $channel->subscribe($subs2);

        // Upload a video: both Varun and Tarun are notified
        $channel->uploadVideo("Observer Pattern Tutorial");

        // Varun unsubscribes; Tarun remains subscribed
        $channel->unsubscribe($subs1);

        // Upload another video: only Tarun is notified
        $channel->uploadVideo("Decorator Pattern Tutorial");
    }
}

ObserverDesignPattern::main();
