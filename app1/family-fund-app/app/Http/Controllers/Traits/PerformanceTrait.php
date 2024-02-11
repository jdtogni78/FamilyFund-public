<?php

namespace App\Http\Controllers\Traits;

use App\Models\AssetExt;
use App\Models\Utils;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;

/**
 */
trait PerformanceTrait
{
    protected $perfObject;
    private AssetExt $sp500Asset;

    public function createPeformanceArray($start, $asOf)
    {
        // print_r([$this->perfObject->id, $start, $asOf]);
        $yp = array();
        $yp['value']        = Utils::currency($value = $this->perfObject->valueAsOf($asOf));
        $yp['shares']       = Utils::shares($shares = $this->perfObject->sharesAsOf($asOf));
        $yp['share_value']  = Utils::currency($shares > 0 ? $value/$shares : 0);
        $yp['performance']  = Utils::percent($this->perfObject->periodPerformance($start, $asOf));
        return $yp;
    }

    public function prepSP500Shares($asOf, $trans)
    {
        $shares = 0;
        $allShares = array();
        try {
            $this->sp500Asset = AssetExt::getSP500Asset();
            foreach ($trans as $tran) {
                if ($tran['timestamp'] <= $asOf) {
                    $sp500Price = $this->sp500Asset->pricesAsOf($tran['timestamp'])->first();
                    $shares += $tran['value'] / $sp500Price->price;
                    $allShares[] = [
                        'timestamp' => $tran['timestamp'],
                        'shares' => $shares
                    ];
                    Log::debug("sp500 value: " . $tran['value'] . " price: $sp500Price->price >> shares: $shares at " . $tran['timestamp']);
                }
            }
        } catch (\Exception $e) {
            Log::error("cant find price for sp500 $asOf: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
        return $allShares;
    }

    public function createSP500PeformanceArray($start, $asOf, $all_shares)
    {
        $value = 0;
        $sp500Shares = 0;
        try {
            // find sp500Shares with latest timestamp before asOf
            foreach ($all_shares as $shares) {
                if ($shares['timestamp'] <= $asOf) {
                    $sp500Shares = $shares['shares'];
                } else {
                    break;
                }
            }
            $sp500Price = $this->sp500Asset->pricesAsOf($asOf)->first();
            $value = $sp500Shares * $sp500Price->price;
        } catch (\Exception $e) {
            Log::error("while calculating sp500 $asOf: " . $e->getMessage());
               Log::error($e->getTraceAsString());
        }
        $yp = array();
        $yp['value']        = Utils::currency($value);
        $yp['shares']       = Utils::shares($sp500Shares);
        return $yp;
    }

    public function createCashPeformanceArray($start, $asOf, $cash)
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
        return $yp;
    }

    public function createSP500MonthlyPerformanceResponse($asOf, $trans)
    {
        $shares = $this->prepSP500Shares($asOf, $trans);
        return $this->createMonthlyPerformanceResponseFor($asOf, $shares,
            'createSP500PeformanceArray');
    }

    public function prepCash($asOf, $trans)
    {
        $allValues = array();
        $total = 0;
        foreach ($trans as $tran) {
            if ($tran['timestamp'] <= $asOf) {
                $timestamp = substr("" . $tran['timestamp'],0,10);
                Log::debug("cash value: " . $tran['value'] . " at " . $timestamp);
                $total += $tran['value'];
                $allValues[$timestamp] = $total;
            }
        }
        return $allValues;
    }

    public function createCashPerformanceResponse($asOf, $trans)
    {
        $cash = $this->prepCash($asOf, $trans);
        return $this->createMonthlyPerformanceResponseFor($asOf, $cash, 'createCashPeformanceArray');
    }

    public function createMonthlyPerformanceResponse($asOf)
    {
        return $this->createMonthlyPerformanceResponseFor($asOf, null, 'createPeformanceArray');
    }

    public function createMonthlyPerformanceResponseFor($asOf, $shares, $func)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $month = substr($asOf,5,2);
        $ym = [$year, $month];
        $monthStart = $year.'-'.sprintf("%02d", $month).'-01';

        if ($asOf != $monthStart) {
            $yp = $this->$func($monthStart, $asOf, $shares);
            $arr[$asOf] = $yp;
        }

        for (; Utils::yearMonthInt($ym) >= 202101; $ym = Utils::decreaseYearMonth($ym)) {
            $monthStart = $ym[0].'-'.sprintf("%02d", $ym[1]).'-01';
            $prevYM = Utils::decreaseYearMonth($ym);
            $prevMonthStart = $prevYM[0].'-'.sprintf("%02d", $prevYM[1]).'-01';
            $yp = $this->$func($prevMonthStart, $monthStart, $shares);
            $arr[$monthStart] = $yp;
        }

        $ret = $this->removeEmptyStart($arr);
        $ret = array_reverse($ret, true);
        $ret = $this->removeEmptyStart($ret);
        return $ret;
    }

    public function createYearlyPerformanceResponse($asOf)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $yearStart = $year.'-01-01';

        if ($asOf != $yearStart) {
            $yp = $this->createPeformanceArray($yearStart, $asOf);
            $arr[$asOf] = $yp;
        }

        for (; $year >= 2021; $year--) {
            $yearStart = $year.'-01-01';
            $prevYearStart = ($year-1).'-01-01';
            $yp = $this->createPeformanceArray($prevYearStart, $yearStart);
            $arr[$yearStart] = $yp;
        }

        $ret = $this->removeEmptyStart($arr);
        $ret = array_reverse($ret);
        $ret = $this->removeEmptyStart($ret);
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
        foreach ($ret as $key => $values) {
            // check if key exists
            if (array_key_exists('shares', $values)) {
                if ($values['value'] == 0) {
                    unset($ret[$key]);
                } else {
                    break;
                }
            }
        }
        return $ret;
    }
}
