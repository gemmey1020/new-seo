<?php

namespace App\DTO\Health;

class ReadinessVerdict
{
    public bool $ready;
    public array $blockers;
    public string $message;

    public function __construct(bool $ready, array $blockers, string $message)
    {
        $this->ready = $ready;
        $this->blockers = $blockers;
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'ready' => $this->ready,
            'blockers' => $this->blockers,
            'message' => $this->message,
        ];
    }
}
