<?php

declare(strict_types=1);

namespace G\Luhn\Tests;

use G\Luhn\Luhn;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Luhn::class)]
final class LuhnTest extends TestCase
{
    // ========================================
    // computeChecksum() tests
    // ========================================

    #[Test]
    #[DataProvider('validChecksumProvider')]
    public function testComputeChecksumReturnsCorrectDigit(string $input, string $expectedChecksum): void
    {
        $result = Luhn::computeChecksum($input);

        $this->assertSame($expectedChecksum, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validChecksumProvider(): array
    {
        return [
            'single digit 0' => ['0', '0'],
            'single digit 1' => ['1', '8'],
            'single digit 5' => ['5', '9'],
            'single digit 9' => ['9', '1'],
            'two digits 00' => ['00', '0'],
            'two digits 12' => ['12', '5'],
            'two digits 99' => ['99', '2'],
            'credit card prefix 4532015112830366' => ['453201511283036', '6'],
            'credit card prefix 5425233430109903' => ['542523343010990', '3'],
            'SIREN 443061841' => ['44306184', '1'],
            'SIRET 44306184100047' => ['4430618410004', '7'],
            'IMEI 352876106' => ['35287610', '6'],
            'long number' => ['123456789012345678', '5'],
            'all zeros' => ['000000000', '0'],
            'all ones' => ['111111111', '6'],
        ];
    }

    #[Test]
    public function testComputeChecksumThrowsExceptionForEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Luhn::computeChecksum('');
    }

    #[Test]
    #[DataProvider('invalidInputProvider')]
    public function testComputeChecksumThrowsExceptionForNonDigitInput(string $invalidInput): void
    {
        $this->expectException(InvalidArgumentException::class);

        Luhn::computeChecksum($invalidInput);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'letters only' => ['abc'],
            'mixed letters and digits' => ['123abc'],
            'special characters' => ['123-456'],
            'spaces' => ['123 456'],
            'decimal number' => ['123.456'],
            'negative sign' => ['-123'],
            'plus sign' => ['+123'],
            'unicode digits' => ['١٢٣'], // Arabic numerals
            'whitespace prefix' => [' 123'],
            'whitespace suffix' => ['123 '],
            'newline' => ["123\n456"],
        ];
    }

    // ========================================
    // appendChecksum() tests
    // ========================================

    #[Test]
    #[DataProvider('appendChecksumProvider')]
    public function testAppendChecksumReturnsCodeWithCheckDigit(string $input, string $expected): void
    {
        $result = Luhn::appendChecksum($input);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function appendChecksumProvider(): array
    {
        return [
            'single digit' => ['7', '75'],
            'credit card number' => ['453201511283036', '4532015112830366'],
            'SIREN' => ['44306184', '443061841'],
            'SIRET' => ['4430618410004', '44306184100047'],
            'short code' => ['12', '125'],
            'zeros' => ['00000000', '000000000'],
        ];
    }

    #[Test]
    public function testAppendChecksumThrowsExceptionForEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Luhn::appendChecksum('');
    }

