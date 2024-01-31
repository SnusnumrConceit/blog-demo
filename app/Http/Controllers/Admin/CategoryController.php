<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Category\PrivacyEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View|Factory
    {
        $categories = Category::paginate(15, ['name', 'privacy', 'created_at', 'updated_at']);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View|Factory
    {
        $privacyItems = PrivacyEnum::getValues();

        return view('admin.categories.create', compact('privacyItems'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse|Redirector
    {
        $category = Category::create($request->validated());

        return redirect(route('admin.categories.show', ['category' => $category->id]))
            ->with(key: 'success', value: 'Категория успешно создана');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): View|Factory
    {
        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): View|Factory
    {
        $privacyItems = PrivacyEnum::getValues();

        return view('admin.categories.edit', compact('category', 'privacyItems'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse|Redirector
    {
        $category->update($request->validated());

        return redirect(route('admin.categories.show', ['category' => $category->id]))
            ->with(key: 'success', value: 'Категория успешно обновлена');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse|Redirector
    {
        $category->delete();

        return redirect(route('admin.categories.index'))->with(key: 'success', value: 'Категория успешно удалена');
    }
}
