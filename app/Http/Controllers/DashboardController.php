<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = $request->user()->id;
        $openDeals = Deal::where('owner_id', $userId)->whereNotIn('stage', [DealStage::Won, DealStage::Lost]);

        return view('dashboard', [
            'stats' => [
                'companies' => Company::where('owner_id', $userId)->count(),
                'contacts' => Contact::where('owner_id', $userId)->count(),
                'pipeline' => (clone $openDeals)->sum('value'),
                'openDeals' => (clone $openDeals)->count(),
                'acceptedQuotes' => Quote::where('owner_id', $userId)->where('status', 'accepted')->sum('total'),
            ],
            'deals' => Deal::with(['company', 'contact'])->where('owner_id', $userId)->latest()->limit(5)->get(),
            'quotes' => Quote::with('company')->where('owner_id', $userId)->latest()->limit(5)->get(),
            'stageTotals' => Deal::where('owner_id', $userId)->selectRaw('stage, COUNT(*) as count, SUM(value) as total')->groupBy('stage')->get()->keyBy(fn ($row) => $row->stage->value),
        ]);
    }
}
