<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogCategoryRequest;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Blog sections.
 *
 * One screen rather than the usual index/create/edit trio: the list is short,
 * a section is five fields, and editing in place beats three page loads.
 */
class BlogCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.blog.sections', [
            'sections' => BlogCategory::orderBy('sort_order')
                ->withCount('posts')
                ->get(),
        ]);
    }

    public function store(BlogCategoryRequest $request): RedirectResponse
    {
        $section = new BlogCategory($request->safe()->except('is_active'));
        $section->is_active = $request->boolean('is_active');
        $section->save();

        return back()->with('status', __('admin.blog.flash.sectionCreated'));
    }

    public function update(BlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $blogCategory->fill($request->safe()->except('is_active'));
        $blogCategory->is_active = $request->boolean('is_active');
        $blogCategory->save();

        return back()->with('status', __('admin.blog.flash.sectionSaved'));
    }

    /**
     * Sections are only removable while empty. The foreign key would null the
     * posts' section rather than refuse, and silently unfiling a dozen articles
     * is not something a delete button should be able to do.
     */
    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        if ($blogCategory->posts()->exists()) {
            return back()->withErrors(['section' => __('admin.blog.flash.sectionInUse')]);
        }

        $blogCategory->delete();

        return back()->with('status', __('admin.blog.flash.sectionDeleted'));
    }
}
