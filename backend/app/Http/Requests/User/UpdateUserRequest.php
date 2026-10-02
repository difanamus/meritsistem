<?php

namespace App\Http\Requests\User;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $account = $this->route('user');
        $personRules = ['required_unless:role,system_admin', 'nullable', 'integer', Rule::unique('users', 'personel_id')->ignore($account)];
        if ($account->personel_id !== null) {
            $personRules[] = 'required';
            $personRules[] = Rule::in([$account->personel_id]);
        }
        $personRules[] = Rule::exists('personel', 'id')->where(fn ($query) => $query->where(function ($query) use ($account): void {
            $query->where(fn ($query) => $query->where('status', 'aktif')->whereNull('deleted_at'));
            if (! $this->boolean('is_active') && $account->personel_id !== null) {
                $query->orWhere('id', $account->personel_id);
            }
        }));

        return [
            'name' => ['required_without:personel_id', 'nullable', 'string', 'max:255'],
            'personel_id' => $personRules,
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['required', 'boolean'],
            'scopes' => ['present', 'array'],
            'scopes.*' => ['array:unit_organisasi_id,scope_type,is_active,berlaku_mulai,berlaku_sampai'],
            'scopes.*.unit_organisasi_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('unit_organisasi', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'scopes.*.scope_type' => ['required', Rule::enum(ScopeType::class)],
            'scopes.*.is_active' => ['required', 'boolean'],
            'scopes.*.berlaku_mulai' => ['nullable', 'date'],
            'scopes.*.berlaku_sampai' => ['nullable', 'date', 'after_or_equal:scopes.*.berlaku_mulai'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $role = $this->input('role');

                if ($this->user()->role === UserRole::AdminSsdm && $role !== UserRole::Operator->value) {
                    $validator->errors()->add('role', 'Admin SSDM hanya dapat menetapkan role Operator.');
                }

                $scopes = $this->input('scopes', []);
                if ($role !== UserRole::Operator->value && $scopes !== []) {
                    $validator->errors()->add('scopes', 'Akun global tidak boleh memiliki cakupan unit.');
                }
                if ($role === UserRole::Operator->value && $this->boolean('is_active') && ! collect($scopes)->contains('is_active', true)) {
                    $validator->errors()->add('scopes', 'Operator wajib memiliki minimal satu cakupan aktif.');
                }
            },
        ];
    }
}
