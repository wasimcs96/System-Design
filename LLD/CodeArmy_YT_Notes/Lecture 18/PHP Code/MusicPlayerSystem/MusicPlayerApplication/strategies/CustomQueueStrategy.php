<?php

namespace MusicPlayerApplication\strategies;

require_once __DIR__ . '/PlayStrategy.php';

use MusicPlayerApplication\models\Playlist;
use MusicPlayerApplication\models\Song;
use RuntimeException;

class CustomQueueStrategy implements PlayStrategy
{
    private ?Playlist $currentPlaylist;
    private int $currentIndex;
    /** @var Song[] used as a FIFO queue (append + array_shift) */
    private array $nextQueue;
    /** @var Song[] used as a LIFO stack (append + array_pop) */
    private array $prevStack;

    private function nextSequential(): Song
    {
        if ($this->currentPlaylist->getSize() === 0) {
            throw new RuntimeException("Playlist is empty.");
        }
        $this->currentIndex = $this->currentIndex + 1;
        return $this->currentPlaylist->getSongs()[$this->currentIndex];
    }

    private function previousSequential(): Song
    {
        if ($this->currentPlaylist->getSize() === 0) {
            throw new RuntimeException("Playlist is empty.");
        }
        $this->currentIndex = $this->currentIndex - 1;
        return $this->currentPlaylist->getSongs()[$this->currentIndex];
    }

    public function __construct()
    {
        $this->currentPlaylist = null;
        $this->currentIndex = -1;
        $this->nextQueue = [];
        $this->prevStack = [];
    }

    public function setPlaylist(Playlist $playlist): void
    {
        $this->currentPlaylist = $playlist;
        $this->currentIndex = -1;
        $this->nextQueue = [];
        $this->prevStack = [];
    }

    public function hasNext(): bool
    {
        return (($this->currentIndex + 1) < $this->currentPlaylist->getSize());
    }

    public function next(): Song
    {
        if ($this->currentPlaylist === null || $this->currentPlaylist->getSize() === 0) {
            throw new RuntimeException("No playlist loaded or playlist is empty.");
        }

        if (!empty($this->nextQueue)) {
            $s = array_shift($this->nextQueue); // poll
            $this->prevStack[] = $s;            // push

            // update index to match queued song
            $songs = $this->currentPlaylist->getSongs();
            for ($i = 0; $i < count($songs); ++$i) {
                if ($songs[$i] === $s) {
                    $this->currentIndex = $i;
                    break;
                }
            }
            return $s;
        }

        // Otherwise sequential
        return $this->nextSequential();
    }

    public function hasPrevious(): bool
    {
        return ($this->currentIndex - 1 > 0);
    }

    public function previous(): Song
    {
        if ($this->currentPlaylist === null || $this->currentPlaylist->getSize() === 0) {
            throw new RuntimeException("No playlist loaded or playlist is empty.");
        }

        if (!empty($this->prevStack)) {
            $s = array_pop($this->prevStack);

            // update index to match stacked song
            $songs = $this->currentPlaylist->getSongs();
            for ($i = 0; $i < count($songs); ++$i) {
                if ($songs[$i] === $s) {
                    $this->currentIndex = $i;
                    break;
                }
            }
            return $s;
        }

        // Otherwise sequential
        return $this->previousSequential();
    }

    public function addToNext(?Song $song): void
    {
        if ($song === null) {
            throw new RuntimeException("Cannot enqueue null song.");
        }
        $this->nextQueue[] = $song;
    }
}
