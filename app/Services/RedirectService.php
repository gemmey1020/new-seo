<?php

namespace App\Services;

use App\Models\Site\Site;
use App\Models\Redirect\Redirect;
use App\Models\Auth\User;
use App\Services\AuthorityService;
use App\Enums\ActionClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RedirectService
{
    protected $authority;

    public function __construct(AuthorityService $authority)
    {
        $this->authority = $authority;
    }

    /**
     * Create a new Redirect rule (Human Approval Required - Class B).
     */
    public function createRedirect(Site $site, array $data, User $actor): Redirect
    {
        // 1. Sanitize Data
        $from = $this->normalizePath($data['from_url']);
        $to = $this->normalizePath($data['to_url'] ?? null);
        $type = $data['type'] ?? '301';

        // 2. Validate Safety (Sanctuary & Logic)
        $this->validateSafety($site, $from, $to, $type);

        // 3. Authority Check (Class B - Human Gated)
        // STRICT: We must respect the boolean return. 
        // AuthorityService catches internal exceptions and logs DENIED.
        // If it returns false, we MUST abort.
        $payload = ['from' => $from, 'to' => $to, 'type' => $type];
        
        $allowed = $this->authority->canPerform($site, ActionClass::CLASS_B, 'create_redirect', $payload, $actor);
        
        if (!$allowed) {
            throw new \Exception("Redirect Creation DENIED by Authority.");
        }

        // 4. Persistence (Transaction)
        return DB::transaction(function () use ($site, $from, $to, $type, $actor) {
            
            // Re-check uniqueness inside transaction
            $exists = Redirect::where('site_id', $site->id)->where('from_url', $from)->exists();
            if ($exists) {
                throw new \Exception("Redirect for '$from' already exists.");
            }

            return Redirect::create([
                'site_id' => $site->id,
                'from_url' => $from,
                'to_url' => $to,
                'type' => $type,
                'status' => 'active',
                'created_by' => $actor->id
            ]);
        });
    }

    private function validateSafety(Site $site, string $from, ?string $to, string $type)
    {
        // Sanctuary Rule
        if ($from === '/' || $from === '') {
            throw new \Exception("SANCTUARY VIOLATION: Cannot redirect Homepage (/).");
        }

        if ($from === $to) {
            throw new \Exception("Self-redirect loop detected.");
        }

        // Loop Detection (Simple Check)
        // Check if $to is already redirected to something else? 
        // A -> B (New). Does B -> A exist?
        if ($to && Redirect::where('site_id', $site->id)->where('from_url', $to)->exists()) {
             // Deep loop check can be complex, but let's check immediate circular.
             $chain = Redirect::where('site_id', $site->id)->where('from_url', $to)->first();
             if ($chain && $chain->to_url === $from) {
                 throw new \Exception("Circular redirect loop detected: $to redirects back to $from.");
             }
        }
    }

    private function normalizePath(?string $url): ?string
    {
        if (!$url) return null;
        // Parse path from URL or use raw
        $path = parse_url($url, PHP_URL_PATH) ?? $url;
        // Ensure leading slash
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        return rtrim($path, '/');
    }
}
