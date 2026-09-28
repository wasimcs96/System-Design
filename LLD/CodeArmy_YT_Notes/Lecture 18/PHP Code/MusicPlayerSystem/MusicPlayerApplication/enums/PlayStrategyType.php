<?php

namespace MusicPlayerApplication\enums;

enum PlayStrategyType
{
    case SEQUENTIAL;
    case RANDOM;
    case CUSTOM_QUEUE;
}
