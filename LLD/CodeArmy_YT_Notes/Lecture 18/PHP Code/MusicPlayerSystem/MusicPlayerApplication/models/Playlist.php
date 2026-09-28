<?php

namespace MusicPlayerApplication\models;

require_once __DIR__ . '/Song.php';

use RuntimeException;

class Playlist
{
    private string $playlistName;
    /** @var Song[] */
    private array $songList;

    public function __construct(string $name)
    {
        $this->playlistName = $name;
        $this->songList = [];
    }

    public function getPlaylistName(): string
    {
        return $this->playlistName;
    }

    /** @return Song[] */
    public function getSongs(): array
    {
        return $this->songList;
    }

    public function getSize(): int
    {
        return count($this->songList);
    }

    public function addSongToPlaylist(?Song $song): void
    {
        if ($song === null) {
            throw new RuntimeException("Cannot add null song to playlist.");
        }
        $this->songList[] = $song;
    }
}
