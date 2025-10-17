<?php

namespace muba00\LaravelLiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkMessagesAsReadRequest extends FormRequest
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
            'message_ids' => ['sometimes', 'array'],
            'message_ids.*' => ['integer', 'exists:live_chat_messages,id'],
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
            'message_ids.array' => 'Message IDs must be an array.',
            'message_ids.*.integer' => 'Each message ID must be an integer.',
            'message_ids.*.exists' => 'One or more message IDs do not exist.',
        ];
    }
}
