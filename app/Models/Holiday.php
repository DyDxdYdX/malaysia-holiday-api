<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'holiday_source_id',
    'holiday_import_batch_id',
    'year',
    'state_code',
    'state_codes',
    'name',
    'date',
    'day_name',
    'scope',
    'type',
    'is_subject_to_change',
    'status',
    'source_note',
])]
class Holiday extends Model
{
    use HasFactory;

    /**
     * @var list<string>|null
     */
    private ?array $legacyStateCodes = null;

    protected static function booted(): void
    {
        static::creating(function (Holiday $holiday): void {
            $legacyStateCode = $holiday->attributes['state_code'] ?? null;
            $legacyStateCodes = $holiday->attributes['state_codes'] ?? null;

            if (is_string($legacyStateCode) && trim($legacyStateCode) !== '') {
                $holiday->legacyStateCodes = [strtoupper(trim($legacyStateCode))];
                unset($holiday->attributes['state_code']);
            }

            if (is_string($legacyStateCodes) && trim($legacyStateCodes) !== '') {
                $holiday->legacyStateCodes = collect(preg_split('/[\s,|]+/', strtoupper($legacyStateCodes)) ?: [])
                    ->map(fn (string $stateCode): string => trim($stateCode))
                    ->filter(fn (string $stateCode): bool => $stateCode !== '')
                    ->unique()
                    ->values()
                    ->all();
                unset($holiday->attributes['state_codes']);
            }
        });

        static::created(function (Holiday $holiday): void {
            if ($holiday->legacyStateCodes !== null) {
                $holiday->syncStateCodes($holiday->legacyStateCodes);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_subject_to_change' => 'boolean',
        ];
    }

    /**
     * Get the source document this holiday came from.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(HolidaySource::class, 'holiday_source_id');
    }

    /**
     * Get the import batch this holiday belongs to.
     */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(HolidayImportBatch::class, 'holiday_import_batch_id');
    }

    /**
     * Get the overrides applied to this holiday.
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(HolidayOverride::class);
    }

    /**
     * Get the state assignments for this holiday.
     */
    public function states(): HasMany
    {
        return $this->hasMany(HolidayState::class);
    }

    /**
     * Sync the holiday's associated states.
     *
     * @param  list<string>  $stateCodes
     */
    public function syncStateCodes(array $stateCodes): void
    {
        $normalized = collect($stateCodes)
            ->map(fn (string $stateCode): string => strtoupper(trim($stateCode)))
            ->filter(fn (string $stateCode): bool => $stateCode !== '')
            ->unique()
            ->values()
            ->all();

        $this->states()->delete();

        if ($normalized === []) {
            return;
        }

        $this->states()->createMany(
            array_map(
                fn (string $stateCode): array => ['state_code' => $stateCode],
                $normalized
            )
        );
    }

    /**
     * @return list<string>
     */
    public function stateCodes(): array
    {
        if (! $this->relationLoaded('states')) {
            $this->load('states');
        }

        /** @var Collection<int, HolidayState> $states */
        $states = $this->getRelation('states');

        return $states
            ->pluck('state_code')
            ->sort()
            ->values()
            ->all();
    }

    public function getStateCodesAttribute(): string
    {
        return implode(',', $this->stateCodes());
    }

    public function getStateCodeAttribute(): ?string
    {
        return $this->stateCodes()[0] ?? null;
    }
}
