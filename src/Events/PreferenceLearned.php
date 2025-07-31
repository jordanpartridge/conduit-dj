<?php

namespace JordanPartridge\ConduitDj\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PreferenceLearned
{
    use Dispatchable;

    public string $type;

    public mixed $value;

    public float $confidence;

    public array $basedOn;

    public function __construct(string $type, mixed $value, float $confidence, array $basedOn = [])
    {
        $this->type = $type;
        $this->value = $value;
        $this->confidence = $confidence;
        $this->basedOn = $basedOn;
    }
}