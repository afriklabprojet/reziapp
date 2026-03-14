<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id'  => ['required', 'integer', 'exists:residences,id'],
            'category'      => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'description'   => ['required', 'string', 'max:500'],
            'amount'        => ['required', 'numeric', 'min:0'],
            'expense_date'  => ['required', 'date'],
            'receipt_path'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'is_recurring'  => ['boolean'],
            'frequency'     => ['required_if:is_recurring,true', 'nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required' => 'Sélectionnez une résidence.',
            'category.required'     => 'La catégorie est obligatoire.',
            'description.required'  => 'La description est obligatoire.',
            'amount.required'       => 'Le montant est obligatoire.',
            'amount.min'            => 'Le montant ne peut pas être négatif.',
            'expense_date.required' => 'La date de la dépense est obligatoire.',
        ];
    }
}
