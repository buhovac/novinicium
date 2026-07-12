<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create($request->validated());

        // Success is announced client-side via the form's onSuccess + role="status"
        // region — no dependency on shared flash props from the kit middleware.
        return back();
    }
}
