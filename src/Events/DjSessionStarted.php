<?php

namespace JordanPartridge\ConduitDj\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DjSessionStarted
{
    use Dispatchable;

    public string $sessionId;

    public string $mode;

    public array $config;

    public int $startedAt;

    public function __construct(string $sessionId, string $mode, array $config)
    {
        $this->sessionId = $sessionId;
        $this->mode = $mode;
        $this->config = $config;
        $this->startedAt = time();
    }
}