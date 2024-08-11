<?php
//Facade is a structural design pattern that provides a simplified (but limited) interface to a complex system of classes, library or framework.
//While Facade decreases the overall complexity of the application, it also helps to move unwanted dependencies to one place.


class Subsystem1{
    public function opration1(){
        echo "Subsystem1: Opration1\n";
    }

    public function opration2(){
        echo "Subsystem1: Opration2\n";
    }
}

class Subsystem2{
    public function opration3(){
        echo "Subsystem2: Opration3\n";
    }

    public function opration4(){
        echo "Subsystem2: Opration4\n";
    }
}


class Facade{
    public $subsystem1;
    public $subsystem2;
    public function __construct(Subsystem1 $subsystem1 = null, Subsystem2 $subsystem2 = null){
        $this->subsystem1 = $subsystem1 ?: new Subsystem1();
        $this->subsystem2 = $subsystem2 ?: new Subsystem2();
    }

    public function performAllOpration(){
        $this->subsystem1->opration1();
        $this->subsystem1->opration2();
        $this->subsystem2->opration3();
        $this->subsystem2->opration4();
    }
}

class client{
    public $facadeClass;

    function __construct(Facade $facade){
        $this->facadeClass = $facade;
    }

    public function perform(){
        $this->facadeClass->performAllOpration();
    }

}

$subsystem1 = new Subsystem1();
$subsystem2 = new Subsystem2();

$facade = new Facade($subsystem1, $subsystem2);

$client = new client($facade);
$client->perform();



//Real World Example

class Youtube{
    public function uploadVideo($video){
        echo "Uploading video to youtube\n";
    }
    public function shareVideo($video){
        echo "Sharing video on youtube\n";
    }

}

class FFmpeg{
    public function convertVideo($video){
        echo "Converting video to required format\n";
    }
    public function compressVideo($video){
        echo "Compressing video to reduce size\n";
    }
}

class YoutubeDownloaderFacade{
    private $youtube;
    private $ffmpeg;

    public function __construct(Youtube $youtubeClass, FFmpeg $ffmpegClass){
        $this->youtube = $youtubeClass;
        $this->ffmpeg = $ffmpegClass;
    }

    public function downloadVideo(){
        $this->youtube->uploadVideo("video.mp4");
        $this->ffmpeg->convertVideo("video.mp4");
        $this->ffmpeg->compressVideo("video.mp4");
        $this->youtube->shareVideo("video.mp4");
    }
}

class clint{
    private $downloader;
    function __construct(YoutubeDownloaderFacade $youtubrDownloader){
        $this->downloader = $youtubrDownloader;
    }

    public function downloadVideo(){
        $this->downloader->downloadVideo();
    }
}

$youtube = new YouTube();
$ffmpeg = new FFmpeg();

$youtubrDownloader = new YoutubeDownloaderFacade($youtube, $ffmpeg);
$clint = new clint($youtubrDownloader);
$clint->downloadVideo();








?>