<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\User;
use App\Services\QuoteCalculator;
use App\Services\QuoteNumberGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(QuoteCalculator $calculator, QuoteNumberGenerator $numberGenerator): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@apexflow-crm.test'],
            ['name' => 'Ana Vuković', 'password' => Hash::make('ApexFlow123!'), 'email_verified_at' => now()],
        );

        if ($user->companies()->exists()) {
            return;
        }

        $companies = collect([
            ['name' => 'Orbita Digital', 'status' => CompanyStatus::Customer, 'industry' => 'SaaS', 'website' => 'https://example.com', 'email' => 'hello@orbita.test', 'phone' => '+385 1 555 2100', 'city' => 'Zagreb', 'country' => 'HR', 'employees' => 42, 'annual_revenue' => 1850000, 'notes' => 'Klijent s velikim potencijalom za proširenje suradnje.'],
            ['name' => 'Studio Sjever', 'status' => CompanyStatus::Prospect, 'industry' => 'Arhitektura', 'email' => 'info@sjever.test', 'phone' => '+385 51 220 448', 'city' => 'Rijeka', 'country' => 'HR', 'employees' => 18, 'annual_revenue' => 720000],
            ['name' => 'Greenline Systems', 'status' => CompanyStatus::Lead, 'industry' => 'CleanTech', 'email' => 'office@greenline.test', 'city' => 'Ljubljana', 'country' => 'SI', 'employees' => 65, 'annual_revenue' => 3100000],
            ['name' => 'Nautica Labs', 'status' => CompanyStatus::Customer, 'industry' => 'IoT', 'email' => 'team@nautica.test', 'city' => 'Split', 'country' => 'HR', 'employees' => 27, 'annual_revenue' => 980000],
            ['name' => 'Kobalt Retail', 'status' => CompanyStatus::Prospect, 'industry' => 'Maloprodaja', 'email' => 'uprava@kobalt.test', 'city' => 'Osijek', 'country' => 'HR', 'employees' => 115, 'annual_revenue' => 6400000],
            ['name' => 'Mikroforma', 'status' => CompanyStatus::Lead, 'industry' => 'Proizvodnja', 'email' => 'kontakt@mikroforma.test', 'city' => 'Varaždin', 'country' => 'HR', 'employees' => 34, 'annual_revenue' => 1250000],
            ['name' => 'Adria Works', 'status' => CompanyStatus::Inactive, 'industry' => 'Savjetovanje', 'email' => 'info@adriaworks.test', 'city' => 'Zadar', 'country' => 'HR', 'employees' => 9, 'annual_revenue' => 310000],
        ])->map(fn (array $data) => $user->companies()->create($data));

        $contacts = collect([
            ['company' => 0, 'first_name' => 'Marta', 'last_name' => 'Kovač', 'status' => ContactStatus::DecisionMaker, 'job_title' => 'CEO', 'email' => 'marta@orbita.test', 'phone' => '+385 98 555 120', 'notes' => 'Preferira kratke statusne pozive četvrtkom.'],
            ['company' => 0, 'first_name' => 'Ivan', 'last_name' => 'Marić', 'status' => ContactStatus::Active, 'job_title' => 'Head of Product', 'email' => 'ivan@orbita.test', 'phone' => '+385 91 220 181'],
            ['company' => 1, 'first_name' => 'Petra', 'last_name' => 'Radić', 'status' => ContactStatus::DecisionMaker, 'job_title' => 'Partnerica', 'email' => 'petra@sjever.test', 'phone' => '+385 99 412 803'],
            ['company' => 2, 'first_name' => 'Luka', 'last_name' => 'Novak', 'status' => ContactStatus::New, 'job_title' => 'COO', 'email' => 'luka@greenline.test'],
            ['company' => 3, 'first_name' => 'Ana', 'last_name' => 'Bilić', 'status' => ContactStatus::Active, 'job_title' => 'CTO', 'email' => 'ana@nautica.test', 'phone' => '+385 95 880 041'],
            ['company' => 4, 'first_name' => 'Marko', 'last_name' => 'Horvat', 'status' => ContactStatus::DecisionMaker, 'job_title' => 'Direktor prodaje', 'email' => 'marko@kobalt.test'],
            ['company' => 5, 'first_name' => 'Ema', 'last_name' => 'Pavić', 'status' => ContactStatus::New, 'job_title' => 'Voditeljica operacija', 'email' => 'ema@mikroforma.test'],
            ['company' => 6, 'first_name' => 'Filip', 'last_name' => 'Jurić', 'status' => ContactStatus::Inactive, 'job_title' => 'Konzultant', 'email' => 'filip@adriaworks.test'],
        ])->map(function (array $data) use ($user, $companies) {
            $companyIndex = $data['company'];
            unset($data['company']);

            return $user->contacts()->create([...$data, 'company_id' => $companies[$companyIndex]->id]);
        });

        $deals = collect([
            ['company' => 0, 'contact' => 0, 'title' => 'Enterprise onboarding platforma', 'stage' => DealStage::Negotiation, 'value' => 42000, 'probability' => 75, 'close' => 21],
            ['company' => 1, 'contact' => 2, 'title' => 'Novi web i klijentski portal', 'stage' => DealStage::Proposal, 'value' => 18500, 'probability' => 55, 'close' => 35],
            ['company' => 2, 'contact' => 3, 'title' => 'CRM integracija za DACH tržište', 'stage' => DealStage::Discovery, 'value' => 36000, 'probability' => 35, 'close' => 52],
            ['company' => 3, 'contact' => 4, 'title' => 'IoT fleet dashboard', 'stage' => DealStage::Won, 'value' => 27500, 'probability' => 100, 'close' => -8],
            ['company' => 4, 'contact' => 5, 'title' => 'Omnichannel loyalty program', 'stage' => DealStage::Qualification, 'value' => 68000, 'probability' => 20, 'close' => 74],
            ['company' => 5, 'contact' => 6, 'title' => 'Digitalizacija proizvodnih naloga', 'stage' => DealStage::Proposal, 'value' => 24000, 'probability' => 60, 'close' => 29],
            ['company' => 6, 'contact' => 7, 'title' => 'Savjetodavni portal', 'stage' => DealStage::Lost, 'value' => 12000, 'probability' => 0, 'close' => -18],
        ])->map(function (array $data) use ($user, $companies, $contacts) {
            return $user->deals()->create(['company_id' => $companies[$data['company']]->id, 'contact_id' => $contacts[$data['contact']]->id, 'title' => $data['title'], 'stage' => $data['stage'], 'value' => $data['value'], 'currency' => 'EUR', 'probability' => $data['probability'], 'expected_close_date' => now()->addDays($data['close']), 'description' => 'Ključna prilika s jasno definiranim sljedećim koracima i vlasnikom procesa.']);
        });

        foreach ([
            ['deal' => 0, 'status' => QuoteStatus::Sent, 'title' => 'Enterprise onboarding — faza 1', 'amount' => 32000],
            ['deal' => 1, 'status' => QuoteStatus::Draft, 'title' => 'Web i klijentski portal', 'amount' => 18500],
            ['deal' => 3, 'status' => QuoteStatus::Accepted, 'title' => 'IoT fleet dashboard', 'amount' => 27500],
            ['deal' => 5, 'status' => QuoteStatus::Sent, 'title' => 'Digitalizacija naloga', 'amount' => 24000],
        ] as $index => $data) {
            $deal = $deals[$data['deal']];
            $analysisPrice = intdiv($data['amount'], 4);
            $items = [
                ['description' => 'Analiza i dizajn rješenja', 'quantity' => '1', 'unit' => 'paket', 'unit_price' => (string) $analysisPrice],
                ['description' => 'Implementacija i puštanje u rad', 'quantity' => '1', 'unit' => 'paket', 'unit_price' => (string) ($data['amount'] - $analysisPrice)],
            ];
            $totals = $calculator->calculate($items, 0, 25);
            $quote = $user->quotes()->create([
                'deal_id' => $deal->id,
                'company_id' => $deal->company_id,
                'contact_id' => $deal->contact_id,
                'number' => $numberGenerator->next(),
                'title' => $data['title'],
                'status' => $data['status'],
                'issue_date' => now()->subDays(6 - $index),
                'valid_until' => now()->addDays(8 + $index * 3),
                'currency' => 'EUR',
                'discount_percent' => 0,
                'tax_percent' => 25,
                ...collect($totals)->except('items')->all(),
                'notes' => 'Hvala na ukazanom povjerenju.',
                'terms' => 'Plaćanje u roku 15 dana od prihvaćanja ponude.',
            ]);
            $quote->items()->createMany($totals['items']);
        }

        foreach ($user->quotes()->get() as $quote) {
            $user->activities()->create([
                'company_id' => $quote->company_id,
                'contact_id' => $quote->contact_id,
                'deal_id' => $quote->deal_id,
                'quote_id' => $quote->id,
                'type' => ActivityType::System,
                'subject' => 'Ponuda izrađena: '.$quote->number,
                'completed_at' => $quote->created_at,
            ]);
        }

        $activities = [
            ['type' => ActivityType::Call, 'subject' => 'Pozvati Martu za potvrdu opsega isporuke', 'company' => 0, 'contact' => 0, 'deal' => 0, 'due' => now()->addHours(3), 'done' => false],
            ['type' => ActivityType::Task, 'subject' => 'Poslati revidiranu ponudu s novim rokovima', 'company' => 0, 'contact' => 1, 'deal' => 0, 'due' => now()->subDays(2), 'done' => false],
            ['type' => ActivityType::Meeting, 'subject' => 'Radionica s arhitektima Studija Sjever', 'company' => 1, 'contact' => 2, 'deal' => 1, 'due' => now()->addDays(2), 'done' => false],
            ['type' => ActivityType::Task, 'subject' => 'Pripremiti tehničku specifikaciju za DACH', 'company' => 2, 'contact' => 3, 'deal' => 2, 'due' => now()->addDays(5), 'done' => false],
            ['type' => ActivityType::Email, 'subject' => 'Poslati sažetak sastanka i zapisnik', 'company' => 3, 'contact' => 4, 'deal' => 3, 'due' => now()->subDay(), 'done' => true],
            ['type' => ActivityType::Note, 'subject' => 'Klijent traži faznu isporuku kroz dva kvartala', 'company' => 4, 'contact' => 5, 'deal' => 4, 'due' => null, 'done' => true],
            ['type' => ActivityType::Task, 'subject' => 'Dogovoriti demo za loyalty program', 'company' => 4, 'contact' => 5, 'deal' => 4, 'due' => now()->addDay(), 'done' => false],
            ['type' => ActivityType::Call, 'subject' => 'Provjeriti zadovoljstvo nakon isporuke', 'company' => 5, 'contact' => 6, 'deal' => 5, 'due' => now()->subDays(6), 'done' => true],
        ];

        foreach ($activities as $data) {
            $user->activities()->create([
                'company_id' => $companies[$data['company']]->id,
                'contact_id' => isset($data['contact']) ? $contacts[$data['contact']]->id : null,
                'deal_id' => isset($data['deal']) ? $deals[$data['deal']]->id : null,
                'type' => $data['type'],
                'subject' => $data['subject'],
                'notes' => 'Zabilježeno tijekom redovnog tjednog pregleda prodaje.',
                'due_at' => $data['due'],
                'completed_at' => $data['done'] ? now()->subHours(4) : null,
            ]);
        }
    }
}
