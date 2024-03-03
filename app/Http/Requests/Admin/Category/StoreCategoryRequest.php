<?php

namespace App\Http\Requests\Admin\Category;

use App\Enums\PrivacyEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends BaseRequest
{
    protected function prepareForValidation()
    {
        $input = $this->request->all();
        $input['name'] = $this->prepareNameForValidation($input['name']);

        $this->merge($input);
    }

    /**
     * Предварительная подготовка к валидации Названия
     *
     * @param mixed $name
     *
     * @return ?string
     */
    protected function prepareNameForValidation(mixed $name): ?string
    {
        return match(gettype($name)) {
            'string' => mb_strtolower($name),
            default => null,
        };
    }

    /**
     * Правила валидации
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'bail',
                'string',
                'max:100',
                Rule::unique(table: 'categories', column: 'name')
            ],
            'privacy' => [
                'nullable',
                Rule::in(PrivacyEnum::getValues())
            ],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['name'] = mb_strtolower($validated['name']);

        return $validated;
    }

    /**
     * Атрибуты
     *
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'name' => 'Название категории',
            'privacy' => 'Приватность',
        ];
    }
}
