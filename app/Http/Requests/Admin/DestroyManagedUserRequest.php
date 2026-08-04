<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DestroyManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target && ($this->user()?->can('delete', $target) ?? false);
    }

    public function rules(): array
    {
        return [
            'custom_message' => ['nullable', 'string', 'max:650'],
        ];
    }
}