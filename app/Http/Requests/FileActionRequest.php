<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FileActionRequest extends ParentIdBaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules() , [
            'all' => 'nullable|bool',
            'ids.*' => [
                Rule::exists(File::class, 'id'),
                function($attributes, $value, $fail){
                    $file = File::query()
                    ->leftJoin('file_shares', 'file_shares.file_id','files.id')
                    ->where('files.id', $value)
                    ->where(function(Builder $query){
                        $query->where('files.created_by', Auth::id())
                            ->orWhere('file_shares.user_id', Auth::id());
                    })->first();

                    if(!$file) $fail('ID '.$value.' can`t be download');
                }

            ],
        ]);
    }
}
