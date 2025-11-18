<?php

namespace App\Http\Requests\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'yandex_map_url' => [
                'required',
                'url',
                'regex:/(yandex\.(ru|uz|com)\/maps|maps\.yandex\.(ru|uz|com))/i',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'yandex_map_url.required' => 'Поле ссылки обязательно для заполнения',
            'yandex_map_url.url' => 'Введите корректную ссылку',
            'yandex_map_url.regex' => 'Ссылка должна быть на Яндекс Карты',
        ];
    }
}
