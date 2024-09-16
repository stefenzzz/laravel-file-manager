<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToFavouritesRequest;
use App\Http\Requests\FileActionRequest;
use App\Http\Requests\ShareFileRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\TrashFileRequest;
use App\Http\Resources\FileResource;
use App\Mail\ShareFilesMail;
use App\Models\File;
use App\Models\FileShare;
use App\Models\StarredFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use ZipArchive;

class FileController extends Controller
{
    //


    public function myFiles(Request $request, string $folder = null)
    {      

        if ($folder) {
            $folder = File::query()
                ->where('created_by', Auth::id())
                ->where('path', $folder)
                ->firstOrFail();
        }
        if (!$folder) {
            $folder = $this->getRoot();
        }

        $query = File::query()
                ->select('files.*')
                ->with('starred')          
                ->whereNot('_lft',1)
                ->where('files.created_by', Auth::id())
                ->orderBy('files.is_folder', 'desc')
                ->orderBy('files.created_at', 'desc');

        if($search = $request->search){
            $query->where('name','like','%'.$search.'%');
        }else{
            $query->where('files.parent_id', $folder->id);
        }
        
        $favourites = (int)$request->favourites;

        if($favourites){
            $query->join('starred_files','starred_files.file_id','files.id')
                    ->where('starred_files.user_id',Auth::id())
                    ->orWhereNotNull('files.parent_id');
        }

        $files = FileResource::collection($query->paginate(10));   

        if($request->wantsJson()){
            return $files;
        }
        
        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);

        $folder = new FileResource($folder);

