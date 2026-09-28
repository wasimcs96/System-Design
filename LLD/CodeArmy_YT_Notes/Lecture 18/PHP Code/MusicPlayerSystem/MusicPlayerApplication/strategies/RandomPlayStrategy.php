<?php

namespace MusicPlayerApplication\strategies;

require_once __DIR__ . '/PlayStrategy.php';

use MusicPlayerApplication\models\Playlist;
use MusicPlayerApplication\models\Song;
use RuntimeException;

class RandomPlayStrategy implements PlayStrategy
{
    use PlayStrategyDefaults;

    private ?Playlist $currentPlaylist;
    /** @var Song[] */
    private array $remainingSongs = [];
    /** @var Song[] used as a stack (array_push / array_pop) */
    private array $history = [];

    public function __construct()
    {
        $this->currentPlaylist = null;
    }

    public function setPlaylist(Playlist $playlist): void
    {
        $this->currentPlaylist = $playlist;
        if ($this->currentPlaylist === null || $this->currentPlaylist->getSize() === 0) return;

        $this->remainingSongs = $this->currentPlaylist->getSongs(); // arrays are copied by value in PHP
        $this->history = [];
    }

    public function hasNext(): bool
    {
        return $this->currentPlaylist !== null && !empty($this->remainingSongs);
    }

    // Next in Loop
    public function next(): Song
    {
        if ($this->currentPlaylist === null || $this->currentPlaylist->getSize() === 0) {
            throw new RuntimeException("No playlist loaded or playlist is empty.");
        }
        if (empty($this->remainingSongs)) {
            throw new RuntimeException("No songs left to play");
        }

        $idx = random_int(0, count($this->remainingSongs) - 1);
        $selectedSong = $this->remainingSongs[$idx];

        // Remove the selectedSong from the list. (Swap and pop to remove in O(1))
        $lastIndex = count($this->remainingSongs) - 1;
        $this->remainingSongs[$idx] = $this->remainingSongs[$lastIndex];
        array_pop($this->remainingSongs);

        $this->history[] = $selectedSong; // push
        return $selectedSong;
    }

    public function hasPrevious(): bool
    {
        return count($this->history) > 0;
    }

    public function previous(): Song
    {
        if (empty($this->history)) {
            throw new RuntimeException("No previous song available.");
        }

        $song = array_pop($this->history);
        return $song;
    }
}
