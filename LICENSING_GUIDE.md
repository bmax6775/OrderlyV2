# Orderlyy - Licensing System Guide

## Overview

Orderlyy includes a comprehensive licensing system designed for CodeCanyon distribution and white-label deployment. This guide covers everything you need to know about generating, managing, and implementing licenses.

## Table of Contents

1. [License System Architecture](#license-system-architecture)
2. [CodeCanyon Distribution Setup](#codecanyon-distribution-setup)
3. [License Generation](#license-generation)
4. [Installation Process](#installation-process)
5. [License Management](#license-management)
6. [White Label Configuration](#white-label-configuration)
7. [Troubleshooting](#troubleshooting)

## License System Architecture

### Database Schema

The licensing system uses three main tables:

- **`licenses`**: Core license information and status
- **`license_validations`**: Validation attempts and logs
- **`installations`**: Installation tracking and history

### Key Features

- **Domain Binding**: Licenses can be restricted to specific domains
- **Feature Control**: JSON-based feature flags for different license tiers
- **Remote Validation**: Optional remote license server validation
- **Installation Tracking**: Complete audit trail of installations
- **Auto-expiration**: Support for time-limited licenses

## CodeCanyon Distribution Setup

### 1. Prepare Distribution Package

Create a distribution-ready package with these files:

```
orderlyy-v1.0.0/
├── install.php                 # One-click installer
├── license_system.php          # License validation system
├── database_postgresql.sql     # Database schema
├── [all application files]
├── README.md                   # Installation instructions
└── LICENSING_GUIDE.md          # This guide
```

### 2. License Server Setup (Optional)

For enhanced security, set up a remote license validation server:

```php
// Example license server endpoint
https://api.yourcompany.com/license/validate

// Expected response format:
{
    "valid": true,
    "license": {
        "key": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
        "domain": "customer-domain.com",
        "features": {...}
    }
}
```

### 3. CodeCanyon Listing Requirements

- **License Key Generation**: Automatic generation for each purchase
- **Purchase Code Integration**: Link with Envato Market API
- **Documentation**: Complete setup and usage instructions
- **Support**: License validation and troubleshooting support

## License Generation

### Manual License Creation

```php
require_once 'license_system.php';

$licenseSystem = new OrderlyyLicense($pdo);

// Create new license
$result = $licenseSystem->createLicense([
    'email' => 'customer@example.com',
    'name' => 'Customer Name'
], [
    'unlimited_orders' => true,
    'courier_integrations' => true,
    'white_label' => true,
    'priority_support' => true
]);

// Returns: ['success' => true, 'license_key' => 'XXXXXXXXXX...']
```

### Automated Generation (CodeCanyon Integration)

```php
// CodeCanyon webhook handler
if ($_POST['item_id'] == 'YOUR_ITEM_ID') {
    $customerData = [
        'email' => $_POST['buyer_email'],
        'name' => $_POST['buyer_name']
    ];
    
    $features = [
        'unlimited_orders' => true,
        'courier_integrations' => true,
        'white_label' => true,
        'priority_support' => true
    ];
    
    $license = $licenseSystem->createLicense($customerData, $features);
    
    // Send license key to customer
    sendLicenseEmail($customerData['email'], $license['license_key']);
}
```

## Installation Process

### Customer Installation Steps

1. **Download & Extract**: Customer downloads and extracts Orderlyy files
2. **Run Installer**: Navigate to `https://their-domain.com/install.php`
3. **System Check**: Installer verifies server requirements
4. **Database Setup**: Configure PostgreSQL connection
5. **License Verification**: Enter 32-character license key
6. **Admin Account**: Create super admin credentials
7. **Branding Setup**: Customize app name, colors, logo
8. **Installation Complete**: System ready for use

### Installer Features

- **System Requirements Check**: PHP 7.4+, PostgreSQL, required extensions
- **Database Auto-Setup**: Automatic schema creation and data population
- **License Validation**: Real-time license verification
- **Branding Configuration**: Custom app name, colors, and logo upload
- **Security**: Auto-generated config file with secure keys

### Example Installation

```bash
# Customer workflow:
1. Upload files to hosting
2. Visit: https://customer-domain.com/install.php
3. Follow 6-step installation wizard
4. Enter license: ORDERLYY2025DEMO123456789012
5. Configure database and admin account
6. Customize branding
7. Installation complete!
```

## License Management

### Admin License Management Interface

Create an admin interface for license management:

```php
// Get all licenses
$stmt = $pdo->query("
    SELECT l.*, COUNT(lv.id) as validation_count 
    FROM licenses l 
    LEFT JOIN license_validations lv ON l.license_key = lv.license_key 
    GROUP BY l.id 
    ORDER BY l.created_at DESC
");
$licenses = $stmt->fetchAll();

// Display in admin table with actions:
// - View Details
// - Activate/Deactivate
// - Change Domain
// - Update Features
// - View Validation Logs
```

### License Status Management

```php
// Activate license
$result = $licenseSystem->activateLicense(
    'ORDERLYY2025DEMO123456789012',
    'customer-domain.com',
    ['email' => 'customer@example.com', 'name' => 'Customer Name']
);

// Deactivate license
$result = $licenseSystem->deactivateLicense('ORDERLYY2025DEMO123456789012');

// Check license status
$info = $licenseSystem->getLicenseInfo('ORDERLYY2025DEMO123456789012');
```

### Feature Management

```php
// Update license features
$stmt = $pdo->prepare("
    UPDATE licenses 
    SET features = ? 
    WHERE license_key = ?
");
$stmt->execute([
    json_encode([
        'unlimited_orders' => true,
        'courier_integrations' => true,
        'white_label' => false,  // Disable white label
        'priority_support' => true
    ]),
    'ORDERLYY2025DEMO123456789012'
]);
```

## White Label Configuration

### Branding System Integration

The licensing system integrates with the branding system for white-label deployments:

```php
// Check white label permission
if ($licenseSystem->hasFeature($licenseKey, 'white_label')) {
    // Allow full branding customization
    // - Custom app name
    // - Custom logo upload
    // - Custom color scheme
    // - Custom footer text
} else {
    // Restrict branding options
    // - Show "Powered by Orderlyy" footer
    // - Limit color customization
}
```

### Installation Branding

During installation, customers can customize:

1. **Application Name**: Default "Orderlyy" → Custom name
2. **Tagline**: Custom tagline for their business
3. **Color Scheme**: Primary and secondary colors
4. **Logo**: Upload custom logo (post-installation)

### Runtime Branding Enforcement

```php
// In each page header:
require_once 'get_branding.php';

// Branding settings automatically respect license features
if (!$licenseSystem->hasFeature($licenseKey, 'white_label')) {
    $branding['footer_text'] = 'Powered by Orderlyy - Professional Order Management';
}
```

## License Validation Flow

### Real-time Validation

```php
// Every page load (cached for performance)
function validateCurrentLicense() {
    $licenseKey = getBrandingSetting('license_key');
    $domain = $_SERVER['HTTP_HOST'];
    
    // Check cache first (5-minute cache)
    $cacheKey = "license_validation_{$licenseKey}_{$domain}";
    $cached = getCache($cacheKey);
    
    if ($cached !== null) {
        return $cached;
    }
    
    // Validate license
    $licenseSystem = new OrderlyyLicense($pdo);
    $result = $licenseSystem->validateLicense($licenseKey, $domain);
    
    // Cache result
    setCache($cacheKey, $result['valid'], 300); // 5 minutes
    
    return $result['valid'];
}
```

### Grace Period

Implement a grace period for temporary validation failures:

```php
// Allow 24-hour grace period for network issues
$lastValidation = getCache("last_valid_license_check");
$gracePeriod = 24 * 60 * 60; // 24 hours

if (!$isValid && $lastValidation && (time() - $lastValidation) < $gracePeriod) {
    // Continue operation during grace period
    $isValid = true;
} elseif ($isValid) {
    // Update last valid check
    setCache("last_valid_license_check", time());
}
```

## Troubleshooting

### Common Issues

#### 1. "Invalid License Key"
- **Cause**: Wrong license key format or non-existent key
- **Solution**: Verify 32-character format, check database

#### 2. "Domain Mismatch"
- **Cause**: License activated for different domain
- **Solution**: Update license domain or transfer license

#### 3. "License Expired"
- **Cause**: Time-limited license has expired
- **Solution**: Renew license or update expiry date

#### 4. "Remote Validation Failed"
- **Cause**: License server unreachable
- **Solution**: Check network, implement local fallback

### Debug License Issues

```php
// Enable license debugging
define('ORDERLYY_LICENSE_DEBUG', true);

// View validation logs
$stmt = $pdo->prepare("
    SELECT * FROM license_validations 
    WHERE license_key = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$licenseKey]);
$logs = $stmt->fetchAll();
```

### Support Workflow

1. **Customer Reports Issue**: License not working
2. **Check License Status**: Verify in admin panel
3. **Review Validation Logs**: Check for errors
4. **Test License**: Manual validation test
5. **Resolve Issue**: Update license or provide solution
6. **Follow Up**: Confirm resolution with customer

## Security Best Practices

### License Key Protection

- Store license keys encrypted in database
- Use HTTPS for all license communications
- Implement rate limiting on validation endpoints
- Log all validation attempts with IP tracking

### Validation Security

```php
// Implement validation rate limiting
$rateLimitKey = "license_validation_" . $_SERVER['REMOTE_ADDR'];
$attempts = getCache($rateLimitKey) ?: 0;

if ($attempts > 10) { // Max 10 attempts per hour
    http_response_code(429);
    die('Rate limit exceeded');
}

setCache($rateLimitKey, $attempts + 1, 3600);
```

### Code Protection

- Obfuscate license validation code
- Use encrypted license server communications
- Implement multiple validation checkpoints
- Regular license verification (not just startup)

## Distribution Checklist

### Pre-Distribution

- [ ] Test installation on fresh server
- [ ] Verify license validation works
- [ ] Test white-label features
- [ ] Create comprehensive documentation
- [ ] Set up license server (if using remote validation)
- [ ] Prepare CodeCanyon listing

### Post-Distribution

- [ ] Monitor license activations
- [ ] Provide customer support
- [ ] Handle license transfers
- [ ] Maintain license server
- [ ] Regular security updates

## Support Contact

For licensing system support:
- **Documentation**: This guide and README.md
- **Technical Support**: [Your support email]
- **License Issues**: [Your license support email]
- **Emergency Contact**: [Emergency contact for critical issues]

---

**Orderlyy Licensing System v1.0.0**  
Professional Order Management System  
© 2025 Orderlyy. All rights reserved.