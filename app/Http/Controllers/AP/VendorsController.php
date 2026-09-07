<?php

namespace App\Http\Controllers\AP;

use App\Http\Controllers\Controller;
use App\Models\AP\PurchaseInvoice;
use App\Models\AP\Vendor;
use Illuminate\Http\Request;

class VendorsController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'vendor.index');

        return to_json([
            'collection' => Vendor::with(['createdBy', 'updatedBy'])->filter(),
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'vendor.create');

        return to_json([
            'form' => [
                'name' => '',
                'code' => '',
                'email' => '',
                'mobile' => '',
                'fax' => '',
                'credit_days' => null,
                'address_line_1' => '',
                'address_line_2' => '',
                'address_line_3' => '',
                'city' => '',
                'state' => '',
                'country' => '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'vendor.create');

        $validated = $request->validate($this->rules());

        $item = Vendor::create($validated);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Vendor created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'vendor.show');

        $item = Vendor::with(['createdBy', 'updatedBy'])->findOrFail($id);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'vendor.update');

        $item = Vendor::findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'vendor.update');

        $validated = $request->validate($this->rules($id));

        $item = Vendor::findOrFail($id);
        $item->fill($validated);
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Vendor updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'vendor.delete');

        $item = Vendor::findOrFail($id);
        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Vendor deleted successfully',
        ]);
    }

    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';

        $vendors = Vendor::when($search, function ($query) use ($column, $search) {
            return $query->where($column, 'like', '%' . $search . '%');
        })->limit(25)->get();

        return to_json([
            'collection' => $vendors,
        ]);
    }


    public function getVendorBills($id){
        $bills = PurchaseInvoice::where('vendor_id', $id)->with('company')->orderByDesc('invoice_date')->filter();

        return to_json([
            'collection' => $bills,
        ]);
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:ap_vendors,code' . ($id ? ',' . $id : ''),
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'credit_days' => 'nullable|integer|min:0|max:999',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'address_line_3' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ];
    }
}
