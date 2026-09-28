<?php

namespace MusicPlayerApplication\models;

class Song
{
    private string $title;
    private string $artist;
    private string $filePath;

    public function __construct(string $title, string $artist, string $filePath)
    {
        $this->title = $title;
        $this->artist = $artist;
        $this->filePath = $filePath;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getArtist(): string
    {
        return $this->artist;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
