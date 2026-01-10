<?php

namespace Tests\Unit;

use App\Models\Utils;
use Tests\TestCase;

/**
 * Unit tests for Utils class
 */
class UtilsTest extends TestCase
{
    public function test_currency_rounds_to_two_decimals()
    {
        $this->assertEquals(100.00, Utils::currency(100.001));
        $this->assertEquals(100.13, Utils::currency(100.125)); // PHP banker's rounding
        $this->assertEquals(100.13, Utils::currency(100.126));
    }

    public function test_shares_floors_to_four_decimals()
    {
        $this->assertEquals(100.1234, Utils::shares(100.12345));
        $this->assertEquals(100.1234, Utils::shares(100.12349));
    }

    public function test_position_floors_to_eight_decimals()
    {
        $this->assertEquals(100.12345678, Utils::position(100.123456789));
    }

    public function test_percent_multiplies_by_100_and_rounds()
    {
        $this->assertEquals(10.00, Utils::percent(0.10));
        $this->assertEquals(50.55, Utils::percent(0.5055));
        $this->assertEquals(100.00, Utils::percent(1.0));
    }

    public function test_decrease_year_month_normal_month()
    {
        $result = Utils::decreaseYearMonth([2022, 6]);
        $this->assertEquals([2022, 5], $result);
    }

    public function test_decrease_year_month_january_rolls_to_previous_year()
    {
        $result = Utils::decreaseYearMonth([2022, 1]);
        $this->assertEquals([2021, 12], $result);
    }

    public function test_decrease_year_month_throws_on_invalid_month()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid month');
        Utils::decreaseYearMonth([2022, 13]);
    }

    public function test_decrease_year_month_throws_on_invalid_year_too_low()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid year');
        Utils::decreaseYearMonth([1969, 6]);
    }

    public function test_decrease_year_month_throws_on_invalid_year_too_high()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid year');
        Utils::decreaseYearMonth([2101, 6]);
    }

    public function test_year_month_int_converts_to_integer()
    {
        $this->assertEquals(202206, Utils::yearMonthInt([2022, 6]));
        $this->assertEquals(202112, Utils::yearMonthInt([2021, 12]));
        $this->assertEquals(202101, Utils::yearMonthInt([2021, 1]));
    }

    public function test_as_of_add_year_positive_offset()
    {
        $result = Utils::asOfAddYear('2022-06-15', 1);
        $this->assertEquals('2023-06-15', $result);
    }

    public function test_as_of_add_year_negative_offset()
    {
        $result = Utils::asOfAddYear('2022-06-15', -1);
        $this->assertEquals('2021-06-15', $result);
    }

    public function test_as_of_add_year_multiple_years()
    {
        $result = Utils::asOfAddYear('2022-06-15', 5);
        $this->assertEquals('2027-06-15', $result);
    }
}
