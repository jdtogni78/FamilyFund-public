<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Controller;
use App\Models\ExchangeHoliday;
use App\Repositories\ExchangeHolidayRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ExchangeHolidayController extends Controller
{
    public function __construct(
        private ExchangeHolidayRepository $repository
    ) {}

    /**
     * Display exchange holidays
     */
    public function index(Request $request): View
    {
        $exchange = $request->get('exchange', 'NYSE');
        $year = $request->get('year', now()->year);

        $holidays = $this->repository->getHolidays($exchange, $year);

        // Get available exchanges and years
        $exchanges = ExchangeHoliday::select('exchange_code')
            ->distinct()
            ->orderBy('exchange_code')
            ->pluck('exchange_code');

        $years = ExchangeHoliday::selectRaw('YEAR(holiday_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Get stats
        $totalHolidays = ExchangeHoliday::active()->count();
        $currentYearCount = ExchangeHoliday::active()
            ->whereYear('holiday_date', now()->year)
            ->count();

        return view('exchange_holidays.index', compact(
            'holidays',
            'exchange',
            'year',
            'exchanges',
            'years',
            'totalHolidays',
            'currentYearCount'
        ));
    }

    /**
     * Trigger holiday sync (placeholder for now)
     */
    public function sync(Request $request): RedirectResponse
    {
        // TODO: Implement actual sync logic
        // For now, just show a message

        return redirect()->route('exchange-holidays.index')
            ->with('success', 'Holiday sync initiated (manual seeding required for now)');
    }

    /**
     * Run seeder to populate holidays
     */
    public function seed(Request $request): RedirectResponse
    {
        try {
            \Artisan::call('db:seed', ['--class' => 'NYSEHolidaysSeeder']);

            $output = \Artisan::output();

            return redirect()->route('exchange-holidays.index')
                ->with('success', 'NYSE holidays seeded successfully');
        } catch (\Exception $e) {
            return redirect()->route('exchange-holidays.index')
                ->with('error', 'Failed to seed holidays: ' . $e->getMessage());
        }
    }
}
