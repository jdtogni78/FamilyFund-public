<?php

namespace App\Http\Controllers\Traits;

use App\Models\AssetExt;
use App\Models\Utils;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use Phpml\Regression\LeastSquares;

/**
 */
trait PerformanceTrait
{
    use VerboseTrait;
    protected $perfObject;

    public function createPerformanceArray($start, $asOf, $shares=null, $asset=null)
    {
        $yp = array();
        $yp['value']        = Utils::currency($value = $this->perfObject->valueAsOf($asOf));
        $yp['shares']       = Utils::shares($shares = $this->perfObject->sharesAsOf($asOf));
        $yp['share_value']  = Utils::currency($shares > 0 ? $value/$shares : 0);
        $yp['performance']  = Utils::percent($this->perfObject->periodPerformance($start, $asOf));
        return $yp;
    }

    public function createAssetPeformanceArray($start, $asOf, $all_shares, AssetExt $asset)
    {
        $value = 0;
        $assetShares = 0;
        $symbol = $asset->name;
        // Log::debug("createAssetPeformanceArray $symbol $asOf");
        try {
            // find asset price with latest timestamp before asOf
            foreach ($all_shares as $shares) {
                // Log::debug("shares['timestamp']: " . $shares['timestamp'] . " asOf: " . $asOf);
                if (substr($shares['timestamp'],0,10) <= $asOf) {
                    $assetShares = $shares['shares'];
                } else {
                    break;
                }
            }
            // Log::debug("assetShares: $assetShares");
            $ap = $asset->priceAsOf($asOf)->first();
            // Log::debug("ap: $ap");
            if (empty($ap)) {
                Log::debug("no price for $symbol at $asOf");
                $value = 0;
            } else {
                $value = $assetShares * $ap->price;
                // Log::debug("value: $value");
            }
        } catch (\Exception $e) {
            Log::error("while calculating $symbol $asOf: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
        $yp = array();
        $yp['value']        = Utils::currency($value);
        $yp['shares']       = Utils::shares($assetShares);
        if ($ap) {
            $yp['price']        = Utils::currency($ap->price);
        }
        return $yp;
    }

    public function createCashPeformanceArray($start, $asOf, $cash, $asset)
    {
        $value = 0;
        // find cash with latest timestamp before asOf
        foreach ($cash as $timestamp => $value_tmp) {
            if ($timestamp <= $asOf) {
                $value = $value_tmp;
            } else {
                break;
            }
        }
        $yp = array();
        $yp['value'] = Utils::currency($value);
        $yp['price'] = Utils::currency($value); // For chart compatibility
        return $yp;
    }

    public function prepAssetShares($asset, $asOf, $trans)
    {
        $shares = 0;
        $allShares = array();
        $symbol = $asset->name;
        try {
            foreach ($trans as $tran) {
                if ($tran['timestamp'] <= $asOf) {
                    $ap = $asset->priceAsOf($tran['timestamp'])->first();
                    if (empty($ap)) {
                        Log::warning("No prices for $symbol at " . $tran['timestamp']);
                        $allShares[] = [
                            'timestamp' => $tran['timestamp'],
                            'shares' => 0
                        ];
                        continue;
                    }
                    $shares += $tran['value'] / $ap->price;
                    $allShares[] = [
                        'timestamp' => $tran['timestamp'],
                        'shares' => $shares
                    ];
                    $this->debug($symbol . " value: " . $tran['value'] . " price: $ap->price >> shares: $shares at " . $tran['timestamp']);
                }
            }
        } catch (\Exception $e) {
            Log::error("cant find price for $symbol $asOf: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
        return $allShares;
    }

    public function prepCash($asOf, $trans)
    {
        $allValues = array();
        $total = 0;
        foreach ($trans as $tran) {
            if ($tran['timestamp'] <= $asOf) {
                $timestamp = substr($tran['timestamp'],0,10);
                $this->debug("cash value: " . $tran['value'] . " at " . $timestamp);
                $total += $tran['value'];
                $allValues[$timestamp] = $total;
            }
        }
        return $allValues;
    }

    public function createAssetMonthlyPerformanceResponse($asset, $asOf, $trans, $removeZeroes=false) {
        $shares = $this->prepAssetShares($asset, $asOf, $trans);
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createAssetPeformanceArray', $removeZeroes, $shares, $asset);
    }

    public function createCashMonthlyPerformanceResponse($asOf, $trans) {
        $shares = $this->prepCash($asOf, $trans);
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createCashPeformanceArray', true, $shares);
    }

    public function createMonthlyPerformanceResponse($asOf) {
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createPerformanceArray', true);
    }

    public function createMonthlyPerformanceResponseFor($asOf, $func, $removeZeroes=false, $shares=null, $asset=null)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $month = substr($asOf,5,2);
        $ym = [$year, $month];
        $monthStart = $year.'-'.sprintf("%02d", $month).'-01';

        if ($asOf != $monthStart) {
            $yp = $this->$func($monthStart, $asOf, $shares, $asset);
            $arr[$asOf] = $yp;
        }

        for (; Utils::yearMonthInt($ym) >= 202101; $ym = Utils::decreaseYearMonth($ym)) {
            $monthStart = $ym[0].'-'.sprintf("%02d", $ym[1]).'-01';
            $prevYM = Utils::decreaseYearMonth($ym);
            $prevMonthStart = $prevYM[0].'-'.sprintf("%02d", $prevYM[1]).'-01';
            $yp = $this->$func($prevMonthStart, $monthStart, $shares, $asset);
            $arr[$monthStart] = $yp;
        }

        if ($removeZeroes) {
            // NOTE: can only remove zeroes if all items on a graph also can...
            $ret = $this->removeEmptyStart($arr);
            $ret = array_reverse($ret, true);
            $ret = $this->removeEmptyStart($ret);
        } else {
            $ret = array_reverse($arr, true);;
        }
        $ret = $this->addValueChangeToArray($ret);
        return $ret;
    }

    public function createYearlyPerformanceResponse($asOf)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $yearStart = $year.'-01-01';

        if ($asOf != $yearStart) {
            $yp = $this->createPerformanceArray($yearStart, $asOf);
            $arr[$yearStart . ' to ' . $asOf] = $yp;
        }

        $tran = $this->perfObject->findOldestTransaction();
        $this->debug("tran: $tran");
        $this->debug("perfObject: $this->perfObject");
        if ($tran == null) {
            throw new \Exception("No transactions found");
        } else {
            $firstDate = $tran->timestamp->addDay()->format('Y-m-d');
            $firstYear = substr($firstDate, 0, 4);
        }

        $prevYearStart = ($year-1).'-01-01';
        for (; $year > $firstYear; $year--) {
            $yearStart = $year.'-01-01';
            $prevYearStart = ($year-1).'-01-01';
            $yp = $this->createPerformanceArray($prevYearStart, $yearStart);
            $arr[$prevYearStart . ' to ' . $yearStart] = $yp;
        }
        $arr[$prevYearStart . ' to ' . $firstDate] = $this->createPerformanceArray($prevYearStart, $firstDate);


        $ret = $this->removeEmptyStart($arr);
        $ret = array_reverse($ret);
        $ret = $this->removeEmptyStart($ret);
        $ret = $this->addValueChangeToArray($ret);
        return $ret;
    }

    /**
     * @param mixed $perfObject
     */
    public function setPerfObject($perfObject): void
    {
        $this->perfObject = $perfObject;
    }

    protected function removeEmptyStart(array $ret): array
    {
        $key1 = 'value';
        foreach ($ret as $key => $values) {
            // check if key exists
            if (array_key_exists($key1, $values)) {
                if ($values[$key1] == 0) {
                    unset($ret[$key]);
                } else {
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * Add value_change percentage to each period (compares total value, includes deposits/withdrawals)
     * This is different from 'performance' which uses share price growth (excludes deposits)
     */
    protected function addValueChangeToArray(array $arr): array
    {
        $prevValue = null;
        foreach ($arr as $period => &$data) {
            if (isset($data['value'])) {
                $currentValue = floatval(str_replace(['$', ','], '', $data['value']));
                if ($prevValue !== null && $prevValue != 0) {
                    $data['value_change'] = Utils::percent(($currentValue - $prevValue) / $prevValue);
                } else {
                    $data['value_change'] = 0;
                }
                $prevValue = $currentValue;
            }
        }
        return $arr;
    }

    public function createLinearRegressionResponse($monthly_performance, $asOf, $currentValue = null) {
        $samples = [];
        $targets = [];
        $firstDate = null;
        $firstValue = null;

        foreach ($monthly_performance as $month => $perf) {
            $samples[] = [strtotime($month)];
            $targets[] = $perf['value'];
            if ($firstDate === null) {
                $firstDate = $month;
                $firstValue = $perf['value'];
            }
        }

        if (count($samples) < 2) {
            return ['m' => 0, 'intercept' => 0, 'predictions' => [], 'comparison' => null];
        }

        $regression = new LeastSquares();
        $regression->train($samples, $targets);

        $linReg = [];
        $linReg['m'] = $regression->getCoefficients()[0];
        $linReg['intercept'] = $regression->getIntercept();

        // Future predictions (next 10 years)
        $year = substr($asOf, 0, 4);
        $predictions = [];
        for ($i = 0; $i < 10; $i++) {
            $year++;
            $when = $year . '-01-01';
            $predictions[$when] = Utils::currency($regression->predict([strtotime($when)]));
        }
        $linReg['predictions'] = $predictions;

        // Value Comparison: Starting vs Expected vs Current
        if ($currentValue !== null && $firstValue !== null) {
            $expectedValue = $regression->predict([strtotime($asOf)]);
            $diff = $currentValue - $expectedValue;

            $linReg['comparison'] = [
                'starting' => [
                    'date' => $firstDate,
                    'value' => $firstValue,
                    'yield_per_year' => $firstValue * 0.04,
                ],
                'expected' => [
                    'date' => $asOf,
                    'value' => $expectedValue,
                    'yield_per_year' => $expectedValue * 0.04,
                ],
                'current' => [
                    'date' => $asOf,
                    'value' => $currentValue,
                    'yield_per_year' => $currentValue * 0.04,
                ],
                'diff' => $diff,
                'is_ahead' => $diff >= 0,
            ];
        }

        return $linReg;
    }

}
