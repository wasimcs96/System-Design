<?php

namespace MusicPlayerApplication\core;

require_once __DIR__ . '/../models/Song.php';
require_once __DIR__ . '/../device/IAudioOutputDevice.php';

use MusicPlayerApplication\device\IAudioOutputDevice;
use MusicPlayerApplication\models\Song;
use RuntimeException;

class AudioEngine
{
    private ?Song $currentSong;
    private bool $songIsPaused;

    public function __construct()
    {
        $this->currentSong = null;
        $this->songIsPaused = false;
    }

    public function getCurrentSongTitle(): string
    {
        if ($this->currentSong !== null) {
            return $this->currentSong->getTitle();
        }
        return "";
    }

    public function isPaused(): bool
    {
        return $this->songIsPaused;
    }

    public function play(IAudioOutputDevice $aod, ?Song $song): void
    {
        if ($song === null) {
            throw new RuntimeException("Cannot play a null song.");
        }
        // Resume if same song was paused
        if ($this->songIsPaused && $song === $this->currentSong) {
            $this->songIsPaused = false;
            echo "Resuming song: " . $song->getTitle() . PHP_EOL;
            $aod->playAudio($song);
            return;
        }

        $this->currentSong = $song;
        $this->songIsPaused = false;
        echo "Playing song: " . $song->getTitle() . PHP_EOL;
        $aod->playAudio($song);
    }

    public function pause(): void
    {
        if ($this->currentSong === null) {
            throw new RuntimeException("No song is currently playing to pause.");
        }
        if ($this->songIsPaused) {
            throw new RuntimeException("Song is already paused.");
        }
        $this->songIsPaused = true;
        echo "Pausing song: " . $this->currentSong->getTitle() . PHP_EOL;
    }
}
