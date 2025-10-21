<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseToken;
use Soap\LaravelOmise\OmiseConfig;

/**
 * @property object $object
 * @property string $id
 * @property bool $livemode
 * @property string $location
 * @property bool $used
 * @property array $card
 * @property string $created
 *
 * @see https://www.omise.co/tokens-api
 */
class Token extends BaseObject
{
    private $omiseConfig;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    /**
     * Create token from card data
     *
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function create(array $cardData)
    {
        try {
            $this->refresh(OmiseToken::create($cardData, $this->omiseConfig->getPublicKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * Retrieve token by ID
     *
     * @param  string  $id
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function find($id)
    {
        try {
            $this->refresh(OmiseToken::retrieve($id, $this->omiseConfig->getPublicKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * Check if token has been used
     */
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * Check if token is still valid (not used)
     */
    public function isValid(): bool
    {
        return ! $this->isUsed();
    }

    /**
     * Check if token has not been used yet
     */
    public function isUnused(): bool
    {
        return ! $this->isUsed();
    }

    /**
     * Get card information from token
     *
     * @return array|null
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Get masked card number
     *
     * @return string|null
     */
    public function getMaskedCardNumber()
    {
        return $this->card['last_digits'] ?? null;
    }

    /**
     * Get card brand
     *
     * @return string|null
     */
    public function getCardBrand()
    {
        return $this->card['brand'] ?? null;
    }

    /**
     * Get card expiration month
     *
     * @return int|null
     */
    public function getCardExpirationMonth()
    {
        return $this->card['expiration_month'] ?? null;
    }

    /**
     * Get card expiration year
     *
     * @return int|null
     */
    public function getCardExpirationYear()
    {
        return $this->card['expiration_year'] ?? null;
    }

    /**
     * Get card fingerprint
     *
     * @return string|null
     */
    public function getCardFingerprint()
    {
        return $this->card['fingerprint'] ?? null;
    }

    /**
     * Get card holder name
     *
     * @return string|null
     */
    public function getCardHolderName()
    {
        return $this->card['name'] ?? null;
    }

    /**
     * Check if card is expired
     */
    public function isCardExpired(): bool
    {
        if (! $this->card) {
            return true;
        }

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        $expirationYear = $this->getCardExpirationYear();
        $expirationMonth = $this->getCardExpirationMonth();

        if ($expirationYear < $currentYear) {
            return true;
        }

        if ($expirationYear == $currentYear && $expirationMonth < $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * Get token creation date
     *
     * @return \Carbon\Carbon|null
     */
    public function getCreatedAt()
    {
        return $this->created ? \Carbon\Carbon::parse($this->created) : null;
    }

    /**
     * Check if token is live mode
     */
    public function isLiveMode(): bool
    {
        return $this->livemode;
    }

    /**
     * Check if token is test mode
     */
    public function isTestMode(): bool
    {
        return ! $this->isLiveMode();
    }

    /**
     * Convert token to array for API usage
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'used' => $this->used,
            'livemode' => $this->livemode,
            'card' => $this->card,
            'created' => $this->created,
        ];
    }

    /**
     * Get token summary for logging/display
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'used' => $this->used,
            'card_brand' => $this->getCardBrand(),
            'card_last_digits' => $this->getMaskedCardNumber(),
            'card_expired' => $this->isCardExpired(),
            'created_at' => $this->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Validate card data before token creation
     *
     * @return array|true Returns array of errors or true if valid
     */
    public static function validateCardData(array $cardData)
    {
        $errors = [];

        // Required fields
        $requiredFields = ['name', 'number', 'expiration_month', 'expiration_year', 'security_code'];

        foreach ($requiredFields as $field) {
            if (empty($cardData[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        if (! empty($errors)) {
            return $errors;
        }

        // Validate card number (basic Luhn algorithm check)
        if (! self::isValidCardNumber($cardData['number'])) {
            $errors[] = 'Invalid card number';
        }

        // Validate expiration month
        $month = (int) $cardData['expiration_month'];
        if ($month < 1 || $month > 12) {
            $errors[] = 'Invalid expiration month';
        }

        // Validate expiration year
        $year = (int) $cardData['expiration_year'];
        $currentYear = (int) date('Y');
        if ($year < $currentYear || $year > $currentYear + 20) {
            $errors[] = 'Invalid expiration year';
        }

        // Check if card is expired
        if ($year == $currentYear && $month < (int) date('m')) {
            $errors[] = 'Card is expired';
        }

        // Validate security code
        $cvv = $cardData['security_code'];
        if (! preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = 'Invalid security code';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Basic Luhn algorithm validation for card numbers
     */
    private static function isValidCardNumber(string $cardNumber): bool
    {
        // Remove spaces and non-digits
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        // Check length
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }

        // Luhn algorithm
        $sum = 0;
        $alternate = false;

        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = (int) $cardNumber[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = ($digit % 10) + 1;
                }
            }

            $sum += $digit;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }

    /**
     * Get card brand from card number
     */
    public static function getCardBrandFromNumber(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        $patterns = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]|^2[2-7]/',
            'amex' => '/^3[47]/',
            'discover' => '/^6(?:011|5)/',
            'diners' => '/^3[0689]/',
            'jcb' => '/^35/',
        ];

        foreach ($patterns as $brand => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $brand;
            }
        }

        return 'unknown';
    }
}
