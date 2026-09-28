<?php

namespace MusicPlayerApplication\managers;

require_once __DIR__ . '/../models/Playlist.php';
require_once __DIR__ . '/../models/Song.php';

use MusicPlayerApplication\models\Playlist;
use MusicPlayerApplication\models\Song;
use RuntimeException;

class PlaylistManager
{
    private static ?PlaylistManager $instance = null;
    /** @var array<string, Playlist> */
    private array $playlists;

    private function __construct()
    {
        $this->playlists = [];
    }

    public static function getInstance(): PlaylistManager
    {
        if (self::$instance === null) {
            self::$instance = new PlaylistManager();
        }
        return self::$instance;
    }

    public function createPlaylist(string $name): void
    {
        if (array_key_exists($name, $this->playlists)) {
            throw new RuntimeException("Playlist \"" . $name . "\" already exists.");
        }
        $this->playlists[$name] = new Playlist($name);
    }

    public function addSongToPlaylist(string $playlistName, Song $song): void
    {
        if (!array_key_exists($playlistName, $this->playlists)) {
            throw new RuntimeException("Playlist \"" . $playlistName . "\" not found.");
        }
        $this->playlists[$playlistName]->addSongToPlaylist($song);
    }

    public function getPlaylist(string $name): Playlist
    {
        if (!array_key_exists($name, $this->playlists)) {
            throw new RuntimeException("Playlist \"" . $name . "\" not found.");
        }
        return $this->playlists[$name];
    }
}
