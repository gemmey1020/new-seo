<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audit\AuditRule;

class PhaseBAuditSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'key' => 'param_url_detected',
                'title' => 'Parameter URL Detected', // name -> title
                'severity_default' => 'warning',
                'category' => 'technical',
                'is_active' => true,
            ],
            [
                'key' => 'pagination_detected',
                'title' => 'Pagination Links Detected',
                'severity_default' => 'info',
                'category' => 'structure',
                'is_active' => true,
            ],
            [
                'key' => 'duplicate_slug_detected',
                'title' => 'Duplicate Slug / Path',
                'severity_default' => 'warning',
                'category' => 'technical',
                'is_active' => true,
            ],
            [
                'key' => 'robots_meta_detected',
                'title' => 'Robots Meta Detected',
                'severity_default' => 'info',
                'category' => 'technical',
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            AuditRule::updateOrCreate(['key' => $rule['key']], $rule);
        }
    }
}
