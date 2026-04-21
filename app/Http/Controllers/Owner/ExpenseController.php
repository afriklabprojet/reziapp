<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseService $expenseService,
    ) {
    }

    public function index(Request $request): View
    {
        $user     = $request->user();
        $expenses = $this->expenseService->getExpenses($user, $request->only(['category', 'residence_id', 'start_date', 'end_date']));
        $summary  = $this->expenseService->getSummary($user, $request->get('year'));
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.expenses.index', compact('expenses', 'summary', 'residences'));
    }

    public function create(Request $request): View
    {
        $residences = $request->user()->residences()->orderBy('name')->get();
        $categories = Expense::CATEGORIES;

        return view('owner.expenses.create', compact('residences', 'categories'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt_path')) {
            $data['receipt_path'] = $request->file('receipt_path')
                ->store('receipts/'.$request->user()->id, 'public');
        }

        $this->expenseService->create($request->user(), $data);

        return redirect()->route('owner.expenses.index')
            ->with('success', 'Dépense ajoutée avec succès.');
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);
        $residences = request()->user()->residences()->orderBy('name')->get();
        $categories = Expense::CATEGORIES;

        return view('owner.expenses.edit', compact('expense', 'residences', 'categories'));
    }

    public function update(StoreExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);
        $data = $request->validated();

        if ($request->hasFile('receipt_path')) {
            $data['receipt_path'] = $request->file('receipt_path')
                ->store('receipts/'.$request->user()->id, 'public');
        }

        $this->expenseService->update($expense, $data);

        return redirect()->route('owner.expenses.index')
            ->with('success', 'Dépense mise à jour.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);
        $this->expenseService->delete($expense);

        return redirect()->route('owner.expenses.index')
            ->with('success', 'Dépense supprimée.');
    }
}
