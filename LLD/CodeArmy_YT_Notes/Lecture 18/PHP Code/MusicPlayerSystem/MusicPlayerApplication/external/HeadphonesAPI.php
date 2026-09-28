<?php

namespace MusicPlayerApplication\external;

class HeadphonesAPI
{
    public function playSoundViaJack(string $data): void
    {
        echo "[Headphones] Playing: " . $data . PHP_EOL;
        // mimics playing music
    }
}
