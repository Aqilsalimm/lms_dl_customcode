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
            'otp_code' => ['required', 'digits:6'],
        ];
    }
}