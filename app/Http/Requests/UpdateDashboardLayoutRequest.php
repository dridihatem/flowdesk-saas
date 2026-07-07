<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && $user->company_id
            && $user->hasAnyRole(['company_admin', 'team_member']);
    }

    public function rules(): array
    {
        return [
            'widgets' => ['required', 'array', 'min:1'],
            'widgets.*.key' => ['required', 'string', 'max:64'],
            'widgets.*.enabled' => ['sometimes', 'boolean'],
            'widgets.*.order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
