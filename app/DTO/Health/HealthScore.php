<?php

namespace App\DTO\Health;

class HealthScore
{
    public int $score;
    public string $grade;
    public string $generated_at;
    public array $dimensions;

    public function __construct(int $score, array $dimensions)
    {
        $this->score = $score;
        $this->grade = $this->calculateGrade($score);
        $this->generated_at = now()->toIso8601String();
        $this->dimensions = $dimensions;
    }

    private function calculateGrade(int $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'grade' => $this->grade,
            'generated_at' => $this->generated_at,
            'dimensions' => $this->dimensions,
        ];
    }
}
