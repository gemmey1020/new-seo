<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Site\Site;
use App\Models\Site\SiteUser;
use App\Models\Auth\User;
use App\Models\Auth\Role;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Return sites where current user is a member
        return User::find(auth()->id())->sites;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Create Site
        $site = Site::create($request->all());

        // Assign Creator as Admin
        $adminRoleId = Role::where('name', 'admin')->first()?->id ?? 1;
        
        SiteUser::create([
            'site_id' => $site->id,
            'user_id' => auth()->id(),
            'role_id' => $adminRoleId,
            'status' => 'active'
        ]);

        return $site;
    }

    /**
     * Display the specified resource.
     */
    public function show($site)
    {
        return Site::findOrFail($site);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $site)
    {
        $siteModel = Site::findOrFail($site);
        $siteModel->update($request->all());
        return $siteModel;
    }
}