        return inertia('MyFiles', compact('files','folder','ancestors'));
    }

    public function createFolder(StoreFolderRequest $request)
    {
        $data = $request->validated();
        
        $parent = $request->parent ?? $this->getRoot();
        
        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);
    }

    private function getRoot(): File
    {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();
    }


    public function store(StoreFileRequest $request){

        $data = $request->validated();
        
        $parent = $request->parent ?? $this->getRoot();
        $user = $request->user();
        $fileTree = $request->file_tree;

        if (!empty($fileTree)) {
            
            $this->saveFileTree($fileTree, $user, $parent);
            
        } else {
            foreach ($data['files'] as $file) {

                $this->saveFile($file, $user, $parent);

            }
        }
    }

    private function saveFile(UploadedFile $file,User $user, File $parent)
    {               
        $model = new File();              
        $model->storage_path = $file->store('/files/' . $user->id, 'local');
        $model->is_folder = false;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getMimeType();
        $model->size = $file->getSize();
        
        $parent->appendNode($model);
    }
    
    public function saveFileTree(Array $fileTree, User $user, File $parent)
    {
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;             
                $parent->appendNode($folder);
                
                $this->saveFileTree($file, $user, $folder);
            } else {

                $this->saveFile($file, $user, $parent);
            }
        }
    }


    public function trash(FileActionRequest $request)
    {
        
        $data = $request->validated();
        $parent = $request->parent;

        
        if ($data['all']) {
            
            foreach ($parent->children as $child) {
              
                $child->moveToTrash();
            }
        } else {
            foreach ($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if ($file) {
                    
                    $file->moveToTrash();
                }
            }
        }
        
        return to_route('myfiles', ['folder' => $parent->path]);
    }


    public function download(FileActionRequest $request){

        $data = $request->validated();
        $parent = $request->parent;
    
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if(!$all && empty($ids)){
            return ['message' => 'Please select file to download'];
        }

        if($all){
            
            if($parent->children->isEmpty()){
                return [ 'message' => 'No files to donwload' ];
            }
            $url = $this->createZip($parent->children);
            $filename = $parent->name.'.zip';
        }else{
           $result = $this->getDownloadUrl( $ids , $parent->name);

           if(isset($result['message'])) {
             return $result;
           }

           [$url, $filename] = $result;
        }

        return [ 'url' => $url, 'filename' => $filename ];
    }

    public function downloadSharedWithMe(FileActionRequest $request)
    {
        $data = $request->validated();
        $filename = 'shared-with-me.zip';
    
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if(!$all && empty($ids)){
            return ['message' => 'Please select file to download'];
        }

        if($all){

            $files = File::getSharedWithMe()->get();
            
            $url = $this->createZip($files);

        }else{
           $result = $this->getDownloadUrl( $ids , $filename);

           if(isset($result['message'])) {
             return $result;
           }

           [$url, $filename] = $result;
        }

        return [ 'url' => $url, 'filename' => $filename ];
    }
    

    private function getDownloadUrl(Array $ids, String $filename)
    {
        if(count($ids) === 1)
        {
            $file = File::find($ids[0]);
            if($file->is_folder)
            {        
                if($file->children->isEmpty()){
                    return [ 'message' => 'Folder is empty' ];
                }
                $url = $this->createZip($file->children);
                $filename = $file->name.'.zip';
            }else{
    
                $dest = pathinfo($file->storage_path,PATHINFO_BASENAME);
                $content = Storage::disk('local')->get($file->storage_path);

                // transfer file from local storage to public
                
                /** @var \Illuminate\Filesystem\FilesystemAdapter $publicStorage */
                $publicStorage = Storage::disk('public');
                $success = $publicStorage->put($dest, $content);
                $url = asset($publicStorage->url($dest));
                $filename = $file->name;
            }
        }else{
            
            $files = File::query()->whereIn('id', $ids)->get();
            $url = $this->createZip($files);
            $filename = $filename.'.zip';
        }
        return [$url,$filename];
    
    }

    public function createZip(Array|Collection $files): String
    {
        $zipPath = 'zip/'. Str::random() . '.zip';

        // create the zip folder if didn't exists yet
        if(!Storage::disk('public')->exists(dirname($zipPath))){    
            Storage::disk('public')->makeDirectory(dirname($zipPath));
        }

        // get the zip filesystem path to open the zip for ZipArchive
        $zipFile = Storage::disk('public')->path($zipPath);

        $zip = new ZipArchive();
        if( $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true ){
            $this->addFilesToZip($zip, $files);
        }

        $zip->close();

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicStorage */
        $publicStorage = Storage::disk('public');
        return asset( $publicStorage->url($zipPath) );
    }

    private function addFilesToZip($zip, $files, $ancestors = '')
    {
        foreach($files as $file)
        {
            if($file->is_folder){
                $this->addFilesToZip($zip, $file->children, $ancestors.$file->name. '/');
            }else{
                $localPath = Storage::disk('local')->path($file->storage_path);
                $zip->addFile($localPath, $ancestors.$file->name);
            }
        }

    }

    public function showTrash(Request $request)
    {   
        $query = File::onlyTrashed()
                ->where('created_by',Auth::id())
                ->orderBy('is_folder', 'desc')
                ->orderBy('deleted_at', 'desc')
                ->orderBy('files.id','desc');
        $files = $query->paginate(10);

        $files = FileResource::collection($files);

        if($request->wantsJson()){
            return $files;
        }

        return inertia('Trash',compact('files'));
    }

    public function restore(TrashFileRequest $restore)
    {
        $data = $restore->validated();

        if($data['all']){
            
            $files = File::onlyTrashed()->get();
            foreach($files as $file){
                $file->restore();
            }

        }else{
            $ids = $data['ids'] ?? [];
            $files = File::onlyTrashed()->whereIn('id',$ids)->get();
            foreach( $files as $file){
                $file->restore();
            }
        }
    }

    public function delete(TrashFileRequest $request)
    {
        $data = $request->validated();
        
        if($data['all']){
            $files = File::onlyTrashed()->get();
            foreach($files as $file){
                $file->deleteFile();
            }
        }else{
            $ids = $data['ids'] ?? [];
            $files = File::onlyTrashed()->whereIn('id',$ids)->get();
            foreach($files as $file){
                $file->deleteFile();
            }
        }
    }

    public function share(ShareFileRequest $request)
    {   
        $data = $request->validated();
        $all = $data['all'] ? true : false;
        $ids = $data['ids'] ?? [];   
        $email = $data['email'];
        $parent = $request->parent;


        if(!$all && empty($ids)){
            redirect()->back()->with('error_message', 'Please select at least one file to share' );
        }

        $user = User::query()->where('email', $email)->first();

        if(!$user) return [ 'message' => 'user cant be found'];

        if($all){
            $files = $parent->children;
            if($files->isEmpty()) return;
        }else{
            $files = File::query()->whereIn('id',$ids)->get();
            
        }   

        $data = [];
        $ids = Arr::pluck($files, 'id');
        $existingFields = FileShare::query()
                        ->whereIn('file_id', $ids)
                        ->where('user_id',$user->id)
                        ->get()
                        ->keyBy('file_id');
        
        foreach($files as $file){
            if($existingFields->has($file->id)){
                continue;
            }
            $data[] = [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
        }
        
        if(empty($data)) return redirect()->back()->with('error_message','File(s) already shared to '. $email.'.');

        FileShare::insert($data);
        
        Mail::to($user)->send(new ShareFilesMail( $user, Auth::user(), $files));

        return redirect()->back();
    }
    public function unshare(Request $request)
    {
        $all = $request->all ?? false;
        $ids = $request->ids ?? [];

        if($all){
            FileShare::selectRaw('file_shares.*, files.created_by, files.name')
                        ->join('files','files.id','=','file_shares.file_id')
                        ->where('files.created_by', Auth::id())->delete();
        }else{

            FileShare::selectRaw('file_shares.*, files.created_by, files.name')
                        ->join('files','files.id','=','file_shares.file_id')
                        ->whereIn('file_shares.id', $ids)
                        ->where('files.created_by', Auth::id())->delete();
            
        }

    }

    public function sharedWithMe(Request $request, string $folder = null)
    {
        $query = File::getSharedWithMe();

        $files = $query->paginate(10);

        if($request->wantsJson())
        {
            return $files;
        }

        return inertia('SharedWithMe', compact('files'));
    }
    public function sharedByMe(Request $request)
    {

        $query = File::getSharedByMe();

        $files = $query->paginate(10);
        
        if($request->wantsJson())
        {
            return $files;
        }

        return inertia('SharedByMe',compact('files'));
    }

    public function addToFavourites(AddToFavouritesRequest $request)
    {
        $data = $request->validated();
        $id = $data['id'];

        $file = File::query()->where('id', $id)->first();
        
        if(!$file) return ['message' => 'file cant be found'];

        $starredFile = StarredFile::query()
                        ->where('file_id', $file->id)
                        ->where('user_id', Auth::id())
                        ->first();
        if($starredFile){
            $starredFile->delete();
        }else{
            StarredFile::create([
                'file_id' => $file->id,
                'user_id' => Auth::id(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return;

    }

}
