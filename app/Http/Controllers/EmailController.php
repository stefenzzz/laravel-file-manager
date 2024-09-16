<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function verifyEmail(Request $request)
    {

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/myfiles');
        }

        $request->user()->sendEmailVerificationNotification();

        return inertia('Auth/VerifyEmail', ['status' => true] );
    }
}
