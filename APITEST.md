# Real API Testing Guide

## 🚀 Quick Setup

### 1. Get Omise Test Keys
1. Sign up at [Omise Dashboard](https://dashboard.omise.co)
2. Go to **Test Mode** → **API Keys**
3. Copy your `Public Key` and `Secret Key`

### 2. Configure Environment
```bash
# Copy example file
cp .env.example .env

# Edit .env file and add your keys
OMISE_TEST_PUBLIC_KEY=pkey_test_5xxxxxxxxxxxxx
OMISE_TEST_SECRET_KEY=skey_test_5xxxxxxxxxxxxx
OMISE_SANDBOX_MODE=true
```

### 3. Install Dependencies
```bash
composer install
```

## 🧪 Running Tests

### Run All Integration Tests
```bash
vendor/bin/pest --group=integration
```

### Run Specific Test
```bash
vendor/bin/pest --filter="can create token and charge successfully"
```

### Run with Verbose Output
```bash
vendor/bin/pest --group=integration --verbose
```

### Skip Integration Tests (for CI/CD)
```bash
vendor/bin/pest --exclude-group=integration
```

## 🎯 Test Coverage

### Token & Charge Tests
- ✅ Create token and successful charge
- ✅ Multiple charges with different tokens
- ✅ Partial and full refunds
- ✅ Different card brands (Visa, Mastercard)
- ✅ Large amount handling

### Error Handling Tests
- ✅ Declined cards
- ✅ Insufficient funds
- ✅ Invalid card numbers
- ✅ Expired cards

### Token Management Tests
- ✅ Token usage status
- ✅ Card information retrieval
- ✅ Token security

## 🔧 Test Card Numbers

### Successful Cards
```
Visa: 4242424242424242
Mastercard: 5555555555554444
```

### Error Testing Cards
```
Declined: 4000000000000002
Insufficient Funds: 4000000000000341
```

## 📊 Example Test Run

```bash
$ vendor/bin/pest --group=integration

✓ can create token and charge successfully
✓ can create multiple charges with different tokens  
✓ can create token and partial refund charge
✓ can create token and full refund charge
✓ can create token with different card brands
✓ can retrieve charge details after token payment
✓ validates token creation with invalid card data
✓ handles expired card in token creation
✓ can handle large amount charges with token

Tests:  9 passed
Time:   15.43s
```

## ⚠️ Important Notes

### Security
- **Never commit real API keys** to version control
- Use test keys only for development
- Test mode charges are **not real transactions**

### Rate Limits
- Omise has API rate limits
- Tests respect these limits with proper delays
- Reduce concurrent tests if you hit limits

### Test Data
- All test charges use Thai Baht (THB)
- Amounts are in **satang** (smallest unit)
- 100000 satang = 1000 THB

## 🐛 Troubleshooting

### Tests Skip with "Sandbox keys not configured"
```bash
# Check your .env file
cat .env | grep OMISE

# Verify config is loaded
php artisan tinker
>>> config('omise.keys.test.public')
```

### API Connection Issues
```bash
# Test connection
php artisan omise:verify

# Check API status
curl -I https://api.omise.co
```

### Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
```

## 🔗 Resources

- [Omise API Documentation](https://www.omise.co/docs)
- [Test Card Numbers](https://www.omise.co/docs/testing)
- [API Rate Limits](https://www.omise.co/docs/api-reference#rate-limits)