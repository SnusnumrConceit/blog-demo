<?php

namespace App\Http\Requests\Admin\Post;

use App\Enums\Post\PrivacyEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;


class StorePostRequest extends BaseRequest
{
    protected function prepareForValidation()
    {
        $input = $this->request->all();
        $input['title'] = $this->prepareTitleForValidation($input['title']);

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
            'published_at' => 'nullable|date|after:' . now()->toDayDateTimeString(),
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
        ];
    }
}
