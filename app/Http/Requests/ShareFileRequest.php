<?php

namespace App\Http\Requests;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ShareFileRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(),[
            'all' => 'nullable|bool',
            'ids.*'=> Rule::exists(File::class, 'id')->where('created_by', Auth::id()),
            'email' => [
                'required',
                'email',
                Rule::exists(User::class,'email'),
                function($attribute, $value, $fail){
                    if($value === Auth::user()->email){
                        $fail('Can`t share files to self');
                    }
                }
            ]
        ]);
    }

    public function messages()
    {
        return [
            'email.exists' => 'Email :input is not registered in this website.'
        ];
    }
}
