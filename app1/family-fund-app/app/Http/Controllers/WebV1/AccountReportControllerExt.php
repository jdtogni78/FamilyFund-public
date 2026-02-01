<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountReportController;
use App\Http\Requests\CreateAccountReportRequest;
use App\Http\Requests\UpdateAccountReportRequest;
use App\Jobs\SendAccountReport;
use App\Models\Account;
use App\Models\AccountReport;
use App\Models\AccountReportExt;
use App\Models\Fund;
use App\Repositories\AccountReportRepository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Response;

class AccountReportControllerExt extends AccountReportController
{
    public function __construct(AccountReportRepository $accountReportRepository)
    {
        parent::__construct($accountReportRepository);
    }

    public function index(Request $request)
    {
        // Get filter values
        $fundId = $request->get('fund_id');
        $accountId = $request->get('account_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get filter options first (fresh queries)
        $funds = Fund::query()->orderBy('name')->pluck('name', 'id');
        $accounts = Account::query()
            ->with('fund')
            ->orderBy('nickname')
            ->get()
            ->mapWithKeys(fn($a) => [$a->id => $a->nickname . ' (' . ($a->fund->name ?? 'No Fund') . ')']);

        // Build account reports query with filters
        $query = AccountReportExt::query()->with(['account.fund', 'account.user']);

        if ($fundId) {
            $query->whereHas('account', fn($q) => $q->where('fund_id', $fundId));
        }

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($dateFrom) {
            $query->where('as_of', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('as_of', '<=', $dateTo);
        }

        $accountReports = $query->orderBy('as_of', 'desc')->get();

        return view('account_reports.index', compact(
            'accountReports',
            'funds',
            'accounts',
            'fundId',
            'accountId',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create()
    {
        $api = [
            'typeMap' => AccountReportExt::$typeMap,
            'accounts' => Account::with('fund')
                ->orderBy('nickname')
                ->get()
                ->mapWithKeys(fn($a) => [$a->id => $a->nickname . ' (' . ($a->fund->name ?? 'No Fund') . ')']),
        ];
        return parent::create()->with('api', $api);
    }

    public function show($id)
    {
        $api = ['typeMap' => AccountReportExt::$typeMap];
        return parent::show($id)->with('api', $api);
    }

    public function store(CreateAccountReportRequest $request)
    {
        $input = $request->all();

        $accountReport = AccountReport::create($input);

        // Only send if not a template (9999-12-31)
        if ($accountReport->as_of->format('Y-m-d') !== '9999-12-31') {
            SendAccountReport::dispatch($accountReport);
            Flash::success('Report created and queued for sending.');
        } else {
            Flash::success('Template saved successfully.');
        }

        return redirect(route('accountReports.index'));
    }

    public function update($id, UpdateAccountReportRequest $request)
    {
        $AccountReport = $this->accountReportRepository->find($id);

        if (empty($AccountReport)) {
            Flash::error('Account Report not found');

            return redirect(route('accountReports.index'));
        }

        $accountReport = $this->accountReportRepository->update($request->all(), $id);
        // Only dispatch if not a template (9999-12-31)
        if ($accountReport->as_of->format('Y-m-d') !== '9999-12-31') {
            SendAccountReport::dispatch($accountReport);
            Flash::success('Report updated and queued for sending.');
        } else {
            Flash::success('Template updated successfully.');
        }

        return redirect(route('accountReports.index'));
    }
}
