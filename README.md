# Luhn

A standalone PHP library implementing the Luhn algorithm with multi-base support (base 2–36). Provides checksum computation, generation, and validation for numeric and alphanumeric codes.

## Tech Stack

- PHP 8.3+
- No runtime dependencies

## Installation

```bash
composer require gadwinn/luhn
```

## Usage

All functionality is exposed through the static API of the `G\Luhn\Luhn` class.

```php
use G\Luhn\Luhn;
```

---

### `Luhn::computeChecksum(string $code, int $base = 10): string`

Computes and returns the single checksum character for a given code, without appending it.

```php
// Base 10 (default)
Luhn::computeChecksum('453201511283036');  // '6'
Luhn::computeChecksum('44306184');        // '1'  - SIREN
Luhn::computeChecksum('4430618410004');   // '7'  - SIRET
Luhn::computeChecksum('35287610');        // '6'  - IMEI

// Base 16
Luhn::computeChecksum('a3f2', 16);        // 'd'

// Base 36
Luhn::computeChecksum('abc123', 36);      // 'i'
```

Throws `\InvalidArgumentException` if:
- `$base` is not in range `[2, 36]`
- `$code` is empty
- `$code` contains characters invalid for the given base

---

### `Luhn::appendChecksum(string $code, int $base = 10): string`

Returns the original code with its computed checksum character appended.

```php
Luhn::appendChecksum('453201511283036');  // '4532015112830366'
Luhn::appendChecksum('44306184');         // '443061841'
Luhn::appendChecksum('4430618410004');    // '44306184100047'

// Arbitrary bases
Luhn::appendChecksum('a3f2', 16);         // 'a3f2d'
Luhn::appendChecksum('abc123', 36);       // 'abc123i'
Luhn::appendChecksum('1011', 2);          // '10111'
```

---

### `Luhn::isValid(string $code, int $base = 10): bool`

Validates a complete code (last character is treated as the checksum digit). Returns `false` instead of throwing for invalid input.

```php
// Valid codes
Luhn::isValid('4532015112830366');  // true  - Visa
Luhn::isValid('5425233430109903');  // true  - Mastercard
Luhn::isValid('378282246310005');   // true  - Amex
Luhn::isValid('443061841');         // true  - SIREN
Luhn::isValid('79927398713');       // true

// Invalid codes
Luhn::isValid('4532015112830365');  // false - wrong checksum
Luhn::isValid('4532015112803366');  // false - transposed digits
Luhn::isValid('443061842');         // false - invalid SIREN
Luhn::isValid('5');                 // false - too short (min length: 2)
Luhn::isValid('');                  // false

// Arbitrary bases
Luhn::isValid('a3f2d', 16);         // true
Luhn::isValid('abc123i', 36);       // true
Luhn::isValid('123', 1);            // false - invalid base
```

---

### Real-world pattern: generate then validate

```php
use G\Luhn\Luhn;

$rawCode = '123456789';

$fullCode = Luhn::appendChecksum($rawCode);
// $fullCode = '1234567897'

$isValid = Luhn::isValid($fullCode);
// $isValid = true
```

### Case insensitivity for alphanumeric bases

Input is normalized to lowercase internally. Uppercase characters are accepted transparently.

```php
Luhn::computeChecksum('A3F2', 16) === Luhn::computeChecksum('a3f2', 16); // true
Luhn::isValid('A3F2D', 16);                                               // true
```

## License

MIT, see [LICENSE](LICENSE).
