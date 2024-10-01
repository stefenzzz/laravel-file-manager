<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $formFields = $request->validate([
            'name' => 'required|string|max:30|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:3|string|confirmed',
            'password_confirmation' => 'required|min:3|string'
        ]);

        $user =  User::create($formFields);

        Auth::login($user);


        $this->createInitialFile($user);

        $request->session()->regenerate();
        
        // send email for verification
        $user->sendEmailVerificationNotification();

        return redirect()->intended('myfiles');

    }
    public function login(Request $request)
    {
        
        $formFields = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email',$formFields['email'])->first();

        if($user && Hash::check($formFields['password'],$user->password) )
        {

            Auth::login($user);
            
            $request->session()->regenerate();

            return redirect()->intended('myfiles')->with('flash_message', 'You have successfully logged in.');
        }

        return back()->withErrors(['password' => 'Credentials are incorrect'])->onlyInput('password');
            
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerate();

        return redirect()->route('login');
    }

    public function createInitialFile(User $user )
    {
        // create root folder for newly registered user
        $folder = new File();
        $folder->name = $user->email;
        $folder->is_folder = 1;
        $folder->makeRoot()->save();
        
        // create Welcome.txt file
        $path = 'files/'.$user->id.'/Welcome.txt';
        Storage::disk('local')->put( $path ,'Welcome!');
        $name = pathinfo($path, PATHINFO_BASENAME);
        $size = Storage::disk('local')->size($path);

        /** @var Illuminate\Filesystem\FilesystemAdapter*/
        $adapter =  Storage::disk('local');
        $mimeType = $adapter->mimeType($path);

        // add Welcome.txt file storage path to database
        $file = new File();
        $file->is_folder = false;
        $file->storage_path = $path;
        $file->name = $name;
        $file->size = $size;
        $file->mime = $mimeType;
        $folder->appendNode($file);
        
    }
}
