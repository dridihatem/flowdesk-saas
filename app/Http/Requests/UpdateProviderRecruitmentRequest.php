<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderRecruitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && $user->company_id
            && $user->hasRole('company_admin');
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'provider_recruitment_enabled' => ['nullable', 'boolean'],
            'provider_recruitment_slug' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('companies', 'provider_recruitment_slug')->ignore($companyId),
            ],
            'provider_partnership_terms' => ['nullable', 'string', 'max:100000'],
            'regenerate_slug' => ['nullable', 'boolean'],
        ];
    }
}
