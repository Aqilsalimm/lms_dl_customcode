<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $query = Organization::query()->withCount('memberships');

        if ($request->filled('search')) {
            $search = $request->search;
            if (strlen($search) < 3) {
                // If search is less than 3 chars, return empty result to prevent full table scan
                $query->whereRaw('1 = 0');
            } else {
                $query->whereFullText(['name', 'code'], $search);
            }
        }

        $organizations = $query->latest()->cursorPaginate(15);

        return Inertia::render('Dashboard/Admin/Organizations/Index', [
            'organizations' => $organizations,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreOrganizationRequest $request)
    {
        Organization::create($request->validated());

        return back()->with('message', 'Organisasi berhasil ditambahkan.');
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $organization->update($request->validated());

        return back()->with('message', 'Organisasi berhasil diperbarui.');
    }

    public function toggleActive(Organization $organization)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $organization->update(['is_active' => !$organization->is_active]);

        return back()->with('message', 'Status aktif organisasi berhasil diubah.');
    }
}
