<?php

namespace MusicPlayerApplication;

require_once __DIR__ . '/models/Song.php';
require_once __DIR__ . '/managers/PlaylistManager.php';
require_once __DIR__ . '/enums/DeviceType.php';
require_once __DIR__ . '/enums/PlayStrategyType.php';
require_once __DIR__ . '/MusicPlayerFacade.php';

use MusicPlayerApplication\enums\DeviceType;
use MusicPlayerApplication\enums\PlayStrategyType;
use MusicPlayerApplication\managers\PlaylistManager;
use MusicPlayerApplication\models\Song;
use RuntimeException;

class MusicPlayerApplication
{
    private static ?MusicPlayerApplication $instance = null;
    /** @var Song[] */
    private array $songLibrary;

    private function __construct()
    {
        $this->songLibrary = [];
    }

    public static function getInstance(): MusicPlayerApplication
    {
        if (self::$instance === null) {
            self::$instance = new MusicPlayerApplication();
        }
        return self::$instance;
    }

    public function createSongInLibrary(string $title, string $artist, string $path): void
    {
        $newSong = new Song($title, $artist, $path);
        $this->songLibrary[] = $newSong;
    }

    public function findSongByTitle(string $title): ?Song
    {
        foreach ($this->songLibrary as $s) {
            if ($s->getTitle() === $title) {
                return $s;
            }
        }
        return null;
    }

    public function createPlaylist(string $playlistName): void
    {
        PlaylistManager::getInstance()->createPlaylist($playlistName);
    }

    public function addSongToPlaylist(string $playlistName, string $songTitle): void
    {
        $song = $this->findSongByTitle($songTitle);
        if ($song === null) {
            throw new RuntimeException("Song \"" . $songTitle . "\" not found in library.");
        }
        PlaylistManager::getInstance()->addSongToPlaylist($playlistName, $song);
    }

    public function connectAudioDevice(DeviceType $deviceType): void
    {
        MusicPlayerFacade::getInstance()->connectDevice($deviceType);
    }

    public function selectPlayStrategy(PlayStrategyType $strategyType): void
    {
        MusicPlayerFacade::getInstance()->setPlayStrategy($strategyType);
    }

    public function loadPlaylist(string $playlistName): void
    {
        MusicPlayerFacade::getInstance()->loadPlaylist($playlistName);
    }

    public function playSingleSong(string $songTitle): void
    {
        $song = $this->findSongByTitle($songTitle);
        if ($song === null) {
            throw new RuntimeException("Song \"" . $songTitle . "\" not found.");
        }
        MusicPlayerFacade::getInstance()->playSong($song);
    }

    public function pauseCurrentSong(string $songTitle): void
    {
        $song = $this->findSongByTitle($songTitle);
        if ($song === null) {
            throw new RuntimeException("Song \"" . $songTitle . "\" not found.");
        }
        MusicPlayerFacade::getInstance()->pauseSong($song);
    }

    public function playAllTracksInPlaylist(): void
    {
        MusicPlayerFacade::getInstance()->playAllTracks();
    }

    public function playPreviousTrackInPlaylist(): void
    {
        MusicPlayerFacade::getInstance()->playPreviousTrack();
    }

    public function queueSongNext(string $songTitle): void
    {
        $song = $this->findSongByTitle($songTitle);
        if ($song === null) {
            throw new RuntimeException("Song \"" . $songTitle . "\" not found.");
        }
        MusicPlayerFacade::getInstance()->enqueueNext($song);
    }
}
