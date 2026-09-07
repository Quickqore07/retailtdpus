<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\Area;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AreaController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'area.index');
        $areas = Area::with(['region.state'])->filter();

        return to_json([
            'collection' => $areas,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'area.create');
        $item = [
            'name' => '',
            'region_id' => null,
            'region' => null,
            'active' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'area.create');

        $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'required|integer|exists:region,id',
            'active' => 'boolean',
        ]);

        $item = Area::create([
            'name' => $request->name,
            'region_id' => $request->region_id,
            'active' => $request->active ?? true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Area created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'area.show');
        $item = Area::with(['region.state'])->findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'area.update');
        $item = Area::with(['region.state'])->findOrFail($id);

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'area.update');

        $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'required|integer|exists:region,id',
            'active' => 'boolean',
        ]);

        $item = Area::findOrFail($id);
        $item->name = $request->name;
        $item->region_id = $request->region_id;
        $item->active = $request->active ?? true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Area updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'area.delete');
        $item = Area::findOrFail($id);
        
        // Check if area has companies
        if ($item->companies()->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete area with associated companies'
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Area deleted successfully'
        ]);
    }

    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';
        $region_id = request('region_id');
        $state_id = request('state_id');
        $areas = Area::with(['region.state'])
            ->when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%');
            })
            ->when($region_id, function ($query) use ($region_id) {
                return $query->where('region_id', $region_id);
            })
            ->when($state_id, function ($query) use ($state_id) {
                return $query->whereHas('region', function ($query) use ($state_id) {
                    $query->where('state_id', $state_id);
                });
            })
            ->get();
        return to_json([
            'collection' => $areas
        ]);
    }
}

