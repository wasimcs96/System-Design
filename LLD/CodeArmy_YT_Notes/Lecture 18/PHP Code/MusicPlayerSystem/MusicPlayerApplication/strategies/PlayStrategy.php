<?php

namespace MusicPlayerApplication\strategies;

require_once __DIR__ . '/../models/Playlist.php';
require_once __DIR__ . '/../models/Song.php';

use MusicPlayerApplication\models\Playlist;
use MusicPlayerApplication\models\Song;

interface PlayStrategy
{
    public function setPlaylist(Playlist $playlist): void;
    public function next(): Song;
    public function hasNext(): bool;
    public function previous(): Song;
    public function hasPrevious(): bool;
    public function addToNext(Song $song): void;
}

/*
 PHP interfaces cannot contain method bodies, so Java's
     default void addToNext(Song song) {}
 is provided through this trait. Strategies that don't support queuing just "use" it.
*/
trait PlayStrategyDefaults
{
    public function addToNext(Song $song): void
    {
        // default: do nothing
    }
}
