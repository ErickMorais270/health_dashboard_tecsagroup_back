<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BiomarkerRepositoryInterface;
use App\Models\Biomarker;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class BiomarkerRepository implements BiomarkerRepositoryInterface
{
    public function allForUser(User $user): Collection
    {
        return Biomarker::query()
            ->where('user_id', $user->id)
            ->with('latestRecommendation')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findForUser(User $user, int $id): ?Biomarker
    {
        return Biomarker::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->with('latestRecommendation')
            ->first();
    }

    public function create(User $user, array $attributes): Biomarker
    {
        $attributes['user_id'] = $user->id;

        return Biomarker::query()->create($attributes);
    }

    public function update(Biomarker $biomarker, array $attributes): Biomarker
    {
        $biomarker->fill($attributes);
        $biomarker->save();

        return $biomarker->fresh();
    }

    public function delete(Biomarker $biomarker): bool
    {
        return (bool) $biomarker->delete();
    }
}
