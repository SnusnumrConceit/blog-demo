<?php

namespace App\Http\Requests\Admin\Post;

use App\Enums\PrivacyEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;


class StorePostRequest extends BaseRequest
{
    protected function prepareForValidation()
    {
        $input = $this->request->all();
        $input['title'] = $this->prepareTitleForValidation($input['title']);
        $input['categories'] = $this->prepareCategoriesForValidation($input['categories'] ?? []);

        $this->merge($input);
    }

    /**
     * Предварительная подготовка к валидации Названия
     *
     * @param mixed $title
     *
     * @return ?string
     */
    protected function prepareTitleForValidation(mixed $title): ?string
    {
        return match(gettype($title)) {
            'string' => mb_strtolower($title),
            default => null,
        };
    }

    /**
     * Предварительная подготовка категорий
     *
     * @param mixed $categories
     *
     * @return array|null
     */
    protected function prepareCategoriesForValidation(mixed $categories): ?array
    {
        if (! is_array($categories)) return null;

        return array_values(
            array_unique(
                array_filter($categories, fn (mixed $categoryId) => is_numeric($categoryId) && $categoryId > 0)
            )
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'bail',
                'string',
                'max:100',
                Rule::unique(table: 'posts', column: 'title')
            ],
            'content' => 'required|string|max:65535',
            'privacy' => [
                'nullable',
                Rule::in(PrivacyEnum::getValues())
            ],
            'published_at' => 'nullable|date|after:' . now()->toDateTimeString(),
            'categories' => [
                'bail',
                'required',
                'array',
                'min:1',
                Rule::exists(table: 'categories', column: 'id')
                    ->when(
                        value: ! auth()->user()->isAdmin(),
                        callback: fn (Exists $rule) => $rule->where(
                            fn (Builder $query) => $query->whereNull('privacy')
                                ->orWhere('privacy', PrivacyEnum::PROTECTED->value)
                        ),
                    )
            ],
            'categories.*' => 'required|numeric|min:1'
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['title'] = mb_strtolower($validated['title']);

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
            'title' => 'Заголовок',
            'content' => 'Контент',
            'privacy' => 'Приватность',
            'published_at' => 'Отложенная дата публикации',
            'categories' => 'Категории',
        ];
    }
}
