<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Upload\UploadDocument;
use App\Models\Upload\UploadFolder;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UploadPortalController extends Controller
{
    public function showLogin()
    {
        return view('upload-portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->with('role')->first();
        
        if (!$user) {
            return back()
                ->withInput()
                ->withErrors(['username' => 'Invalid credentials.']);
        }
        
        $user->makeVisible('password');
        
        if (Hash::check($request->password, $user->password)) {
            // Check if user has upload access
            if (!$user->only_upload && !$user->folder_access && !optional($user->role)->folder_access && !in_array(optional($user->role)->name, ['admin', 'superadmin'])) {
                return back()
                    ->withInput()
                    ->withErrors(['username' => 'You do not have access to the upload portal.']);
            }

            Auth::guard('upload-portal')->login($user, true);
            $request->session()->regenerate();
            $request->session()->put('upload_username', $user->username);
            $request->session()->save();
            
            return redirect()->route('upload-portal.index');
        }

        return back()
            ->withInput()
            ->withErrors(['username' => 'Invalid credentials.']);
    }

    public function index()
    {
        return view('upload-portal.index');
    }

    public function logout(Request $request)
    {
        Auth::guard('upload-portal')->logout();
        Auth::logout();
        return redirect()->route('upload-portal.login');
    }

    public function getUser(Request $request)
    {
        $user = Auth::guard('upload-portal')->user();
        
        if (!$user) {
            $user = Auth::user();
            if ($user) {
               Auth::guard('upload-portal')->login($user, true);
               $request->session()->regenerate();
               $request->session()->put('upload_username', $user->username);
               return redirect()->route('upload-portal.index');
            }
            info('User not found in session');
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        $user = User::with('role')->find($user->id);
        if ($user) {
            $user->role = Role::find($user->role_id);
            $user->companies_data = $user->companiesArray ? Company::whereIn('id', $user->companiesArray)->selectRaw('id, workgroup_id, CONCAT(store_number, " - ", name) as name')->get() : [];
        }

        $folderData = $this->getFolderData();
        if($folderData['success']){
            $folders = $folderData['folders'];
        } else {
           $folders = [];
        }
        $invoice_folders = collect($folders)->where('is_invoice', 1);
        $invoice_folder = $invoice_folders->first();
        return response()->json(['success' => true, 'user' => $user, 'invoice_folder' => $invoice_folder]);
    }


    public function getFolders()
    {
        $folderData = $this->getFolderData();
        if($folderData['success']){
            return response()->json(['success' => true, 'folders' => $folderData['folders']]);
        } else {
            return response()->json(['success' => false, 'message' => $folderData['message']]);
        }
    }
    public function getFolderData(){
        $user = Auth::guard('upload-portal')->user();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not authenticated'];
        }
        
        $user = User::with('role')->find($user->id);

        if(isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])){
            $folders = UploadFolder::where('hide', false)->get();
            foreach($folders as $folder){
                $folder->document_count = UploadDocument::where('folder_id', $folder->id)->count();
            }
        } else if(isset($user->folder_access) && $user->folder_access && count($user->folder_access) > 0){
            $folders = UploadFolder::where('hide', false)->whereIn('id', $user->folder_access)->selectRaw('id, name, is_invoice, is_check')->get();
            foreach($folders as $folder){
                $folder->document_count = UploadDocument::where('folder_id', $folder->id)
                    ->whereIn('company_id', $user->companiesArray)
                    ->count();
            }
        } else if(isset($user->role->folder_access) && $user->role->folder_access && count($user->role->folder_access) > 0){
            $folders = UploadFolder::where('hide', false)->whereIn('id', $user->role->folder_access)->selectRaw('id, name, is_invoice, is_check')->get();
            foreach($folders as $folder){
                $folder->document_count = UploadDocument::where('folder_id', $folder->id)
                    ->whereIn('company_id', $user->companiesArray)
                    ->count();
            }
        } else {
            $folders = [];
        }

        return ['success' => true, 'folders' => $folders];
    }

    public function getFolder($folderId)
    {
        $folder = UploadFolder::where('hide', false)->findOrFail($folderId);
        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        return response()->json(['success' => true, 'folder' => $folder]);
    }

    public function getDashboardStats(Request $request)
    {
        $user = Auth::guard('upload-portal')->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        $user = User::with('role')->find($user->id);
        
        if(isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])){
            $folderCount = UploadFolder::where('hide', false)->count();
            $documents = UploadDocument::query();
        } else if(isset($user->folder_access) && $user->folder_access && count($user->folder_access) > 0){
            $folderCount = UploadFolder::where('hide', false)->whereIn('id', $user->folder_access)->count();
            $documents = UploadDocument::whereIn('company_id', $user->companiesArray);
        } else if(isset($user->role->folder_access) && $user->role->folder_access && count($user->role->folder_access) > 0){
            $folderCount = UploadFolder::where('hide', false)->whereIn('id', $user->role->folder_access)->count();
            $documents = UploadDocument::whereIn('company_id', $user->companiesArray);
        } else {
            $folderCount = 0;
            $documents = UploadDocument::whereRaw('1 = 0'); // No results
        }

        $stats = [
            'folder_count' => $folderCount,
            'total_documents' => $documents->count(),
            'recent_uploads' => $documents->where('created_at', '>=', now()->subDays(15))->count(),
        ];

        return response()->json(['success' => true, 'stats' => $stats]);
    }
}
