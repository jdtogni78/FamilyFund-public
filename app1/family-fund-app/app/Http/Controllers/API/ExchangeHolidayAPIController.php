<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AuthorizesApiAccess;
use App\Repositories\ExchangeHolidayRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExchangeHolidayAPIController extends Controller
{
    use AuthorizesApiAccess;

    public function __construct(
        private ExchangeHolidayRepository $repository
    ) {}

    /**
     * GET /api/exchange_holidays/{exchange}/{year}
     */
    public function index(string $exchange, int $year): JsonResponse
    {
        $holidays = $this->repository->getHolidays($exchange, $year);

        $data = $holidays->mapWithKeys(function ($holiday) {
            return [
                $holiday->holiday_date->format('Y-m-d') => [
                    'name' => $holiday->holiday_name,
                    'early_close' => $holiday->early_close_time?->format('H:i'),
                ]
            ];
        });

        return response()->json([
            'exchange' => $exchange,
            'year' => $year,
            'holidays' => $data,
        ]);
    }

    /**
     * POST /api/exchange_holidays/sync
     */
    public function sync(Request $request): JsonResponse
    {
        // Reference-data write: system-admin-only (#82, flag-gated).
        $this->requireAdminWrite();

        // Trigger sync job (to be implemented)
        return response()->json(['status' => 'sync_initiated']);
    }

    /**
     * GET /api/exchange_holidays/status
     */
    public function status(): JsonResponse
    {
        // Return last sync status (to be implemented)
        return response()->json(['status' => 'ok']);
    }
}
