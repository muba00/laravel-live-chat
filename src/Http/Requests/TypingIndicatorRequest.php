<?php

namespace muba00\LaravelLiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypingIndicatorRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_typing' => ['required', 'boolean'],
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
            'is_typing.required' => 'Typing status is required.',
            'is_typing.boolean' => 'Typing status must be a boolean.',
        ];
    }
}
