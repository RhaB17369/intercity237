<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TicketHelpersTest extends TestCase
{
    // ── build_qr_data() ──────────────────────────────────────

    public function test_build_qr_data_returns_valid_json(): void
    {
        $json = build_qr_data('I237-ABC123', 'Jean Paul', 'Yaoundé', 'Douala', '2026-06-06 06:00:00', 'abc123token');
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertSame('I237-ABC123', $data['ref']);
        $this->assertSame('Jean Paul', $data['name']);
        $this->assertSame('Yaoundé', $data['from']);
        $this->assertSame('Douala', $data['to']);
    }

    public function test_build_qr_data_includes_all_fields(): void
    {
        $json = build_qr_data('REF', 'Name', 'From', 'To', 'Dep', 'Token');
        $data = json_decode($json, true);
        $this->assertArrayHasKey('ref', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('from', $data);
        $this->assertArrayHasKey('to', $data);
        $this->assertArrayHasKey('dep', $data);
        $this->assertArrayHasKey('token', $data);
    }

    // ── parse_qr_data() ──────────────────────────────────────

    public function test_parse_valid_qr_data(): void
    {
        $json = build_qr_data('I237-TEST', 'Jean', 'YDE', 'DLA', '2026-06-10 07:00:00', 'tok123');
        $result = parse_qr_data($json);
        $this->assertIsArray($result);
        $this->assertSame('I237-TEST', $result['ref']);
    }

    public function test_parse_invalid_json_returns_null(): void
    {
        $this->assertNull(parse_qr_data('not-json'));
    }

    public function test_parse_json_missing_field_returns_null(): void
    {
        $json = json_encode(['ref' => 'X', 'name' => 'Y']);
        $this->assertNull(parse_qr_data($json));
    }

    public function test_parse_empty_json_object_returns_null(): void
    {
        $this->assertNull(parse_qr_data('{}'));
    }

    // ── is_ticket_scannable() ────────────────────────────────

    public function test_confirmed_unscanned_is_scannable(): void
    {
        $this->assertTrue(is_ticket_scannable('confirmed', null));
    }

    public function test_confirmed_already_scanned_not_scannable(): void
    {
        $this->assertFalse(is_ticket_scannable('confirmed', '2026-06-06 07:15:00'));
    }

    public function test_pending_not_scannable(): void
    {
        $this->assertFalse(is_ticket_scannable('pending', null));
    }

    public function test_cancelled_not_scannable(): void
    {
        $this->assertFalse(is_ticket_scannable('cancelled', null));
    }

    // ── get_scan_error_message() ─────────────────────────────

    public function test_no_error_for_valid_ticket(): void
    {
        $this->assertNull(get_scan_error_message('confirmed', null));
    }

    public function test_error_for_non_confirmed(): void
    {
        $msg = get_scan_error_message('pending', null);
        $this->assertStringContainsString('non confirmée', $msg);
    }

    public function test_error_for_already_scanned(): void
    {
        $msg = get_scan_error_message('confirmed', '2026-06-06 07:15:00');
        $this->assertStringContainsString('déjà été scanné', $msg);
    }

    // ── format_scan_timestamp() ──────────────────────────────

    public function test_null_timestamp_shows_not_scanned(): void
    {
        $this->assertSame('Non scanné', format_scan_timestamp(null));
    }

    public function test_valid_timestamp_is_formatted(): void
    {
        $result = format_scan_timestamp('2026-06-06 07:15:00');
        $this->assertStringContainsString('06/06/2026', $result);
    }

    // ── generate_qr_token() ──────────────────────────────────

    public function test_token_is_64_hex_chars(): void
    {
        $token = generate_qr_token();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function test_tokens_are_unique(): void
    {
        $tokens = array_map(fn() => generate_qr_token(), range(1, 10));
        $this->assertCount(10, array_unique($tokens));
    }
}
