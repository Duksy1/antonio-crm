<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = $request->user()->id;

        $openDeals = fn (): Builder => Deal::query()->where('owner_id', $userId)->whereNotIn('stage', [DealStage::Won, DealStage::Lost]);
        $tasks = fn (): Builder => Activity::query()->ownedBy($userId)->tasks();
        $openTasks = fn (): Builder => $tasks()->whereNull('completed_at');

        $won = Deal::where('owner_id', $userId)->where('stage', DealStage::Won)->count();
        $lost = Deal::where('owner_id', $userId)->where('stage', DealStage::Lost)->count();
        $wonValue = (float) Deal::where('owner_id', $userId)->where('stage', DealStage::Won)->sum('value');

        return view('dashboard', [
            'stats' => [
                'companies' => Company::where('owner_id', $userId)->count(),
                'contacts' => Contact::where('owner_id', $userId)->count(),
                'pipeline' => (float) $openDeals()->sum('value'),
                'openDeals' => $openDeals()->count(),
                'forecast' => (float) $openDeals()->sum(DB::raw('value * probability / 100')),
                'winRate' => $won + $lost === 0 ? null : (int) round($won / ($won + $lost) * 100),
                'wonValue' => $wonValue,
                'wonThisMonth' => (float) Deal::where('owner_id', $userId)->where('stage', DealStage::Won)->where('updated_at', '>=', now()->startOfMonth())->sum('value'),
                'acceptedQuotes' => (float) Quote::where('owner_id', $userId)->where('status', QuoteStatus::Accepted)->sum('total'),
                'awaitingQuotes' => Quote::where('owner_id', $userId)->where('status', QuoteStatus::Sent)->count(),
                'awaitingQuotesValue' => (float) Quote::where('owner_id', $userId)->where('status', QuoteStatus::Sent)->sum('total'),
                'openTasks' => $openTasks()->count(),
                'overdueTasks' => $tasks()->overdue()->count(),
                'dueTodayTasks' => $openTasks()->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            ],
            'agenda' => $openTasks()->with(['company:id,name', 'contact:id,first_name,last_name', 'deal:id,title'])
                ->whereNotNull('due_at')
                ->orderBy('due_at')
                ->limit(6)
                ->get(),
            'timeline' => Activity::query()->ownedBy($userId)->with(['company:id,name', 'owner:id,name'])->latest()->limit(8)->get(),
            'deals' => Deal::with(['company', 'contact'])->where('owner_id', $userId)->latest()->limit(5)->get(),
            'quotes' => Quote::with('company')->where('owner_id', $userId)->latest()->limit(5)->get(),
            'stageTotals' => Deal::where('owner_id', $userId)->selectRaw('stage, COUNT(*) as count, SUM(value) as total')->groupBy('stage')->get()->keyBy(fn ($row) => $row->stage->value),
        ]);
    }
}
