<?php

namespace Tests\Unit\Helpers;

use App\Helpers\InputSanitizer;
use PHPUnit\Framework\TestCase;

class InputSanitizerTest extends TestCase
{
    public function test_sanitize_string_returns_null_for_null(): void
    {
        $this->assertNull(InputSanitizer::sanitizeString(null));
    }

    public function test_sanitize_string_strips_html_tags(): void
    {
        $result = InputSanitizer::sanitizeString('<script>alert("xss")</script>Hello');
        $this->assertEquals('alert(&quot;xss&quot;)Hello', $result);
    }

    public function test_sanitize_string_encodes_special_characters(): void
    {
        $result = InputSanitizer::sanitizeString('Hello "world" & <test>');
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    public function test_sanitize_string_trims_whitespace(): void
    {
        $result = InputSanitizer::sanitizeString('  Hello World  ');
        $this->assertEquals('Hello World', $result);
    }

    public function test_sanitize_string_handles_empty_string(): void
    {
        $result = InputSanitizer::sanitizeString('');
        $this->assertEquals('', $result);
    }

    public function test_sanitize_search_returns_null_for_null(): void
    {
        $this->assertNull(InputSanitizer::sanitizeSearch(null));
    }

    public function test_sanitize_search_removes_special_characters(): void
    {
        $result = InputSanitizer::sanitizeSearch('Hello! @World# $Test%');
        $this->assertStringNotContainsString('!', $result);
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringNotContainsString('#', $result);
        $this->assertStringNotContainsString('$', $result);
    }

    public function test_sanitize_search_allows_letters_numbers_spaces(): void
    {
        $result = InputSanitizer::sanitizeSearch('Ruang Kelas 2B');
        $this->assertEquals('Ruang Kelas 2B', $result);
    }

    public function test_sanitize_search_allows_hyphens_and_underscores(): void
    {
        $result = InputSanitizer::sanitizeSearch('LOK-001_test');
        $this->assertEquals('LOK-001_test', $result);
    }

    public function test_sanitize_search_supports_unicode(): void
    {
        $result = InputSanitizer::sanitizeSearch('Gedung Kantor Bersih');
        $this->assertEquals('Gedung Kantor Bersih', $result);
    }

    public function test_sanitize_array_returns_null_for_null(): void
    {
        $this->assertNull(InputSanitizer::sanitizeArray(null));
    }

    public function test_sanitize_array_sanitizes_string_elements(): void
    {
        $input = ['<b>bold</b>', 'normal', '<script>xss</script>'];
        $result = InputSanitizer::sanitizeArray($input);

        $this->assertEquals('bold', $result[0]);
        $this->assertEquals('normal', $result[1]);
        $this->assertEquals('xss', $result[2]);
    }

    public function test_sanitize_array_preserves_non_string_elements(): void
    {
        $input = ['text', 123, true, null];
        $result = InputSanitizer::sanitizeArray($input);

        $this->assertEquals(123, $result[1]);
        $this->assertTrue($result[2]);
        $this->assertNull($result[3]);
    }

    public function test_sanitize_string_prevents_xss_injection(): void
    {
        $xssAttempts = [
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '"><script>alert(document.cookie)</script>',
            "javascript:alert('xss')",
        ];

        foreach ($xssAttempts as $attempt) {
            $result = InputSanitizer::sanitizeString($attempt);
            $this->assertStringNotContainsString('<script>', $result);
            $this->assertStringNotContainsString('<img', $result);
            $this->assertStringNotContainsString('<svg', $result);
        }
    }
}
