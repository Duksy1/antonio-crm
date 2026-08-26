<?php

namespace App\Http\Requests;

use App\Enums\ContactStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', Rule::exists('companies', 'id')->where('owner_id', $this->user()->id)],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::enum(ContactStatus::class)],
            'job_title' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
