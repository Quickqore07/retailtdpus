<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\State;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'state.index');
        $states = State::filter();

        return to_json([
            'collection' => $states,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'state.create');
        $item = [
            'name' => '',
            'active' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'state.create');

        $request->validate([
            'name' => 'required|string|max:255|unique:state,name',
            'active' => 'boolean',
        ]);

        $item = State::create([
            'name' => $request->name,
            'active' => $request->active ?? true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'State created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'state.show');
        $item = State::findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'state.update');
        $item = State::findOrFail($id);

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'state.update');

        $request->validate([
            'name' => 'required|string|max:255|unique:state,name,' . $id,
            'active' => 'boolean',
        ]);

        $item = State::findOrFail($id);
        $item->name = $request->name;
        $item->active = $request->active ?? true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'State updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'state.delete');
        $item = State::findOrFail($id);
        
        // Check if state has regions or companies
        if ($item->regions()->count() > 0 || $item->companies()->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete state with associated regions or companies'
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'State deleted successfully'
        ]);
    }
    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';
        $states = State::when($search, function ($query) use ($column, $search) {
            return $query->where($column, 'like', '%' . $search . '%');
        })->get();
        return to_json([
            'collection' => $states
        ]);
    }
}

