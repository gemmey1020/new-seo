<?php

namespace App\DTO\Health;

class DriftReport
{
    public string $status;
    public array $indicators;

    public function __construct(array $indicators)
    {
        $this->indicators = $indicators;
        $this->status = $this->deriveStatus($indicators);
    }

    private function deriveStatus(array $indicators): string
    {
        foreach ($indicators as $key => $data) {
            if (($data['severity'] ?? 'SAFE') === 'CRITICAL') {
                return 'CRITICAL';
            }
        }
        foreach ($indicators as $key => $data) {
            if (($data['severity'] ?? 'SAFE') !== 'SAFE') {
                return 'DRIFTING';
            }
        }
        return 'SAFE';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'indicators' => $this->indicators,
        ];
    }
}
