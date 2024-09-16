<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory, HasCreatorAndUpdater, NodeTrait, SoftDeletes;


    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_id', 'id');
    }

    public function owner() : Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes){
                return $attributes['created_by'] == Auth::id() ? 'me' : $this->user->name; 
            }
        );
    }

    public function isRoot(): Bool
    {
        return $this->parent_id === null;
    }

    public function starred():HasOne
    {
        return $this->hasOne(StarredFile::class, 'file_id', 'id')->where('user_id', Auth::id());
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->parent) {
                return;
            }
            $model->path = ( !$model->parent->isRoot() ? $model->parent->path . '/' : '' ) . Str::slug($model->name);
        });


    }

    public function moveToTrash()
    {
        $this->deleted_at = Carbon::now();

        return $this->save();
    }

    
    public function deleteFile()
    {
        $this->deleteFilesFromStorage([$this]);
        $this->forceDelete();
    }

    public function deleteFilesFromStorage($files)
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->deleteFilesFromStorage($file->children);
            } else {
                Storage::delete($file->storage_path);
            }
        }
    }

    public function get_file_size()
    {
        
        $totalSize = $this->size;
        
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

        $power = $totalSize > 0 ? floor(log($totalSize, 1024)) : 0;

        return number_format($totalSize / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    public function getChildrenSize(Array|Collection $files){
        
        $totalSize = 0;
        foreach($files as $file){

            if($file->is_folder){
                $totalSize += $this->getChildrenSize($file->children);
            }else{
                $totalSize += $file->size;
            }
        }

        return $totalSize;
    }

    public static function getSharedWithMe()
    {
        return File::query()
            ->selectRaw('files.*, users.email as sharedByEmail, file_shares.id as fileShared_id')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->join('users','users.id','files.created_by')
            ->where('file_shares.user_id',Auth::id())
            ->orderBy('file_shares.created_at','desc')
            ->orderBy('files.id','desc');

    }
    public static function getSharedByMe()
    {
        return File::query()
            ->selectRaw('files.*, users.email as sharedToEmail, file_shares.id as fileShared_id')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->join('users','users.id','file_shares.user_id')
            ->where('files.created_by',Auth::id())
            ->orderBy('file_shares.created_at','desc')
            ->orderBy('files.id','desc');
    }
}
