<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactUpdateFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Contact $contact */
        $contact = $this->contact;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contacts', 'email')->ignore($contact->id),
                app()->isLocal() ?
                    Rule::email() :
                    Rule::email()
                        ->rfcCompliant(
                            strict: true
                        )
                        ->validateMxRecord()
                        ->preventSpoofing(),
            ],
            'phone' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contacts', 'phone')->ignore($contact->id),
            ],
        ];
    }
}
