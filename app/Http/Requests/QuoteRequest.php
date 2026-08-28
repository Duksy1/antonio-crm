<?php

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deal_id' => ['nullable', Rule::exists('deals', 'id')->where('owner_id', $this->user()->id)],
            'company_id' => ['nullable', Rule::exists('companies', 'id')->where('owner_id', $this->user()->id)],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('owner_id', $this->user()->id)],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(QuoteStatus::class)],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'size:3'],
            'discount_percent' => ['required', 'decimal:0,2', 'between:0,100'],
            'tax_percent' => ['required', 'decimal:0,2', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'decimal:0,2', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.unit_price' => ['required', 'decimal:0,2', 'min:0'],
        ];
    }
}
