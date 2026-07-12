<?php

namespace App\Http\Controllers;

use App\Models\WarehouseIdentityCard;
use App\Support\PaginationHelper;
use Illuminate\View\View;

class TraineeIdentityCardController extends Controller
{
    public function index(): View
    {
        $cards = auth()->user()
            ->warehouseIdentityCards()
            ->with('trainingApplication.course')
            ->where('status', WarehouseIdentityCard::STATUS_PUBLISHED)
            ->orderByDesc('published_at')
            ->paginate(PaginationHelper::PER_PAGE);

        return view('training.identity-cards', compact('cards'));
    }
}
