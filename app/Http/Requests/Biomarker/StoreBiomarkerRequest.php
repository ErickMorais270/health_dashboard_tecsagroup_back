<?php

declare(strict_types=1);

namespace App\Http\Requests\Biomarker;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBiomarkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sleep_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'glucose_level' => ['required', 'numeric', 'min:20', 'max:600'],
            'heart_rate' => ['required', 'integer', 'min:30', 'max:220'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sleep_hours' => 'sono (horas)',
            'glucose_level' => 'glicose',
            'heart_rate' => 'frequência cardíaca',
        ];
    }
}
