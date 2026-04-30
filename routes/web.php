<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\WorksController;
use App\Mail\ContactOwnerNotification;
use App\Mail\ContactSenderConfirmation;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/works', WorksController::class)->name('works');
Route::get('/privacy', PrivacyController::class)->name('privacy');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,10');

if (app()->environment('local')) {
    Route::get('/dev/mail-preview/{type}', function (string $type) {
        $mailable = match ($type) {
            'owner' => new ContactOwnerNotification(
                senderName: 'Jane Smith',
                senderEmail: 'jane@example.com',
                senderMessage: "Hi Mauricio,\n\nI came across your portfolio and was really impressed by your work on the CRM platform. I'm looking for a senior full-stack developer to join our product team.\n\nWould love to have a chat if you're open to it!\n\nBest,\nJane",
            ),
            'sender' => new ContactSenderConfirmation(
                senderName: 'Jane Smith',
                senderEmail: 'jane@example.com',
            ),
            default => abort(404),
        };

        return $mailable->render();
    })->name('dev.mail-preview');
}
