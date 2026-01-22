<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class LoginFormRequest extends FormRequest
{
    protected ?User $user = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! Auth::guard('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'max:255',
                app()->isLocal() ?
                    Rule::email() :
                    Rule::email()
                        ->rfcCompliant(
                            strict: true
                        )
                        ->validateMxRecord()
                        ->preventSpoofing(),
            ],
            'password' => [
                'required',
                'string',
                'max:255',
                app()->isLocal() ? Password::min(8) : Password::min(
                    size: 8
                )->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(
                        threshold: 3
                    ),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->findUser();
            /** @var string $password */
            $password = $this->input('password');

            if ($this->filled('email') && blank($user)) {
                $validator->errors()->add('email', __('auth.failed'));
            }

            if ($user) {
                /** @var string $hashPassword */
                $hashPassword = $user->password;

                if (! Hash::check($password, $hashPassword)) {
                    $validator->errors()->add('password', __('auth.password'));
                }
            }
        });
    }

    public function findUser(): ?User
    {
        if (! $this->user) {
            $this->user = User::query()->where('email', $this->input('email'))->first();
        }

        return $this->user;
    }
}
