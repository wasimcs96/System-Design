<?php

namespace MusicPlayerApplication\strategies;

require_once __DIR__ . '/PlayStrategy.php';

use MusicPlayerApplication\models\Playlist;
use MusicPlayerApplication\models\Song;
use RuntimeException;

class SequentialPlayStrategy implements PlayStrategy
{
    use PlayStrategyDefaults;

    private ?Playlist $currentPlaylist;
    private int $currentIndex;

    public function __construct()
    {
        $this->currentPlaylist = null;
        $this->currentIndex = -1;
    }

    public function setPlaylist(Playlist $playlist): void
    {
        $this->currentPlaylist = $playlist;
        $this->currentIndex = -1;
    }

    public function hasNext(): bool
    {
        return (($this->currentIndex + 1) < $this->currentPlaylist->getSize());
    }

    // Next in Loop
    public function next(): Song
    {
        if ($this->currentPlaylist === null || $this->currentPlaylist->getSize() === 0) {
            throw new RuntimeException("No playlist loaded or playlist is empty.");
        }
        $this->currentIndex = $this->currentIndex + 1;
        return $this->currentPlaylist->getSongs()[$this->currentIndex];
    }

    public function hasPrevious(): bool
    {
        return ($this->currentIndex - 1 > 0);
    }

    // previous in Loop
    public function previous(): Song
    {
        if ($this->currentPlaylist === null || $this->currentPlaylist->getSize() === 0) {
            throw new RuntimeException("No playlist loaded or playlist is empty.");
        }
        $this->currentIndex = $this->currentIndex - 1;
        return $this->currentPlaylist->getSongs()[$this->currentIndex];
    }
}
