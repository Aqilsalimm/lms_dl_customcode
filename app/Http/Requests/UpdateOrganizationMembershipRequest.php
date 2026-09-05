<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');
        return auth()->check() && $membership && $membership->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            // Membership Fields
            'member_type' => ['required', 'string', 'in:employee_id,nip,nim,nis,member_id,other'],
            'member_number' => ['required', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            
            // Profile Fields
            'legal_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
        ];
    }
}
