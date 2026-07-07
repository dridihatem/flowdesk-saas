<?php

namespace App\View\Components;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\Component;

class AdminCompanyContactWidget extends Component
{
    /** @var list<array{company: Company, contact_email: ?string}> */
    public array $companies;

    public function __construct()
    {
        $this->companies = Company::query()
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(function (Company $c): array {
                $email = $c->contact_email;
                if (! is_string($email) || $email === '') {
                    $admin = User::query()
                        ->where('company_id', $c->id)
                        ->whereHas('roles', fn (Builder $q) => $q->where('name', 'company_admin'))
                        ->orderBy('id')
                        ->first();
                    $email = $admin?->email;
                }

                return [
                    'company' => $c,
                    'contact_email' => is_string($email) && $email !== '' ? $email : null,
                ];
            })
            ->all();
    }

    public function render(): View
    {
        return view('components.admin-company-contact-widget');
    }
}
