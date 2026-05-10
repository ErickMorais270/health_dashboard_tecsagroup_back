<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Models\Biomarker;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * Chama o Google Gemini e devolve exatamente três recomendações.
 * Em qualquer falha (rede, HTTP, JSON inválido, chave ausente), usa recomendações padrão em português.
 */
final class GenerateHealthRecommendationsAction
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        Você é um coach de bem-estar (não médico): nunca diagnostique, prescreva medicamentos ou garanta cura.
        Use linguagem prudente e baseada em hábitos. Os dados são apenas números informados pelo usuário.

        Responda APENAS com JSON válido neste formato exato (sem markdown, sem chaves extras):
        {"recommendations":["texto1","texto2","texto3"]}

        Cada texto é uma recomendação curta e prática (sono, alimentação, movimento, hidratação, estresse ou
        orientação genérica para procurar profissional se algo parecer preocupante). Exatamente três itens, em português do Brasil.
    PROMPT;

    /**
     * @return array{recommendations: list<string>, raw: string}
     */
    public function execute(User $user, Biomarker $biomarker): array
    {
        try {
            return $this->callGemini($user, $biomarker);
        } catch (Throwable $e) {
            Log::warning('health_ai.gemini_unexpected', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->fallbackPayload($biomarker, 'unexpected:'.$e::class);
        }
    }

    /**
     * @return array{recommendations: list<string>, raw: string}
     */
    private function callGemini(User $user, Biomarker $biomarker): array
    {
        $apiKey = trim((string) config('services.gemini.key', ''));
        $model = (string) config('services.gemini.model', 'gemini-2.0-flash');
        $baseUrl = rtrim((string) config('services.gemini.url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        if ($apiKey === '') {
            Log::notice('health_ai.gemini_key_missing');

            return $this->fallbackPayload($biomarker, 'missing_api_key');
        }

        $userContent = sprintf(
            'Usuário id %d. Biomarcador id %d. Sono: %s h. Glicose: %s mg/dL. Frequência cardíaca: %d bpm (repouso; não há HRV neste MVP).',
            $user->id,
            $biomarker->id,
            (string) $biomarker->sleep_hours,
            (string) $biomarker->glucose_level,
            $biomarker->heart_rate,
        );

        $url = sprintf('%s/models/%s:generateContent', $baseUrl, $model);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->acceptJson()
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => self::SYSTEM_PROMPT]],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $userContent]],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (Throwable $e) {
            Log::error('health_ai.gemini_transport', ['exception' => $e]);

            return $this->fallbackPayload($biomarker, 'transport');
        }

        $raw = $response->body();

        if (! $response->successful()) {
            Log::warning('health_ai.gemini_http', [
                'status' => $response->status(),
                'body' => $raw,
            ]);

            return $this->fallbackPayload($biomarker, 'http_'.$response->status(), $raw);
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (! is_string($text) || trim($text) === '') {
            Log::warning('health_ai.gemini_shape', ['body' => $raw]);

            return $this->fallbackPayload($biomarker, 'empty_model_text', $raw);
        }

        try {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            Log::warning('health_ai.gemini_json', ['text' => $text]);

            return $this->fallbackPayload($biomarker, 'invalid_json', $raw);
        }

        $list = $decoded['recommendations'] ?? null;
        if (! is_array($list) || count($list) !== 3) {
            Log::warning('health_ai.gemini_three', ['decoded' => $decoded]);

            return $this->fallbackPayload($biomarker, 'three_required', $raw);
        }

        $normalized = [];
        foreach ($list as $item) {
            if (! is_string($item) || trim($item) === '') {
                Log::warning('health_ai.gemini_entry');

                return $this->fallbackPayload($biomarker, 'invalid_entry', $raw);
            }

            $normalized[] = trim($item);
        }

        return [
            'recommendations' => $normalized,
            'raw' => $raw,
        ];
    }

    /**
     * @return array{recommendations: list<string>, raw: string}
     */
    private function fallbackPayload(Biomarker $biomarker, string $reason, ?string $providerBody = null): array
    {
        $sleep = (float) $biomarker->sleep_hours;
        $glucose = (float) $biomarker->glucose_level;
        $hr = $biomarker->heart_rate;

        $r1 = $sleep < 6.5
            ? 'Priorize 7–8 h de sono: defina um horário fixo para despertar, inclusive nos fins de semana.'
            : 'Mantenha janela de sono consistente; evite cafeína no fim do dia e telas 60 min antes de deitar.';

        $r2 = $glucose > 125
            ? 'Para glicose mais alta, prefira refeições com fibras e proteínas e evite picos de açúcar refinado entre as refeições.'
            : 'Distribua carboidratos ao longo do dia com vegetais e proteínas para manter glicose mais estável.';

        $r3 = $hr > 100
            ? 'FC elevada pode refletir estresse ou esforço recente: experimente 5 min de respiração lenta e hidratação.'
            : 'Inclua caminhadas leves ou alongamentos diários; monitore como se sente e busque orientação médica se notar sintomas.';

        $meta = [
            'fallback' => true,
            'reason' => $reason,
            'biomarker_id' => $biomarker->id,
        ];
        if ($providerBody !== null && strlen($providerBody) < 4000) {
            $meta['provider_excerpt'] = $providerBody;
        }

        return [
            'recommendations' => [$r1, $r2, $r3],
            'raw' => json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ];
    }
}
