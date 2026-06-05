<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour includes/helpers.php
 */
class HelpersTest extends TestCase
{
    // ── format_money() ──────────────────────────────────────

    public function test_format_money_basic(): void
    {
        $this->assertSame('3 500 FCFA', format_money(3500.0));
    }

    public function test_format_money_thousands(): void
    {
        $this->assertSame('10 000 FCFA', format_money(10000.0));
    }

    public function test_format_money_zero(): void
    {
        $this->assertSame('0 FCFA', format_money(0.0));
    }

    // ── format_duration() ───────────────────────────────────

    public function test_format_duration_hours_and_minutes(): void
    {
        $this->assertSame('3h 30min', format_duration(210));
    }

    public function test_format_duration_exact_hours(): void
    {
        $this->assertSame('4h', format_duration(240));
    }

    public function test_format_duration_minutes_only(): void
    {
        $this->assertSame('45min', format_duration(45));
    }

    public function test_format_duration_one_hour_and_minutes(): void
    {
        $this->assertSame('1h 5min', format_duration(65));
    }

    // ── validate_phone_cm() ─────────────────────────────────

    public function test_valid_mtn_phone(): void
    {
        $this->assertTrue(validate_phone_cm('677123456'));
    }

    public function test_valid_orange_phone_with_spaces(): void
    {
        $this->assertTrue(validate_phone_cm('699 123 456'));
    }

    public function test_valid_phone_with_237_prefix(): void
    {
        $this->assertTrue(validate_phone_cm('+237677123456'));
    }

    public function test_invalid_phone_too_short(): void
    {
        $this->assertFalse(validate_phone_cm('677123'));
    }

    public function test_invalid_phone_starts_with_5(): void
    {
        $this->assertFalse(validate_phone_cm('577123456'));
    }

    public function test_invalid_phone_empty(): void
    {
        $this->assertFalse(validate_phone_cm(''));
    }

    // ── validate_password() ─────────────────────────────────

    public function test_strong_password_passes(): void
    {
        $this->assertTrue(validate_password('Admin@1234'));
    }

    public function test_password_too_short_fails(): void
    {
        $this->assertFalse(validate_password('Ab1'));
    }

    public function test_password_no_uppercase_fails(): void
    {
        $this->assertFalse(validate_password('admin1234'));
    }

    public function test_password_no_lowercase_fails(): void
    {
        $this->assertFalse(validate_password('ADMIN1234'));
    }

    public function test_password_no_digit_fails(): void
    {
        $this->assertFalse(validate_password('AdminPass'));
    }

    // ── generate_booking_ref() ──────────────────────────────

    public function test_booking_ref_format(): void
    {
        $ref = generate_booking_ref();
        $this->assertMatchesRegularExpression('/^ICY-\d{4}-[A-F0-9]{6}$/', $ref);
    }

    public function test_booking_refs_are_unique(): void
    {
        $refs = array_map(fn() => generate_booking_ref(), range(1, 10));
        $this->assertCount(10, array_unique($refs));
    }

    // ── calculate_arrival() ─────────────────────────────────

    public function test_arrival_3h30_after_departure(): void
    {
        $result = calculate_arrival('2026-06-06 06:00:00', 210);
        $this->assertSame('2026-06-06 09:30:00', $result);
    }

    public function test_arrival_crossing_midnight(): void
    {
        $result = calculate_arrival('2026-06-06 20:00:00', 480);
        $this->assertSame('2026-06-07 04:00:00', $result);
    }

    // ── seats_available() ───────────────────────────────────

    public function test_seats_available_basic(): void
    {
        $this->assertSame(65, seats_available(70, 5));
    }

    public function test_seats_available_full_bus(): void
    {
        $this->assertSame(0, seats_available(70, 70));
    }

    public function test_seats_available_never_negative(): void
    {
        $this->assertSame(0, seats_available(70, 75));
    }

    // ── is_bookable() ────────────────────────────────────────

    public function test_scheduled_with_seats_is_bookable(): void
    {
        $this->assertTrue(is_bookable('scheduled', 10));
    }

    public function test_boarding_with_seats_is_bookable(): void
    {
        $this->assertTrue(is_bookable('boarding', 1));
    }

    public function test_departed_is_not_bookable(): void
    {
        $this->assertFalse(is_bookable('departed', 30));
    }

    public function test_cancelled_is_not_bookable(): void
    {
        $this->assertFalse(is_bookable('cancelled', 50));
    }

    public function test_no_seats_is_not_bookable(): void
    {
        $this->assertFalse(is_bookable('scheduled', 0));
    }
}
