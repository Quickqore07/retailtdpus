<?php

namespace App\Http\Controllers\Upload;

use App\Models\Upload\UploadFolder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Upload\UploadDocument;

class UploadFolderController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'upload-folder.index');
        $folders = UploadFolder::where('hide', false)->filter();

        return to_json([
            'collection' => $folders,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'upload-folder.create');
        $item = [
            'name' => '',
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'upload-folder.create');

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $item = UploadFolder::create([
            'name' => $request->name,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload Folder created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'upload-folder.show');
        $item = UploadFolder::findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'upload-folder.update');
        $item = UploadFolder::where('hide', false)->findOrFail($id);

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'upload-folder.update');

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $item = UploadFolder::where('hide', false)->findOrFail($id);
        $item->name = $request->name;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload Folder updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'upload-folder.delete');
        $item = UploadFolder::where('hide', false)->findOrFail($id);

        if(UploadDocument::where('folder_id', $id)->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete folder with associated documents'
            ]);
        }
        
        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Upload Folder deleted successfully'
        ]);
    }

    public function search()
    {
        $search = request('query');

        $folders = UploadFolder::where('hide', false)->when($search, function ($query) use ($search) {
            return $query->where('name', 'like', '%' . $search . '%');
        })
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

        return to_json([
            'collection' => $folders
        ]);
    }
}
