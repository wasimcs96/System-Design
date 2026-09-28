<?php

// Entry point. Run with:  php Main.php   (requires PHP 8.1+ for enums)

namespace MusicPlayerApplication;

require_once __DIR__ . '/MusicPlayerApplication.php';
require_once __DIR__ . '/enums/DeviceType.php';
require_once __DIR__ . '/enums/PlayStrategyType.php';

use Exception;
use MusicPlayerApplication\enums\DeviceType;
use MusicPlayerApplication\enums\PlayStrategyType;

class Main
{
    public static function main(): void
    {
        try {
            $application = MusicPlayerApplication::getInstance();

            // Populate library
            $application->createSongInLibrary("Kesariya", "Arijit Singh", "/music/kesariya.mp3");
            $application->createSongInLibrary("Chaiyya Chaiyya", "Sukhwinder Singh", "/music/chaiyya_chaiyya.mp3");
            $application->createSongInLibrary("Tum Hi Ho", "Arijit Singh", "/music/tum_hi_ho.mp3");
            $application->createSongInLibrary("Jai Ho", "A. R. Rahman", "/music/jai_ho.mp3");
            $application->createSongInLibrary("Zinda", "Siddharth Mahadevan", "/music/zinda.mp3");

            // Create playlist and add songs
            $application->createPlaylist("Bollywood Vibes");
            $application->addSongToPlaylist("Bollywood Vibes", "Kesariya");
            $application->addSongToPlaylist("Bollywood Vibes", "Chaiyya Chaiyya");
            $application->addSongToPlaylist("Bollywood Vibes", "Tum Hi Ho");
            $application->addSongToPlaylist("Bollywood Vibes", "Jai Ho");

            // Connect device
            $application->connectAudioDevice(DeviceType::BLUETOOTH);

            // Play/pause a single song
            $application->playSingleSong("Zinda");
            $application->pauseCurrentSong("Zinda");
            $application->playSingleSong("Zinda");  // resume

            echo PHP_EOL . "-- Sequential Playback --" . PHP_EOL . PHP_EOL;
            $application->selectPlayStrategy(PlayStrategyType::SEQUENTIAL);
            $application->loadPlaylist("Bollywood Vibes");
            $application->playAllTracksInPlaylist();

            echo PHP_EOL . "-- Random Playback --" . PHP_EOL . PHP_EOL;
            $application->selectPlayStrategy(PlayStrategyType::RANDOM);
            $application->loadPlaylist("Bollywood Vibes");
            $application->playAllTracksInPlaylist();

            echo PHP_EOL . "-- Custom Queue Playback --" . PHP_EOL . PHP_EOL;
            $application->selectPlayStrategy(PlayStrategyType::CUSTOM_QUEUE);
            $application->loadPlaylist("Bollywood Vibes");
            $application->queueSongNext("Kesariya");
            $application->queueSongNext("Tum Hi Ho");
            $application->playAllTracksInPlaylist();

            echo PHP_EOL . "-- Play Previous in Sequential --" . PHP_EOL . PHP_EOL;
            $application->selectPlayStrategy(PlayStrategyType::SEQUENTIAL);
            $application->loadPlaylist("Bollywood Vibes");
            $application->playAllTracksInPlaylist();

            $application->playPreviousTrackInPlaylist();
            $application->playPreviousTrackInPlaylist();

        } catch (Exception $error) {
            file_put_contents('php://stderr', "Error: " . $error->getMessage() . PHP_EOL);
        }
    }
}

Main::main();
