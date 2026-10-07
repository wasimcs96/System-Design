<?php
interface IVideo{
    public function getVideoData():array;
}
interface ISubscriber{
    public function subscribeChannel(IChannel $channel):void;
    public function unSubscribeChannel(IChannel $channel):void;
    public function Notify(IChannel $channel, IVideo $video):void;
}
interface IChannel{
    public function subscribeUser(ISubscriber $user):void;
    public function unSubscribeUser(ISubscriber $user):void;
    public function uploadVideo(IVideo $video):void;
    public function notifySubscribers(IVideo $video):void;
}

class VideoData implements IVideo{
    public string $title;
    public string $description;
    public string $videoLink;

    public function __construct(string $title, string $description, string $videoLink)
    {
        $this->title = $title;
        $this->description = $description;
        $this->videoLink = $videoLink;
    }

    public function getVideoData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'videoLink' => $this->videoLink
        ];
    }
}

class User implements ISubscriber{
    public string $name;
    public array $subscribedChannels = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function subscribeChannel(IChannel $channel):void{
        $channelName = $channel->name;
        $this->subscribedChannels[$channelName] = $channel;
        $channel->subscribeUser($this);
    }
    public function unSubscribeChannel(IChannel $channel):void{
        $channelName = $channel->name;
        unset($this->subscribedChannels[$channelName]);
        $channel->unSubscribeUser($this);
    }
    public function Notify(IChannel $channel, IVideo $video):void{
        echo 'Send notification to ' . $this->name . PHP_EOL;
        echo 'New video uploaded on channel: ' . $channel->name . PHP_EOL;
        $videoData = $video->getVideoData();
        echo 'Video Data: ' . json_encode($videoData) . PHP_EOL;
    }
}

class Channel implements IChannel{
    public string $name;
    public array $subscribers = [];
    public array $uploadedVideos = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function subscribeUser(ISubscriber $user):void{
        $this->subscribers[$user->name] = $user;
    }
    public function unSubscribeUser(ISubscriber $user):void{
        unset($this->subscribers[$user->name]);
    }
    public function uploadVideo(IVideo $video):void{
        $this->uploadedVideos[$video->title] = $video;
        $this->notifySubscribers($video);
    }
    public function notifySubscribers(IVideo $video):void{
        if(count($this->subscribers) > 0){
            foreach($this->subscribers as $subscriber){
                $subscriber->Notify($this, $video);
            }
        }
    }
}

Class Client{
    public function run():void{
        $channel1 = new Channel("Channel 1");
        $channel2 = new Channel("Channel 2");

        $user1 = new User("User 1");
        $user2 = new User("User 2");

        $user1->subscribeChannel($channel1);
        $user2->subscribeChannel($channel1);
        $user2->subscribeChannel($channel2);

        $video1 = new VideoData("Video 1", "Description of Video 1", "http://example.com/video1");
        $video2 = new VideoData("Video 2", "Description of Video 2", "http://example.com/video2");

        $channel1->uploadVideo($video1);

        echo PHP_EOL;

        $channel2->uploadVideo($video2);
    }
}

$client1 = new Client();
$client1->run();