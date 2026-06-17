<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BundleBuilderController extends Controller
{
    /**
     * View list of bundles
     */
    public function index()
    {
        $user = auth()->user();

        $bundles = Bundle::with('courses')
            ->when(!$user->isAdmin(), function($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })
            ->latest()
            ->get();

        // Instructors can only bundle published courses
        $courses = Course::where('status', 'published')
            ->latest()
            ->get(['id', 'title', 'price', 'level']);

        return Inertia::render('Dashboard/Instructor/BundleIndex', [
            'bundles' => $bundles,
            'courses' => $courses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:draft,published',
            'courses' => 'nullable|array',
            'courses.*' => 'exists:courses,id',
            'description' => 'nullable|string',
        ]);

        $bundle = Bundle::create([
            'instructor_id' => auth()->id(),
            'title' => $request->title,
            'price' => $request->price,
            'status' => $request->status,
            'description' => $request->description,
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('bundles/thumbnails', 'public');
            \App\Services\ImageOptimizer::optimize(
                storage_path('app/public/' . $path),
                $request->file('thumbnail')->getMimeType()
            );
            $bundle->update(['thumbnail' => '/storage/' . $path]);
        }

        if ($request->has('courses')) {
            $bundle->courses()->sync($request->courses);
        }

        return redirect()->route('bundle-builder.edit', $bundle->id)
            ->with('success', 'Bundle created successfully');
    }

    /**
     * Render Bundle Builder page
     */
    public function edit(Bundle $bundle)
    {
        // Authorize
        if (!auth()->user()->isAdmin() && $bundle->instructor_id !== auth()->id()) {
            abort(403);
        }

        $bundle->load('courses');

        $courses = Course::where('status', 'published')
            ->latest()
            ->get(['id', 'title', 'price', 'level']);

        return Inertia::render('Dashboard/Instructor/BundleBuilder', [
            'bundle' => $bundle,
            'courses' => $courses
        ]);
    }

    /**
     * Update bundle details
     */
    public function update(Request $request, Bundle $bundle)
    {
        // Authorize
        if (!auth()->user()->isAdmin() && $bundle->instructor_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:draft,published',
            'courses' => 'required|array|min:1',
            'courses.*' => 'exists:courses,id',
            'description' => 'nullable|string',
        ]);

        $data = $request->only(['title', 'price', 'status', 'description']);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('bundles/thumbnails', 'public');
            \App\Services\ImageOptimizer::optimize(
                storage_path('app/public/' . $path),
                $request->file('thumbnail')->getMimeType()
            );
            $data['thumbnail'] = '/storage/' . $path;
        }

        $bundle->update($data);
        $bundle->courses()->sync($request->courses);

        return redirect()->route('bundle-builder.index')
            ->with('success', 'Bundle updated successfully');
    }

    /**
     * Delete bundle
     */
    public function destroy(Bundle $bundle)
    {
        // Authorize
        if (!auth()->user()->isAdmin() && $bundle->instructor_id !== auth()->id()) {
            abort(403);
        }

        $bundle->delete();

        return redirect()->route('bundle-builder.index')
            ->with('success', 'Bundle deleted successfully');
    }
}
