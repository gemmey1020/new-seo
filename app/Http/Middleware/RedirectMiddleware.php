<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Redirect\Redirect;
use App\Models\Site\Site;
use Illuminate\Support\Facades\Cache;

class RedirectMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Process Request chain first
        $response = $next($request);

        // 2. Only intervene on 404
        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        // 3. Check for Redirect
         try {
            // Identify Site context (assuming passed via route middleware or resolved globally)
            // For MVP/Test, we look up site by domain header or assume single site in context?
            // "new-seo" implies multi-tenant or single?
            // Request structure: The middleware runs on the *Client Site*? 
            // Wait, this is a dashboard system "new-seo" that likely manages sites.
            // Does this middleware run on the Dashboard itself?
            // "System is currently v1.5 ... Redirects are Class B Actions"
            // The prompt says "Implement RedirectMiddleware ... Trigger ONLY on 404 responses ... Lookup exact match in redirects table".
            // If this system *is* the site being managed (self-hosted), then yes.
            // If it manages *remote* sites, middleware on *this* app only affects *this* app's 404s.
            // Assuming this middleware is for the application itself or a "Proxy" mode. 
            // Given "CrawlRun" and "Site" models, this is an External Auditor/Manager.
            // *However*, generally SEO tools facilitate redirects on the target site via plugins/snippets.
            // BUT, "Redirect Manager (301/410) ... Middleware ... Implement RedirectMiddleware".
            // If I am implementing it *here*, it implies this application handles traffic for the sites?
            // OR I am building the code to be deployed? 
            // Context: "/opt/lampp/htdocs.../new-seo".
            // Prompt: "Redirects are Class B actions -> HUMAN GATED".
            // Let's implement it for the current app context assuming it might be serving pages or the user wants the logic available.
            
            // Assume Site resolution logic exists e.g. Domain mapping or Env.
            // Fallback: Using host header to find site.
            $host = $request->getHost();
            $path = $request->getRequestUri();
            
            // Try to find site by domain (simple resolution)
            $site = Site::where('domain', 'like', "%$host%")->first();
            
            if ($site) {
                // Normalize Path
                $lookupPath = rtrim(parse_url($path, PHP_URL_PATH), '/');
                if (empty($lookupPath)) $lookupPath = '/';

                $redirect = Redirect::where('site_id', $site->id)
                    ->where('from_url', $lookupPath)
                    ->where('status', 'active')
                    ->first();

                if ($redirect) {
                    if ($redirect->type === '410') {
                        return response('Gone', 410);
                    }
                    return redirect($redirect->to_url, 301);
                }
            }
        } catch (\Exception $e) {
            // Fail Safe: Return original 404 if DB/Logic errors
            return $response;
        }

        return $response;
    }
}
