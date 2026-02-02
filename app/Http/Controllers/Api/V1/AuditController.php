<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Audit\SeoAudit;
use App\Models\Audit\AuditRule;

class AuditController extends Controller
{
    /**
     * List Audits.
     */
    public function index(Request $request, $siteId)
    {
        return SeoAudit::where('site_id', $siteId)->paginate(50);
    }

    /**
     * Run Audits (Trigger).
     */
    public function run(Request $request, $siteId)
    {
        return response()->json(['message' => 'Audit run queued (placeholder)'], 202);
    }

    /**
     * Update Audit (e.g. mark fixed).
     */
    public function update(Request $request, $siteId, $auditId)
    {
        $audit = SeoAudit::where('site_id', $siteId)->findOrFail($auditId);
        
        $data = $request->all();
        
        if (isset($data['status']) && $data['status'] === 'fixed') {
            $data['fixed_at'] = now();
            $data['fixed_by_user_id'] = auth()->id();
        }

        $audit->update($data);
        return $audit;
    }

    /**
     * List Rules (Admin/Global).
     */
    public function rulesIndex(Request $request, $siteId)
    {
        return AuditRule::all();
    }

    /**
     * Update Rule Configuration.
     */
    public function ruleUpdate(Request $request, $siteId, $ruleId)
    {
        $rule = AuditRule::findOrFail($ruleId);
        $rule->update($request->all());
        return $rule;
    }
}
