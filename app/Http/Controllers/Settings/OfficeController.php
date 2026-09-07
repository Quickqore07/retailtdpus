<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\Office;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OfficeController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'office.index');
        $offices = Office::filter();

        return to_json([
            'collection' => $offices,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'office.create');
        $item = [
            'name' => '',
            'latitude' => '',
            'longitude' => '',
            'authorized_radius' => 300,
            'timezone' => Office::DEFAULT_TIMEZONE,
            'active' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'office.create');

        $request->validate([
            'name' => 'required|string|max:255|unique:office,name',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'authorized_radius' => 'required|integer|min:1|max:100000',
            'timezone' => 'required|timezone',
            'active' => 'boolean',
        ]);

        $item = Office::create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'authorized_radius' => $request->authorized_radius,
            'timezone' => $request->timezone,
            'active' => $request->active ?? true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Office created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'office.show');
        $item = Office::findOrFail($id);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'office.update');
        $item = Office::findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'office.update');

        $request->validate([
            'name' => 'required|string|max:255|unique:office,name,' . $id,
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'authorized_radius' => 'required|integer|min:1|max:100000',
            'timezone' => 'required|timezone',
            'active' => 'boolean',
        ]);

        $item = Office::findOrFail($id);
        $item->name = $request->name;
        $item->latitude = $request->latitude;
        $item->longitude = $request->longitude;
        $item->authorized_radius = $request->authorized_radius;
        $item->timezone = $request->timezone;
        $item->active = $request->active ?? true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Office updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'office.delete');
        $item = Office::findOrFail($id);

        if ($item->users()->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete office with associated users',
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Office deleted successfully',
        ]);
    }

    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';

        $offices = Office::query()
            ->when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%');
            })
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'authorized_radius', 'timezone']);

        return to_json([
            'collection' => $offices,
        ]);
    }
}
