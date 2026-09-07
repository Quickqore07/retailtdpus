<?php

namespace App\Http\Controllers\AP;

use App\Http\Controllers\Controller;
use App\Models\AP\ExpenseType;
use App\Models\AP\PurchaseInvoice;
use Illuminate\Http\Request;

class ExpenseTypesController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'expense-type.index');

        return to_json([
            'collection' => ExpenseType::with(['createdBy', 'updatedBy'])->filter(),
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'expense-type.create');

        return to_json([
            'form' => $this->defaultForm(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'expense-type.create');

        $validated = $request->validate($this->rules());

        $item = ExpenseType::create($validated);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Expense type created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'expense-type.show');

        $item = ExpenseType::with(['createdBy', 'updatedBy'])->findOrFail($id);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'expense-type.update');

        $item = ExpenseType::findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'expense-type.update');

        $validated = $request->validate($this->rules());

        $item = ExpenseType::findOrFail($id);
        $item->fill($validated);
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Expense type updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'expense-type.delete');

        $item = ExpenseType::findOrFail($id);

        if (PurchaseInvoice::where('expense_id', $id)->exists()) {
            return to_json([
                'deleted' => false,
                'message' => 'Expense type is used by purchase invoices and cannot be deleted.',
            ], 422);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Expense type deleted successfully',
        ]);
    }

    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';

        $items = ExpenseType::query()
            ->where('active', true)
            ->when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->limit(25)
            ->get();

        return to_json([
            'collection' => $items,
        ]);
    }

    private function defaultForm(): array
    {
        return [
            'name' => '',
            'amount_label' => 'Amount',
            'other_amount_label' => 'Other Amount',
            'show_other_amount' => true,
            'active' => true,
        ];
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'amount_label' => 'required|string|max:255',
            'other_amount_label' => 'required|string|max:255',
            'show_other_amount' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
