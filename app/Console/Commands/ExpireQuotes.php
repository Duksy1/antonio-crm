<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Console\Command;

class ExpireQuotes extends Command
{
    protected $signature = 'quotes:expire';

    protected $description = 'Označi poslane ponude kojima je istekao rok valjanosti';

    public function handle(): int
    {
        $quotes = Quote::query()
            ->where('status', QuoteStatus::Sent->value)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->get();

        // Pojedinačni update pokreće observer koji promjenu upisuje u vremensku crtu.
        $quotes->each(fn (Quote $quote) => $quote->update(['status' => QuoteStatus::Expired]));

        $this->info('Isteklo ponuda: '.$quotes->count());

        return self::SUCCESS;
    }
}
