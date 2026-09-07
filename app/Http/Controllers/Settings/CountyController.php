<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\County;
use App\Models\Settings\State;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CountyController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'county.index');
        $counties = County::with('state')->filter();

        return to_json([
            'collection' => $counties,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'county.create');
        
        $states = State::active()->orderBy('name')->get();
        
        $item = [
            'state_id' => '',
            'name' => '',
            'active' => true,
        ];

        return to_json([
            'form' => $item,
            'states' => $states,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'county.create');

        $request->validate([
            'state_id' => 'required|exists:state,id',
            'name' => 'required|string|max:255|unique:county,name,NULL,id,state_id,' . $request->state_id,
            'active' => 'boolean',
        ]);

        $item = County::create([
            'state_id' => $request->state_id,
            'name' => $request->name,
            'active' => $request->active ?? true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'County created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'county.show');
        $item = County::with('state')->findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'county.update');
        
        $item = County::with('state')->where('id', $id)->firstOrFail();

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'county.update');

        $request->validate([
            'state_id' => 'required|exists:state,id',
            'name' => 'required|string|max:255|unique:county,name,' . $id . ',id,state_id,' . $request->state_id,
            'active' => 'boolean',
        ]);

        $item = County::findOrFail($id);
        $item->state_id = $request->state_id;
        $item->name = $request->name;
        $item->active = $request->active ?? true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'County updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'county.delete');
        $item = County::findOrFail($id);

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'County deleted successfully'
        ]);
    }

    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';
        $stateId = request('state_id');
        
        $counties = County::with('state')
            ->when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%');
            })
            ->when($stateId, function ($query) use ($stateId) {
                return $query->where('state_id', $stateId);
            })
            ->get();
            
        return to_json([
            'collection' => $counties
        ]);
    }
}
