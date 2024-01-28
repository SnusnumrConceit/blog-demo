<?php

namespace App\Http\Requests\Admin\Post;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends StorePostRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['title'] = [
            'required',
            'bail',
            'string',
            'max:100',
            Rule::unique(table: 'posts', column: 'title')->ignore($this->route('post')->id),
        ];


        return $rules;
    }
}
