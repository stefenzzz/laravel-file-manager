<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\FileController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;


// Route::get('/phpinfo',function(){
//     phpinfo();
// });

Route::middleware('guest')->group(function(){
    
    Route::inertia('/','Home')->name('home');
    Route::inertia('/login','Auth/Login')->name('login');
    Route::post('/login',[AuthController::class,'login']);
    Route::inertia('register','Auth/Register')->name('register');
    Route::post('/register',[AuthController::class,'register']);

});

Route::controller(FileController::class)->middleware('auth','verified')->group(function(){
    
    Route::inertia('/dashboard','Dashboard')->name('dashboard');
    Route::get('/myfiles/{folder?}','myFiles')->where('folder', '(.*)' )->name('myfiles');
    Route::post('/folder/create','createFolder')->name('folder.create');
    Route::post('/file','store')->name('file.store');
    Route::get('/file/download','download')->name('file.download');
    Route::get('/trash','showTrash')->name('trash');
    Route::delete('/file/trash', 'trash')->name('file.trash');
    Route::post('/file/restore','restore')->name('file.restore');
    Route::delete('/file/delete', 'delete')->name('file.delete'); 
    Route::get('/shared-with-me/{folder?}','sharedWithMe')->where('folder', '(.*)')->name('file.sharedWithMe');
    Route::get('/shared-by-me','sharedByMe')->name('file.sharedByMe');
    Route::post('/file/share','share')->name('file.share'); 
    Route::delete('file/unshare','unshare')->name('file.unshare');
    Route::get('/file/download-shared-with-me','downloadSharedWithMe')->name('file.downloadSharedWithMe');
    Route::post('/file/add-to-favourites','addToFavourites')->name('file.addToFavourites');

});



Route::middleware('auth')->group(function(){
    Route::inertia('/verify-email','Auth/VerifyEmail')->name('verify-email')->middleware('unverified');
    Route::post('/verify-email', [EmailController::class,'verifyEmail'] )->middleware('unverified');

    Route::post('/logout',[AuthController::class,'logout'])->name('logout');
    
    // when user click the email verifiation link that was sent to their email
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('myfiles')->with('flash_message','You have successfully verified your account.');
    })->name('verification.verify')->middleware('signed');
});
    

