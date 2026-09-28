<?php

namespace MusicPlayerApplication\managers;

require_once __DIR__ . '/../enums/PlayStrategyType.php';
require_once __DIR__ . '/../strategies/PlayStrategy.php';
require_once __DIR__ . '/../strategies/SequentialPlayStrategy.php';
require_once __DIR__ . '/../strategies/RandomPlayStrategy.php';
require_once __DIR__ . '/../strategies/CustomQueueStrategy.php';

use MusicPlayerApplication\enums\PlayStrategyType;
use MusicPlayerApplication\strategies\CustomQueueStrategy;
use MusicPlayerApplication\strategies\PlayStrategy;
use MusicPlayerApplication\strategies\RandomPlayStrategy;
use MusicPlayerApplication\strategies\SequentialPlayStrategy;

class StrategyManager
{
    private static ?StrategyManager $instance = null;
    private SequentialPlayStrategy $sequentialStrategy;
    private RandomPlayStrategy $randomStrategy;
    private CustomQueueStrategy $customQueueStrategy;

    private function __construct()
    {
        $this->sequentialStrategy = new SequentialPlayStrategy();
        $this->randomStrategy = new RandomPlayStrategy();
        $this->customQueueStrategy = new CustomQueueStrategy();
    }

    public static function getInstance(): StrategyManager
    {
        if (self::$instance === null) {
            self::$instance = new StrategyManager();
        }
        return self::$instance;
    }

    public function getStrategy(PlayStrategyType $type): PlayStrategy
    {
        if ($type === PlayStrategyType::SEQUENTIAL) {
            return $this->sequentialStrategy;
        } elseif ($type === PlayStrategyType::RANDOM) {
            return $this->randomStrategy;
        } else {
            return $this->customQueueStrategy;
        }
    }
}
