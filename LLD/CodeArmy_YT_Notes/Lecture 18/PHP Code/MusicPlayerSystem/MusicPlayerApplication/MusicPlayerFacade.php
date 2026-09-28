<?php

namespace MusicPlayerApplication;

require_once __DIR__ . '/core/AudioEngine.php';
require_once __DIR__ . '/models/Playlist.php';
require_once __DIR__ . '/models/Song.php';
require_once __DIR__ . '/strategies/PlayStrategy.php';
require_once __DIR__ . '/enums/DeviceType.php';
require_once __DIR__ . '/enums/PlayStrategyType.php';
require_once __DIR__ . '/managers/DeviceManager.php';
require_once __DIR__ . '/managers/PlaylistManager.php';
require_once __DIR__ . '/managers/StrategyManager.php';
require_once __DIR__ . '/device/IAudioOutputDevice.php';

use MusicPlayerApplication\core\AudioEngine;
use MusicPlayerApplication\enums\DeviceType;
use MusicPlayerApplication\enums\PlayStrategyType;
use MusicPlayerApplication\managers\DeviceManager;
use MusicPlayerApplication\managers\PlaylistManager;
use MusicPlayerApplication\managers\StrategyManager;
use MusicPlayerApplication\models\Playlist;
use MusicPlayerApplication\models\Song;
use MusicPlayerApplication\strategies\PlayStrategy;
use RuntimeException;

class MusicPlayerFacade
{
    private static ?MusicPlayerFacade $instance = null;
    private AudioEngine $audioEngine;
    private ?Playlist $loadedPlaylist;
    private ?PlayStrategy $playStrategy;

    private function __construct()
    {
        $this->loadedPlaylist = null;
        $this->playStrategy = null;
        $this->audioEngine = new AudioEngine();
    }

    public static function getInstance(): MusicPlayerFacade
    {
        if (self::$instance === null) {
            self::$instance = new MusicPlayerFacade();
        }
        return self::$instance;
    }

    public function connectDevice(DeviceType $deviceType): void
    {
        DeviceManager::getInstance()->connect($deviceType);
    }

    public function setPlayStrategy(PlayStrategyType $strategyType): void
    {
        $this->playStrategy = StrategyManager::getInstance()->getStrategy($strategyType);
    }

    public function loadPlaylist(string $name): void
    {
        $this->loadedPlaylist = PlaylistManager::getInstance()->getPlaylist($name);
        if ($this->playStrategy === null) {
            throw new RuntimeException("Play strategy not set before loading.");
        }
        $this->playStrategy->setPlaylist($this->loadedPlaylist);
    }

    public function playSong(Song $song): void
    {
        if (!DeviceManager::getInstance()->hasOutputDevice()) {
            throw new RuntimeException("No audio device connected.");
        }
        $device = DeviceManager::getInstance()->getOutputDevice();
        $this->audioEngine->play($device, $song);
    }

    public function pauseSong(Song $song): void
    {
        if ($this->audioEngine->getCurrentSongTitle() !== $song->getTitle()) {
            throw new RuntimeException("Cannot pause \"" . $song->getTitle() . "\"; not currently playing.");
        }
        $this->audioEngine->pause();
    }

    public function playAllTracks(): void
    {
        if ($this->loadedPlaylist === null) {
            throw new RuntimeException("No playlist loaded.");
        }
        while ($this->playStrategy->hasNext()) {
            $nextSong = $this->playStrategy->next();
            $device = DeviceManager::getInstance()->getOutputDevice();
            $this->audioEngine->play($device, $nextSong);
        }
        echo "Completed playlist: " . $this->loadedPlaylist->getPlaylistName() . PHP_EOL;
    }

    public function playNextTrack(): void
    {
        if ($this->loadedPlaylist === null) {
            throw new RuntimeException("No playlist loaded.");
        }
        if ($this->playStrategy->hasNext()) {
            $nextSong = $this->playStrategy->next();
            $device = DeviceManager::getInstance()->getOutputDevice();
            $this->audioEngine->play($device, $nextSong);
        } else {
            echo "Completed playlist: " . $this->loadedPlaylist->getPlaylistName() . PHP_EOL;
        }
    }

    public function playPreviousTrack(): void
    {
        if ($this->loadedPlaylist === null) {
            throw new RuntimeException("No playlist loaded.");
        }
        if ($this->playStrategy->hasPrevious()) {
            $prevSong = $this->playStrategy->previous();
            $device = DeviceManager::getInstance()->getOutputDevice();
            $this->audioEngine->play($device, $prevSong);
        } else {
            echo "Completed playlist: " . $this->loadedPlaylist->getPlaylistName() . PHP_EOL;
        }
    }

    public function enqueueNext(Song $song): void
    {
        $this->playStrategy->addToNext($song);
    }
}
