<?php

namespace App\Observers;

use App\Enums\ActivityType;
use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Automatski zapisuje promjene u vremensku crtu povezanih zapisa.
 */
class SystemActivityObserver
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function created(Model $model): void
    {
        $this->logger->log(ActivityType::System, $this->createdSubject($model), $this->context($model));
    }

    public function updated(Model $model): void
    {
        foreach ($this->transitionSubjects($model) as $subject) {
            $this->logger->log(ActivityType::System, $subject, $this->context($model));
        }
    }

    public function deleted(Model $model): void
    {
        if ($model->isForceDeleting()) {
            $this->logger->log(ActivityType::System, 'Trajno obrisano: '.$this->shortName($model), ['owner_id' => $model->owner_id]);

            return;
        }

        $this->logger->log(ActivityType::System, 'Arhivirano: '.$this->shortName($model), $this->context($model));
    }

    private function createdSubject(Model $model): string
    {
        return match (true) {
            $model instanceof Company => 'Tvrtka izrađena: '.$model->name,
            $model instanceof Contact => 'Kontakt izrađen: '.$model->full_name,
            $model instanceof Deal => 'Prilika izrađena: '.$model->title,
            $model instanceof Quote => 'Ponuda izrađena: '.$model->number,
            default => 'Zapis izrađen',
        };
    }

    private function shortName(Model $model): string
    {
        return match (true) {
            $model instanceof Company => $model->name,
            $model instanceof Contact => $model->full_name,
            $model instanceof Deal => $model->title,
            $model instanceof Quote => $model->number,
            default => class_basename($model),
        };
    }

    /**
     * @return array<int, string>
     */
    private function transitionSubjects(Model $model): array
    {
        $subjects = [];

        if ($model instanceof Company && $model->wasChanged('status')) {
            $subjects[] = 'Status tvrtke: '.$this->label($model->getOriginal('status'), CompanyStatus::class).' → '.$model->status->label();
        }

        if ($model instanceof Contact && $model->wasChanged('status')) {
            $subjects[] = 'Status kontakta: '.$this->label($model->getOriginal('status'), ContactStatus::class).' → '.$model->status->label();
        }

        if ($model instanceof Deal) {
            if ($model->wasChanged('stage')) {
                $subjects[] = 'Faza prilike: '.$this->label($model->getOriginal('stage'), DealStage::class).' → '.$model->stage->label();
            }

            if ($model->wasChanged('value')) {
                $subjects[] = 'Vrijednost prilike: '.$this->money($model->getOriginal('value')).' → '.$this->money($model->value).' '.$model->currency;
            }
        }

        if ($model instanceof Quote) {
            if ($model->wasChanged('status')) {
                $subjects[] = 'Status ponude: '.$this->label($model->getOriginal('status'), QuoteStatus::class).' → '.$model->status->label();
            }

            if ($model->wasChanged('total')) {
                $subjects[] = 'Ukupan iznos ponude: '.$this->money($model->getOriginal('total')).' → '.$this->money($model->total).' '.$model->currency;
            }
        }

        return $subjects;
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     */
    private function label(mixed $value, string $enum): string
    {
        if ($value instanceof UnitEnum && method_exists($value, 'label')) {
            return $value->label();
        }

        $case = is_string($value) ? $enum::tryFrom($value) : null;

        return $case instanceof UnitEnum && method_exists($case, 'label') ? $case->label() : (string) $value;
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }

    /**
     * @return array{owner_id: int|null, company_id?: int|null, contact_id?: int|null, deal_id?: int|null, quote_id?: int|null}
     */
    private function context(Model $model): array
    {
        $context = ['owner_id' => $model->owner_id];

        if ($model instanceof Company) {
            $context['company_id'] = $model->id;
        }

        if ($model instanceof Contact) {
            $context['company_id'] = $model->company_id;
            $context['contact_id'] = $model->id;
        }

        if ($model instanceof Deal) {
            $context['company_id'] = $model->company_id;
            $context['contact_id'] = $model->contact_id;
            $context['deal_id'] = $model->id;
        }

        if ($model instanceof Quote) {
            $context['company_id'] = $model->company_id;
            $context['contact_id'] = $model->contact_id;
            $context['deal_id'] = $model->deal_id;
            $context['quote_id'] = $model->id;
        }

        return $context;
    }
}
