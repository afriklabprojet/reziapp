<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseService
{
    public function getExpenses(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = Expense::forOwner($owner->id)->with('residence');

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        return $query->orderByDesc('expense_date')->paginate(20);
    }

    public function create(User $owner, array $data): Expense
    {
        $data['owner_id'] = $owner->id;

        return Expense::create($data);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense->fresh();
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    public function getSummary(User $owner, ?string $year = null): array
    {
        $year = $year ?? now()->year;

        $expenses = Expense::forOwner($owner->id)
            ->whereYear('expense_date', $year)
            ->get();

        $byCategory = [];
        foreach (Expense::CATEGORIES as $key => $label) {
            $byCategory[$key] = [
                'label'  => $label,
                'total'  => $expenses->where('category', $key)->sum('amount'),
                'count'  => $expenses->where('category', $key)->count(),
            ];
        }

        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $byMonth[$m] = $expenses->filter(fn ($e) => $e->expense_date->month === $m)->sum('amount');
        }

        return [
            'total'       => $expenses->sum('amount'),
            'count'       => $expenses->count(),
            'by_category' => $byCategory,
            'by_month'    => $byMonth,
            'year'        => $year,
        ];
    }
}
