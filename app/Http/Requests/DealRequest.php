<?php

namespace App\Http\Requests;

use App\Enums\DealStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', Rule::exists('companies', 'id')->where('owner_id', $this->user()->id)],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('owner_id', $this->user()->id)],
            'title' => ['required', 'string', 'max:255'],
            'stage' => ['required', Rule::enum(DealStage::class)],
            'value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'probability' => ['required', 'integer', 'between:0,100'],
            'expected_close_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
