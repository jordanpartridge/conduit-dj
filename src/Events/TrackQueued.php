<?php

namespace JordanPartridge\ConduitDj\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TrackQueued
{
    use Dispatchable;

    public array $track;

    public int $position;

    public float $compatibilityScore;

    public string $reason;

    public function __construct(array $track, int $position, float $compatibilityScore, string $reason = '')
    {
        $this->track = $track;
        $this->position = $position;
        $this->compatibilityScore = $compatibilityScore;
        $this->reason = $reason;
    }
}