<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactOwnerNotification;
use App\Mail\ContactSenderConfirmation;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact');
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect()->route('contact')->with('success', true);
        }

        $validated = $request->validated();

        ContactSubmission::create($validated);

        Mail::to(config('app.email'))
            ->send(new ContactOwnerNotification($validated['name'], $validated['email'], $validated['message']));

        Mail::to($validated['email'])
            ->send(new ContactSenderConfirmation($validated['name'], $validated['email']));

        return redirect()->route('contact')->with('success', true);
    }
}
