<?php

namespace Utils;

class TimeUtils
{
    public static function getCurrentTime(): string
    {
        // Same format as Java's "EEE MMM dd HH:mm:ss yyyy" -> e.g. "Thu Sep 24 18:54:00 2026"
        return date("D M d H:i:s Y");
    }
}
