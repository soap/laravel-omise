# Changelog

All notable changes to `laravel-omise` will be documented in this file.

## v1.2.0 - 2025-10-30

### Major Improvements

#### Enhanced Capabilities Support
* **New:** Comprehensive `OmiseCapabilitiesCommand` with filtering and JSON export
* **New:** Added support for 40+ payment methods with Thai-friendly names
* **New:** Methods for checking supported currencies, banks, and payment methods
* **Improved:** Better capabilities API handling with proper filtering

#### Better Object Handling
* **Fixed:** Critical bug where Charge objects couldn't access properties from Omise SDK
* **Improved:** BaseObject now supports both array and object property access
* **New:** Added `hasProperty()`, `getProperty()`, and `validateProperties()` methods
* **New:** Charge validation with `isValid()` and `getDebugInfo()` methods

#### Enhanced Commands
* **Improved:** `omise:capabilities` command with `--currency`, `--type`, and `--format` options
* **Improved:** Better command output formatting with emoji and tables
* **New:** Support for payment method categorization (Card, QR, Wallet, etc.)

#### Developer Experience
* **New:** Comprehensive README with detailed examples
* **New:** Capabilities command documentation
* **Improved:** PHPStan Level 5 compliance maintained
* **Improved:** Better error handling and validation
* **New:** Unit tests for Charge and Capabilities functionality

### What's Changed

* Enhanced capabilities retrieval and display by @soap
* Fixed BaseObject to support both array and object access patterns
* Added comprehensive payment method support (PromptPay, TrueMoney, ShopeePay, etc.)
* Improved command output with better formatting and filtering
* Added validation methods for charge objects
* Updated documentation with real-world examples

### API Changes

**Non-breaking additions:**
- `Capabilities::getAvailablePaymentMethods()` - New method (old typo method deprecated)
- `Capabilities::getSupportedCurrencies()` - Get all supported currencies
- `Capabilities::getSupportedBanks()` - Get list of supported banks
- `Capabilities::getCountry()` - Get account country
- `Capabilities::hasPaymentMethod()` - Check specific payment method availability
- `BaseObject::hasProperty()` - Check if property exists
- `BaseObject::getProperty()` - Get property with default value
- `BaseObject::validateProperties()` - Validate required properties
- `Charge::isValid()` - Validate charge has required properties
- `Charge::getDebugInfo()` - Get debug information about charge object
- `Charge::retrieve()` - Alias for `find()` method

**Deprecated:**
- `Capabilities::getAavailablePaymentMethods()` - Use `getAvailablePaymentMethods()` instead (typo fix)

### Bug Fixes

* Fixed charge ID not found when using Omise SDK objects
* Fixed capabilities not properly filtering payment methods
* Fixed config typo (`sanbox_status` → `sandbox_status`)
* Fixed PHPStan errors with ignore patterns

**Full Changelog**: v1.1.32...v1.2.0

## v1.1.32 - 2025-06-05

### What's Changed

* Update README.md by @soap in https://github.com/soap/laravel-omise/pull/16
* Update packages by @soap in https://github.com/soap/laravel-omise/pull/17

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.31...v1.1.32

## v1.1.31 - 2025-06-03

### What's Changed

* Add updateWebhookUri by @soap in https://github.com/soap/laravel-omise/pull/15

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.30...v1.1.31

## v1.1.30 - 2025-05-14

### What's Changed

* Fixed: error in parsing datetime from Omise by @soap in https://github.com/soap/laravel-omise/pull/14

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.29...v1.1.30

## v1.1.29 - 2025-05-14

### What's Changed

* Add created_at to omise balance command by @soap in https://github.com/soap/laravel-omise/pull/13

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.28...v1.1.29

## v1.1.28 - 2025-05-14

### What's Changed

* Add created at to balance command by @soap in https://github.com/soap/laravel-omise/pull/12

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.27...v1.1.28

## v1.1.27 - 2025-05-14

### What's Changed

* Add --json to balance command by @soap in https://github.com/soap/laravel-omise/pull/11

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.26...v1.1.27

## v1.1.26 - 2025-05-14

### What's Changed

* Bump dependabot/fetch-metadata from 2.3.0 to 2.4.0 by @dependabot in https://github.com/soap/laravel-omise/pull/9
* Develop by @soap in https://github.com/soap/laravel-omise/pull/10

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.25...v1.1.26

## v1.1.25 - 2025-05-10

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.24...v1.1.25

### What's Changed

* Fix typo in Omise class by @soap in https://github.com/soap/laravel-omise/pull/8

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.24...v1.1.25

## v1.1.24 - 2025-05-10

### What's Changed

* Classes not found by @soap in https://github.com/soap/laravel-omise/pull/7

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.23...v1.1.24

## v1.1.23 - 2025-05-10

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.22...v1.1.23

## v1.1.22 - 2025-05-10

### What's Changed

* Develop by @soap in https://github.com/soap/laravel-omise/pull/6

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.21...v1.1.22

## v1.1.21 - 2025-05-10

### What's Changed

* Develop by @soap in https://github.com/soap/laravel-omise/pull/5

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.20...v1.1.21

## Fix typo in configuration file - 2025-04-03

- Fix typo in configuration (live mode detection is incorrect)

## v1.1.19 - 2024-12-21

### What's Changed

* [FIXED] Account command not show live mode status by @soap in https://github.com/soap/laravel-omise/pull/1

### New Contributors

* @soap made their first contribution in https://github.com/soap/laravel-omise/pull/1

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.18...v1.1.19

## v1.1.18 - 2024-12-19

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.17...v1.1.18

## v1.1.17 - 2024-12-18

Add capabilities method to Omise class

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.16...v1.1.17

## v1.1.16 - 2024-12-16

Add Source API to 'omise' class.
**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.15...v1.1.16

## v1.1.15 - 2024-12-15

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.14...v1.1.15

## v1.1.14 - 2024-12-15

- Now you can get public key and secret key from Omise instance.
  **Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.13...v1.1.14

## v1.1.13 - 2024-12-12

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.2...v1.1.13

## v1.1.2 - 2024-12-12

Bugs Fixed

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.1...v1.1.2

## v1.1.1 - 2024-12-12

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.1.0...v1.1.1

## v1.1.0 - 2024-12-12

- Add artisan command to retrieve account information.
- Restructure the package classes.

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.0.1...v1.1.0

## v1.0.1 - 2024-12-12

**Full Changelog**: https://github.com/soap/laravel-omise/compare/v1.0.0...v1.0.1

## v1.0.0 - 2024-12-12

**Full Changelog**: https://github.com/soap/laravel-omise/commits/v1.0.0
