<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = [
            'required',
            'bail',
            'string',
            'max:100',
            Rule::unique(table: 'categories', column: 'name')->ignore($this->route('category')->id),
        ];

        return $rules;
    }
}
