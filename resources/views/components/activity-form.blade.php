@props(['companyId' => null, 'contactId' => null, 'dealId' => null, 'quoteId' => null, 'placeholder' => 'Što treba napraviti?', 'compact' => false])
<form class="activity-form {{ $compact ? 'is-compact' : '' }}" method="POST" action="{{ route('activities.store') }}">
    @csrf
    <input type="hidden" name="company_id" value="{{ $companyId }}">
    <input type="hidden" name="contact_id" value="{{ $contactId }}">
    <input type="hidden" name="deal_id" value="{{ $dealId }}">
    <input type="hidden" name="quote_id" value="{{ $quoteId }}">
    <div class="activity-form-row">
        <select class="filter-select" name="type" aria-label="Tip aktivnosti">
            @foreach (App\Enums\ActivityType::selectable() as $type)
                <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->icon() }} {{ $type->label() }}</option>
            @endforeach
        </select>
        <input type="text" name="subject" value="{{ old('subject') }}" placeholder="{{ $placeholder }}" maxlength="255" required>
        <input type="datetime-local" name="due_at" value="{{ old('due_at') }}" aria-label="Rok">
        <label class="check-inline"><input type="checkbox" name="done" value="1" @checked(old('done'))> već obavljeno</label>
        <button class="primary-btn" type="submit">Dodaj</button>
    </div>
    <textarea name="notes" rows="2" maxlength="5000" placeholder="Bilješke (opcionalno)">{{ old('notes') }}</textarea>
    <x-form-errors />
</form>
