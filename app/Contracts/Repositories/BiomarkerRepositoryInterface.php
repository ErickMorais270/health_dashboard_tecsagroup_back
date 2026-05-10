<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Biomarker;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface BiomarkerRepositoryInterface
{
    /**
     * @return Collection<int, Biomarker>
     */
    public function allForUser(User $user): Collection;

    public function findForUser(User $user, int $id): ?Biomarker;

    public function create(User $user, array $attributes): Biomarker;

    public function update(Biomarker $biomarker, array $attributes): Biomarker;

    public function delete(Biomarker $biomarker): bool;
}
