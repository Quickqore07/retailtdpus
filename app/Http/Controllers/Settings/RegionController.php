<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\Region;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RegionController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'region.index');
        $regions = Region::with(['state'])->filter();

        return to_json([
            'collection' => $regions,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'region.create');
        $item = [
            'name' => '',
            'state_id' => null,
            'state' => null,
            'active' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'region.create');

        $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|integer|exists:state,id',
            'active' => 'boolean',
        ]);

        $item = Region::create([
            'name' => $request->name,
            'state_id' => $request->state_id,
            'active' => $request->active ?? true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Region created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'region.show');
        $item = Region::with(['state'])->findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'region.update');
        $item = Region::with(['state'])->findOrFail($id);

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'region.update');

        $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|integer|exists:state,id',
            'active' => 'boolean',
        ]);

        $item = Region::findOrFail($id);
        $item->name = $request->name;
        $item->state_id = $request->state_id;
        $item->active = $request->active ?? true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Region updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'region.delete');
        $item = Region::findOrFail($id);
        
        // Check if region has areas or companies
        if ($item->areas()->count() > 0 || $item->companies()->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete region with associated areas or companies'
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Region deleted successfully'
        ]);
    }

    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';
        $state_id = request('state_id');
        $regions = Region::with(['state'])
            ->when($state_id, function ($query) use ($state_id) {
                return $query->where('state_id', $state_id);
            })
            ->when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%');
            })->with(['state'])
            ->get();
        return to_json([
            'collection' => $regions
        ]);
    }
}

