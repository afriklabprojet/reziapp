<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CleaningTask;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CleaningTaskService
{
    public function getTasks(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = CleaningTask::forOwner($owner->id)
            ->with(['residence', 'assignee', 'booking']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        return $query->orderByDesc('scheduled_date')->paginate(20);
    }

    public function create(User $owner, array $data): CleaningTask
    {
        $data['owner_id'] = $owner->id;
        $data['status']   = CleaningTask::STATUS_PENDING;

        // Default checklist if not provided
        if (empty($data['checklist'])) {
            $data['checklist'] = $this->getDefaultChecklist();
        }

        return CleaningTask::create($data);
    }

    public function update(CleaningTask $task, array $data): CleaningTask
    {
        $task->update($data);

        return $task->fresh();
    }

    public function markCompleted(CleaningTask $task): void
    {
        $task->markCompleted();
    }

    public function verify(CleaningTask $task): void
    {
        $task->verify();
    }

    public function delete(CleaningTask $task): void
    {
        $task->delete();
    }

    public function getUpcomingTasks(User $owner, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return CleaningTask::forOwner($owner->id)
            ->upcoming($days)
            ->with(['residence', 'assignee'])
            ->orderBy('scheduled_date')
            ->get();
    }

    private function getDefaultChecklist(): array
    {
        return [
            ['item' => 'Nettoyage salon et salle à manger', 'done' => false],
            ['item' => 'Nettoyage chambre(s)', 'done' => false],
            ['item' => 'Nettoyage salle(s) de bain', 'done' => false],
            ['item' => 'Nettoyage cuisine', 'done' => false],
            ['item' => 'Changement draps et serviettes', 'done' => false],
            ['item' => 'Aspiration/Balayage sols', 'done' => false],
            ['item' => 'Vérification équipements', 'done' => false],
            ['item' => 'Réapprovisionnement produits', 'done' => false],
        ];
    }
}
