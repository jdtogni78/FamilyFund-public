<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AuthorizesApiAccess;
use App\Models\PortfolioBalance;
use App\Models\PortfolioExt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Class PortfolioBalanceAPIController
 * @package App\Http\Controllers\API
 */
class PortfolioBalanceAPIController extends AppBaseController
{
    use AuthorizesApiAccess;

    /**
     * Bulk update portfolio balances with temporal stitching.
     *
     * POST /api/portfolio_balances_bulk_update
     * {
     *     "balances": [
     *         {"source": "MONARCH_FIDE_XXXX", "balance": 660249.93, "as_of": "2026-01-29"},
     *         {"source": "MONARCH_CHAR_XXXX", "balance": 275592.20, "as_of": "2026-01-29"}
     *     ]
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request)
    {
        // Bulk balance updates are a fund-management / trade-execution
        // operation; require full access to a fund (system admin bypasses).
        $this->requireFullAccessToAnyFund();

        $validator = Validator::make($request->all(), [
            'balances' => 'required|array|min:1',
            'balances.*.source' => 'required|string',
            'balances.*.balance' => 'required|numeric',
            'balances.*.as_of' => 'required|date'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $balances = $request->input('balances');
        $results = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($balances as $item) {
                $source = $item['source'];
                $balance = $item['balance'];
                $asOf = $item['as_of'];

                $portfolio = PortfolioExt::where('source', $source)->first();
                if (!$portfolio) {
                    $errors[] = "Portfolio not found: {$source}";
                    continue;
                }

                $result = $this->insertHistoricalBalance($portfolio, $balance, $asOf);
                $results[] = [
                    'source' => $source,
                    'balance' => $balance,
                    'as_of' => $asOf,
                    'action' => $result['action'],
                    'id' => $result['record']->id
                ];
            }

            if (!empty($errors)) {
                DB::rollBack();
                return $this->sendError(implode('; ', $errors), 422);
            }

            DB::commit();
            return $this->sendResponse($results, 'Portfolio balances updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Portfolio balance bulk update failed: " . $e->getMessage());
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Insert a historical balance with proper temporal stitching.
     *
     * Logic:
     * 1. If a record exists at the exact date with same value: no change
     * 2. If a record exists at the exact date with different value: error
     * 3. If a record spans this date with different value: split it
     * 4. If a future record exists with same value: extend backwards
     * 5. If a future record exists with different value: create with end_dt = future start
     * 6. Otherwise: create new record with end_dt = 9999-12-31
     *
     * @param PortfolioExt $portfolio
     * @param float $newBalance
     * @param string $timestamp
     * @return array ['action' => string, 'record' => PortfolioBalance]
     * @throws Exception
     */
    protected function insertHistoricalBalance(PortfolioExt $portfolio, float $newBalance, string $timestamp): array
    {
        $portfolioId = $portfolio->id;
        $date = date('Y-m-d', strtotime($timestamp));

        // Find existing record at this date
        $existing = PortfolioBalance::where('portfolio_id', $portfolioId)
            ->whereDate('start_dt', '<=', $date)
            ->whereDate('end_dt', '>=', $date)
            ->first();

        if ($existing) {
            $existingStartDate = $existing->start_dt->format('Y-m-d');

            // Exact date match
            if ($existingStartDate === $date) {
                if (abs($existing->balance - $newBalance) < 0.01) {
                    // Same value - no change needed
                    return ['action' => 'unchanged', 'record' => $existing];
                } else {
                    // Different value at exact date - error
                    throw new Exception("Balance record already exists for {$portfolio->source} on {$date} with different value");
                }
            }

            // Record spans this date with different value - split it
            if (abs($existing->balance - $newBalance) >= 0.01) {
                $oldEndDt = $existing->end_dt;
                $existing->end_dt = $date;
                $existing->save();

                // Create new record
                $newRecord = PortfolioBalance::create([
                    'portfolio_id' => $portfolioId,
                    'balance' => $newBalance,
                    'start_dt' => $date,
                    'end_dt' => $oldEndDt
                ]);

                return ['action' => 'split', 'record' => $newRecord];
            }

            // Same value - no change needed
            return ['action' => 'unchanged', 'record' => $existing];
        }

        // No existing record at this date - check for future records
        $futureRecord = PortfolioBalance::where('portfolio_id', $portfolioId)
            ->whereDate('start_dt', '>', $date)
            ->orderBy('start_dt', 'asc')
            ->first();

        if ($futureRecord) {
            if (abs($futureRecord->balance - $newBalance) < 0.01) {
                // Future record has same value - extend backwards
                $futureRecord->start_dt = $date;
                $futureRecord->save();
                return ['action' => 'extended', 'record' => $futureRecord];
            } else {
                // Future record has different value - create new record ending at future start
                $newRecord = PortfolioBalance::create([
                    'portfolio_id' => $portfolioId,
                    'balance' => $newBalance,
                    'start_dt' => $date,
                    'end_dt' => $futureRecord->start_dt
                ]);
                return ['action' => 'created', 'record' => $newRecord];
            }
        }

        // No existing or future record - create new open-ended record
        $newRecord = PortfolioBalance::create([
            'portfolio_id' => $portfolioId,
            'balance' => $newBalance,
            'start_dt' => $date,
            'end_dt' => '9999-12-31'
        ]);

        return ['action' => 'created', 'record' => $newRecord];
    }
}
