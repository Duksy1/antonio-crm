<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $owner = $this->user()->id;
        $types = array_map(fn (ActivityType $type) => $type->value, ActivityType::selectable());

        return [
            'type' => ['required', Rule::in($types)],
            'subject' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'done' => ['nullable', 'boolean'],
            'company_id' => ['nullable', Rule::exists('companies', 'id')->where('owner_id', $owner)->whereNull('deleted_at')],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('owner_id', $owner)->whereNull('deleted_at')],
            'deal_id' => ['nullable', Rule::exists('deals', 'id')->where('owner_id', $owner)->whereNull('deleted_at')],
            'quote_id' => ['nullable', Rule::exists('quotes', 'id')->where('owner_id', $owner)->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['subject' => 'naslov', 'due_at' => 'rok', 'type' => 'tip aktivnosti'];
    }
}
