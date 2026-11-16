<?php

declare(strict_types=1);

namespace G\Luhn;

use InvalidArgumentException;

final class Luhn
{
    private const string CHARSET = '0123456789abcdefghijklmnopqrstuvwxyz';
    private const int MIN_BASE = 2;
    private const int MAX_BASE = 36;
    private const int MIN_LENGTH_WITH_CHECKSUM = 2;

    /**
     * Computes the Luhn checksum for a given code in the specified base.
     *
     * @param string $code The input code to compute the checksum for
     * @param int $base The base to use (2-36). Defaults to 10.
     * @return string The computed checksum character
     * @throws InvalidArgumentException If base is not between 2-36, code is empty,
     * or contains invalid characters for the base
     */
    public static function computeChecksum(string $code, int $base = 10): string
    {
        if ($base < self::MIN_BASE || self::MAX_BASE < $base) {
            throw new InvalidArgumentException('Base must be between 2 and 36.');
        }

        $length = strlen($code);
        if (!$length) {
            throw new InvalidArgumentException('Input code must not be empty.');
        }

        $code = strtolower($code);

        if (!self::isValidForBase($code, $base)) {
            throw new InvalidArgumentException("Input code contains invalid characters for base $base.");
        }

        // Calculate sum using Luhn algorithm (without checksum digit)
        $sum = 0;
        $shouldDouble = true;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) strpos(self::CHARSET, $code[$i]);

            if ($shouldDouble) {
                $digit *= 2;
                if ($digit >= $base) {
                    $digit = $digit - ($base - 1);
                }
            }

            $sum += $digit;
            $shouldDouble = !$shouldDouble;
        }

        // Calculate checksum: the digit that makes (sum + checksum) % base == 0
        $checksum = ($base - ($sum % $base)) % $base;

        return self::CHARSET[$checksum];
    }

    /**
     * Appends the computed Luhn checksum to the given code.
     *
     * @param string $code The input code
     * @param int $base The base to use (2-36). Defaults to 10.
     * @return string The code with the checksum appended
     */
    public static function appendChecksum(string $code, int $base = 10): string
    {
        return $code . self::computeChecksum($code, $base);
    }

    /**
     * Validates if a code has a valid Luhn checksum.
     *
     * @param string $code The code to validate (must include checksum as last character)
     * @param int $base The base to use (2-36). Defaults to 10.
     * @return bool True if the checksum is valid, false otherwise
     */
    public static function isValid(string $code, int $base = 10): bool
    {
        if ($base < self::MIN_BASE || self::MAX_BASE < $base) {
            return false;
        }

        $length = strlen($code);

        if ($length < self::MIN_LENGTH_WITH_CHECKSUM) {
            return false;
        }

        $code = strtolower($code);

        if (!self::isValidForBase($code, $base)) {
            return false;
        }

        // Extract checksum and code parts
        $providedChecksum = $code[$length - 1];
        $codeWithoutChecksum = substr($code, 0, $length - 1);

        // Compare with calculated checksum
        try {
            $calculatedChecksum = self::computeChecksum($codeWithoutChecksum, $base);
            return $providedChecksum === $calculatedChecksum;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Checks if a code contains only valid characters for the given base.
     *
     * @param string $code The code to validate
     * @param int $base The base to check against
     * @return bool True if all characters are valid for the base, false otherwise
     */
    private static function isValidForBase(string $code, int $base): bool
    {
        $validChars = substr(self::CHARSET, 0, $base);

        for ($i = 0, $length = strlen($code); $i < $length; $i++) {
            if (strpos($validChars, $code[$i]) === false) {
                return false;
            }
        }

        return true;
    }
}
