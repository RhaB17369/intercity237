<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour includes/auth.php
 * Cible: ≥80% de couverture de code
 */
class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ── h() — Échappement HTML ──────────────────────────────

    public function test_h_escapes_script_tag(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', h('<script>alert(1)</script>'));
    }

    public function test_h_escapes_double_quotes(): void
    {
        $this->assertSame('&quot;hello&quot;', h('"hello"'));
    }

    public function test_h_escapes_single_quotes(): void
    {
        $this->assertSame('&#039;test&#039;', h("'test'"));
    }

    public function test_h_does_not_alter_safe_string(): void
    {
        $this->assertSame('Hello World', h('Hello World'));
    }

    public function test_h_handles_empty_string(): void
    {
        $this->assertSame('', h(''));
    }

    public function test_h_escapes_ampersand(): void
    {
        $this->assertSame('a &amp; b', h('a & b'));
    }

    // ── is_logged_in() ──────────────────────────────────────

    public function test_is_logged_in_false_when_no_session(): void
    {
        $this->assertFalse(is_logged_in());
    }

    public function test_is_logged_in_true_when_user_id_set(): void
    {
        $_SESSION['user_id'] = 42;
        $this->assertTrue(is_logged_in());
    }

    public function test_is_logged_in_false_when_user_id_zero(): void
    {
        $_SESSION['user_id'] = 0;
        $this->assertFalse(is_logged_in());
    }

    // ── is_admin() ──────────────────────────────────────────

    public function test_is_admin_false_when_no_role(): void
    {
        $this->assertFalse(is_admin());
    }

    public function test_is_admin_false_for_employee_role(): void
    {
        $_SESSION['role'] = 'employee';
        $this->assertFalse(is_admin());
    }

    public function test_is_admin_true_for_admin_role(): void
    {
        $_SESSION['role'] = 'admin';
        $this->assertTrue(is_admin());
    }

    public function test_is_admin_true_for_superadmin_role(): void
    {
        $_SESSION['role'] = 'superadmin';
        $this->assertTrue(is_admin());
    }

    // ── is_superadmin() ─────────────────────────────────────

    public function test_is_superadmin_false_when_no_role(): void
    {
        $this->assertFalse(is_superadmin());
    }

    public function test_is_superadmin_false_for_admin_role(): void
    {
        $_SESSION['role'] = 'admin';
        $this->assertFalse(is_superadmin());
    }

    public function test_is_superadmin_false_for_employee_role(): void
    {
        $_SESSION['role'] = 'employee';
        $this->assertFalse(is_superadmin());
    }

    public function test_is_superadmin_true_for_superadmin_role(): void
    {
        $_SESSION['role'] = 'superadmin';
        $this->assertTrue(is_superadmin());
    }

    // ── csrf_token() ────────────────────────────────────────

    public function test_csrf_token_returns_64_char_hex(): void
    {
        $token = csrf_token();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function test_csrf_token_is_stable_across_calls(): void
    {
        $token1 = csrf_token();
        $token2 = csrf_token();
        $this->assertSame($token1, $token2);
    }

    public function test_csrf_token_stored_in_session(): void
    {
        csrf_token();
        $this->assertArrayHasKey('csrf_token', $_SESSION);
    }

    public function test_csrf_token_reuses_existing_session_token(): void
    {
        $_SESSION['csrf_token'] = 'abcd1234';
        $token = csrf_token();
        $this->assertSame('abcd1234', $token);
    }

    // ── verify_csrf() ───────────────────────────────────────

    public function test_verify_csrf_passes_on_get_request(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // Should not throw or die
        verify_csrf();
        $this->assertTrue(true);
    }
}