    #[Test]
    public function testAppendChecksumThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Luhn::appendChecksum('abc');
    }

    // ========================================
    // isValid() tests
    // ========================================

    #[Test]
    #[DataProvider('validCodesProvider')]
    public function testIsValidReturnsTrueForValidCodes(string $validCode): void
    {
        $result = Luhn::isValid($validCode);

        $this->assertTrue($result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validCodesProvider(): array
    {
        return [
            'simple valid code' => ['18'],
            'credit card Visa' => ['4532015112830366'],
            'credit card MasterCard' => ['5425233430109903'],
            'credit card Amex' => ['378282246310005'],
            'SIREN' => ['443061841'],
            'SIRET' => ['44306184100047'],
            'code ending with 0' => ['00'],
            'longer valid code' => ['79927398713'],
            'test number 49927398716' => ['49927398716'],
        ];
    }

    #[Test]
    #[DataProvider('invalidCodesProvider')]
    public function testIsValidReturnsFalseForInvalidCodes(string $invalidCode): void
    {
        $result = Luhn::isValid($invalidCode);

        $this->assertFalse($result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCodesProvider(): array
    {
        return [
            'single digit' => ['1'],
            'empty string' => [''],
            'wrong checksum' => ['4532015112830365'],
            'transposed adjacent digits' => ['4532015112803366'],
            'one digit changed' => ['4532015112830376'],
            'invalid SIREN' => ['443061842'],
        ];
    }

    #[Test]
    public function testIsValidReturnsFalseForSingleCharacter(): void
    {
        $this->assertFalse(Luhn::isValid('0'));
        $this->assertFalse(Luhn::isValid('5'));
        $this->assertFalse(Luhn::isValid('9'));
    }

    #[Test]
    public function testIsValidReturnsFalseForEmptyString(): void
    {
        $this->assertFalse(Luhn::isValid(''));
    }

    // ========================================
    // Integration tests
    // ========================================

    #[Test]
    public function testAppendChecksumCreatesValidCode(): void
    {
        $baseCode = '123456789';

        $codeWithChecksum = Luhn::appendChecksum($baseCode);

        $this->assertTrue(Luhn::isValid($codeWithChecksum));
    }

    #[Test]
    #[DataProvider('randomCodesProvider')]
    public function testAppendChecksumAlwaysCreatesValidCode(string $baseCode): void
    {
        $codeWithChecksum = Luhn::appendChecksum($baseCode);

        $this->assertTrue(Luhn::isValid($codeWithChecksum));
        $this->assertSame(strlen($baseCode) + 1, strlen($codeWithChecksum));
        $this->assertStringStartsWith($baseCode, $codeWithChecksum);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function randomCodesProvider(): array
    {
        return [
            'short code' => ['1'],
            'medium code' => ['12345'],
            'long code' => ['9876543210123456789'],
            'all same digits' => ['5555555555'],
            'alternating' => ['1010101010'],
            'ascending' => ['123456789'],
            'descending' => ['987654321'],
        ];
    }

    #[Test]
    public function testComputeChecksumIsConsistent(): void
    {
        $code = '453201511283036';

        $checksum1 = Luhn::computeChecksum($code);
        $checksum2 = Luhn::computeChecksum($code);

        $this->assertSame($checksum1, $checksum2);
    }

    #[Test]
    public function testChecksumIsAlwaysSingleDigit(): void
    {
        $testCodes = ['1', '12', '123', '1234567890', '99999999999999999'];

        foreach ($testCodes as $code) {
            $checksum = Luhn::computeChecksum($code);

            $this->assertSame(1, strlen($checksum));
            $this->assertTrue(ctype_digit($checksum));
        }
    }

    // ========================================
    // Base support tests
    // ========================================

    #[Test]
    public function testComputeChecksumThrowsExceptionForInvalidBase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base must be between 2 and 36.');

        Luhn::computeChecksum('123', 1);
    }

    #[Test]
    public function testComputeChecksumThrowsExceptionForBaseTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base must be between 2 and 36.');

        Luhn::computeChecksum('123', 37);
    }

    #[Test]
    #[DataProvider('baseChecksumProvider')]
    public function testComputeChecksumWorksInDifferentBases(string $code, int $base, string $expectedChecksum): void
    {
        $result = Luhn::computeChecksum($code, $base);

        $this->assertSame($expectedChecksum, $result);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function baseChecksumProvider(): array
    {
        return [
            'base 2' => ['1011', 2, '1'],
            'base 16 lowercase' => ['a3f2', 16, 'd'],
            'base 16 uppercase converted' => ['A3F2', 16, 'd'],
            'base 36' => ['abc123', 36, 'i'],
            'base 36 uppercase' => ['ABC123', 36, 'i'],
            'base 36 complex' => ['jgjixl36f8r2fn', 36, '2'],
        ];
    }

    #[Test]
    #[DataProvider('baseValidCodesProvider')]
    public function testIsValidWorksInDifferentBases(string $code, int $base): void
    {
        $this->assertTrue(Luhn::isValid($code, $base));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function baseValidCodesProvider(): array
    {
        return [
            'base 2 valid' => ['10111', 2],
            'base 16 valid' => ['a3f2d', 16],
            'base 36 valid' => ['abc123i', 36],
            'base 36 complex' => ['jgjixl36f8r2fn2', 36],
        ];
    }

    #[Test]
    #[DataProvider('baseInvalidCodesProvider')]
    public function testIsValidReturnsFalseForInvalidCodesInDifferentBases(string $code, int $base): void
    {
        $this->assertFalse(Luhn::isValid($code, $base));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function baseInvalidCodesProvider(): array
    {
        return [
            'base 2 invalid checksum' => ['10110', 2],
            'base 16 invalid checksum' => ['a3f2e', 16],
            'base 36 invalid checksum' => ['abc123j', 36],
            'invalid base range' => ['123', 1],
            'invalid base too high' => ['123', 37],
            'invalid char for base' => ['abc', 10],
        ];
    }

    #[Test]
    #[DataProvider('appendChecksumDifferentBasesProvider')]
    public function testAppendChecksumWorksInDifferentBases(string $code, int $base, string $expected): void
    {
        $result = Luhn::appendChecksum($code, $base);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function appendChecksumDifferentBasesProvider(): array
    {
        return [
            'base 2' => ['1011', 2, '10111'],
            'base 16' => ['a3f2', 16, 'a3f2d'],
            'base 36' => ['abc123', 36, 'abc123i'],
        ];
    }

    #[Test]
    public function testAppendChecksumCreatesValidCodeInBase16(): void
    {
        $baseCode = 'cafe';

        $codeWithChecksum = Luhn::appendChecksum($baseCode, 16);

        $this->assertTrue(Luhn::isValid($codeWithChecksum, 16));
    }

    #[Test]
    public function testAppendChecksumCreatesValidCodeInBase36(): void
    {
        $baseCode = 'test123';

        $codeWithChecksum = Luhn::appendChecksum($baseCode, 36);

        $this->assertTrue(Luhn::isValid($codeWithChecksum, 36));
    }

    #[Test]
    public function testComputeChecksumThrowsExceptionForInvalidCharInBase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Input code contains invalid characters for base 10.');

        Luhn::computeChecksum('abc', 10);
    }

    #[Test]
    public function testCaseInsensitiveHandlingForAlphanumericBases(): void
    {
        $lowerChecksum = Luhn::computeChecksum('abc', 16);
        $upperChecksum = Luhn::computeChecksum('ABC', 16);

        $this->assertSame($lowerChecksum, $upperChecksum);

        $this->assertTrue(Luhn::isValid('abc' . $lowerChecksum, 16));
        $this->assertTrue(Luhn::isValid('ABC' . $upperChecksum, 16));
    }
}
