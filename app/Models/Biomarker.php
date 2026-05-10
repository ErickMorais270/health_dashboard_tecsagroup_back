<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BiomarkerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $sleep_hours
 * @property string $glucose_level
 * @property int $heart_rate
 * @property list<string>|null $ai_recommendations
 */
final class Biomarker extends Model
{
    /** @use HasFactory<BiomarkerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sleep_hours',
        'glucose_level',
        'heart_rate',
        'ai_recommendations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sleep_hours' => 'decimal:2',
            'glucose_level' => 'decimal:2',
            'heart_rate' => 'integer',
            'ai_recommendations' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<HealthRecommendation, $this>
     */
    public function healthRecommendations(): HasMany
    {
        return $this->hasMany(HealthRecommendation::class);
    }

    /**
     * Recomendação gerada junto com esta medição (uma por biomarcador no fluxo atual).
     *
     * @return HasOne<HealthRecommendation, $this>
     */
    public function latestRecommendation(): HasOne
    {
        return $this->hasOne(HealthRecommendation::class)->latestOfMany();
    }

    /**
     * Recomendações estáveis para o painel: coluna na medição (prioridade) ou registro em health_recommendations.
     *
     * @return list<string>
     */
    public function recommendationsForDisplay(): array
    {
        $stored = $this->ai_recommendations;
        if (is_array($stored) && $stored !== []) {
            $out = [];
            foreach ($stored as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $out[] = trim($item);
                }
            }

            return $out;
        }

        return $this->latestRecommendation?->recommendations ?? [];
    }

    /**
     * @return array{id: int, biomarker_id: int, recommendations: list<string>, created_at: string}|null
     */
    public function healthRecommendationPayload(): ?array
    {
        $tips = $this->recommendationsForDisplay();
        if ($tips === []) {
            return null;
        }

        $rec = $this->relationLoaded('latestRecommendation')
            ? $this->latestRecommendation
            : $this->latestRecommendation()->first();

        return [
            'id' => $rec?->id ?? $this->id,
            'biomarker_id' => $this->id,
            'recommendations' => $tips,
            'created_at' => $rec?->created_at?->toIso8601String()
                ?? ($this->created_at?->toIso8601String() ?? ''),
        ];
    }
}
