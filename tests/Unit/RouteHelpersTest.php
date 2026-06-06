<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RouteHelpersTest extends TestCase
{
    // ── format_duration_route() ──────────────────────────────

    public function test_duration_hours_and_minutes(): void
    {
        $this->assertSame('3h 30min', format_duration_route(210));
    }

    public function test_duration_exact_hours(): void
    {
        $this->assertSame('4h', format_duration_route(240));
    }

    public function test_duration_minutes_only(): void
    {
        $this->assertSame('45min', format_duration_route(45));
    }

    public function test_duration_one_hour(): void
    {
        $this->assertSame('1h', format_duration_route(60));
    }

    public function test_duration_one_hour_and_five_min(): void
    {
        $this->assertSame('1h 5min', format_duration_route(65));
    }

    // ── format_price_fcfa() ──────────────────────────────────

    public function test_format_3500_fcfa(): void
    {
        $this->assertSame('3 500 FCFA', format_price_fcfa(3500.0));
    }

    public function test_format_zero_fcfa(): void
    {
        $this->assertSame('0 FCFA', format_price_fcfa(0.0));
    }

    public function test_format_large_amount(): void
    {
        $this->assertSame('15 000 FCFA', format_price_fcfa(15000.0));
    }

    // ── is_valid_schedule_status() ───────────────────────────

    public function test_scheduled_is_valid(): void
    {
        $this->assertTrue(is_valid_schedule_status('scheduled'));
    }

    public function test_boarding_is_valid(): void
    {
        $this->assertTrue(is_valid_schedule_status('boarding'));
    }

    public function test_departed_is_valid(): void
    {
        $this->assertTrue(is_valid_schedule_status('departed'));
    }

    public function test_cancelled_is_valid(): void
    {
        $this->assertTrue(is_valid_schedule_status('cancelled'));
    }

    public function test_completed_is_valid(): void
    {
        $this->assertTrue(is_valid_schedule_status('completed'));
    }

    public function test_unknown_status_is_invalid(): void
    {
        $this->assertFalse(is_valid_schedule_status('on_hold'));
    }

    // ── is_schedule_active() ─────────────────────────────────

    public function test_scheduled_is_active(): void
    {
        $this->assertTrue(is_schedule_active('scheduled'));
    }

    public function test_boarding_is_active(): void
    {
        $this->assertTrue(is_schedule_active('boarding'));
    }

    public function test_departed_not_active(): void
    {
        $this->assertFalse(is_schedule_active('departed'));
    }

    public function test_cancelled_not_active(): void
    {
        $this->assertFalse(is_schedule_active('cancelled'));
    }

    // ── parse_api_path() ─────────────────────────────────────

    public function test_parse_cities_path(): void
    {
        $result = parse_api_path('/cities');
        $this->assertSame('cities', $result['resource']);
        $this->assertNull($result['id']);
    }

    public function test_parse_schedules_with_id(): void
    {
        $result = parse_api_path('/schedules/42');
        $this->assertSame('schedules', $result['resource']);
        $this->assertSame(42, $result['id']);
    }

    public function test_parse_root_path(): void
    {
        $result = parse_api_path('/');
        $this->assertSame('', $result['resource']);
        $this->assertNull($result['id']);
    }

    public function test_parse_path_without_leading_slash(): void
    {
        $result = parse_api_path('routes');
        $this->assertSame('routes', $result['resource']);
        $this->assertNull($result['id']);
    }

    // ── calculate_arrival_time() ─────────────────────────────

    public function test_arrival_after_3h30(): void
    {
        $result = calculate_arrival_time('2026-06-06 06:00:00', 210);
        $this->assertSame('2026-06-06 09:30:00', $result);
    }

    public function test_arrival_crossing_midnight(): void
    {
        $result = calculate_arrival_time('2026-06-06 22:00:00', 180);
        $this->assertSame('2026-06-07 01:00:00', $result);
    }

    // ── build_schedule_filter() ──────────────────────────────

    public function test_filter_with_all_params(): void
    {
        $params = ['origin' => '1', 'destination' => '2', 'date' => '2026-06-10'];
        $filter = build_schedule_filter($params);
        $this->assertSame(1, $filter['origin']);
        $this->assertSame(2, $filter['destination']);
        $this->assertSame('2026-06-10', $filter['date']);
    }

    public function test_filter_ignores_empty_params(): void
    {
        $filter = build_schedule_filter(['origin' => '', 'destination' => '']);
        $this->assertArrayNotHasKey('origin', $filter);
        $this->assertArrayNotHasKey('destination', $filter);
    }

    public function test_filter_empty_params_returns_empty_array(): void
    {
        $filter = build_schedule_filter([]);
        $this->assertSame([], $filter);
    }
}
