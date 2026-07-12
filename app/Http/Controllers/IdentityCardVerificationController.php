<?php

namespace App\Http\Controllers;

use App\Models\WarehouseIdentityCard;
use Illuminate\View\View;

class IdentityCardVerificationController extends Controller
{
    public function show(string $token): View
    {
        $card = WarehouseIdentityCard::query()
            ->where('verification_token', $token)
            ->firstOrFail();

        return view('identity-cards.verify', compact('card'));
    }
}
