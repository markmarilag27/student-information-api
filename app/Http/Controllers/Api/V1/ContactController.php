<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ContactCreateFormRequest;
use App\Http\Requests\V1\ContactUpdateFormRequest;
use App\Http\Resources\V1\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function store(ContactCreateFormRequest $request): JsonResponse
    {
        /** @var Contact $contact */
        $contact = DB::transaction(function () use ($request) {
            return Contact::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ]);
        });

        return response()->json([
            'data' => new ContactResource($contact),
        ]);
    }

    public function show(Contact $contact): JsonResponse
    {
        return response()->json([
            'data' => new ContactResource($contact),
        ]);
    }

    public function update(ContactUpdateFormRequest $request, Contact $contact): JsonResponse
    {
        $contact = DB::transaction(function () use ($request, $contact) {
            $contact->name = $request->input('name');
            $contact->email = $request->input('email');
            $contact->phone = $request->input('phone');
            $contact->save();

            return $contact;
        });

        return response()->json([
            'data' => new ContactResource($contact),
        ]);
    }

    public function destroy(Contact $contact): Response
    {
        DB::transaction(function () use ($contact) {
            $contact->delete();
        });

        return response()->noContent();
    }
}
