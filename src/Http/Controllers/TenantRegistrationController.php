<?php

namespace Laravilt\Panel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravilt\Panel\Facades\Laravilt;
use Laravilt\Panel\Facades\Panel;

class TenantRegistrationController extends Controller
{
    /**
     * Show the tenant registration form.
     */
    public function create()
    {
        $panel = Panel::getCurrent();

        return Inertia::render('Tenant/Register', [
            'panel' => [
                'id' => $panel->getId(),
                'path' => $panel->getPath(),
            ],
        ]);
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request)
    {
        $panel = Panel::getCurrent();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
        ]);

        $tenantModel = $panel->getTenantModel();
        $slugAttribute = $panel->getTenantSlugAttribute();
        $user = $request->user();

        // Generate slug if not provided
        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Ensure slug is unique
        $baseSlug = $slug;
        $counter = 1;
        while ($tenantModel::where($slugAttribute, $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // Create the tenant
        $tenant = new $tenantModel;
        $tenant->name = $validated['name'];
        $tenant->{$slugAttribute} = $slug;

        // Set owner if the model has owner_id
        if (property_exists($tenant, 'owner_id') || method_exists($tenant, 'owner')) {
            $tenant->owner_id = $user->id;
        }

        $tenant->save();

        // Attach user to tenant
        $ownershipRelationship = $panel->getTenantOwnershipRelationship();
        $pluralRelationship = Str::plural($ownershipRelationship);

        if (method_exists($user, $pluralRelationship)) {
            $user->{$pluralRelationship}()->attach($tenant->id, ['role' => 'owner']);
        }

        // Set as current team
        if (property_exists($user, 'current_team_id') || isset($user->current_team_id)) {
            $user->current_team_id = $tenant->id;
            $user->save();
        }

        // Set tenant in session
        session()->put('laravilt.tenant_id', $tenant->getKey());
        Laravilt::setTenant($tenant);

        return redirect('/'.$panel->getPath());
    }
}
