<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'created_at',
        'updated_at'
    ];


    public function sharedTo()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
