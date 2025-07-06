<?php
/**
 * Orderlyy License System
 * Professional license validation and management for CodeCanyon distribution
 */

class OrderlyyLicense {
    private $pdo;
    private $licenseServer = 'https://api.orderlyy.com/license/'; // Your license server URL
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a new license key
     */
    public function generateLicenseKey() {
        return strtoupper(bin2hex(random_bytes(16)));
    }
    
    /**
     * Validate license key against domain
     */
    public function validateLicense($licenseKey, $domain, $checkRemote = true) {
        try {
            // Remove www. and protocols from domain
            $domain = $this->cleanDomain($domain);
            
            // Check local database first
            $stmt = $this->pdo->prepare("
                SELECT * FROM licenses 
                WHERE license_key = ? AND status = 'active' 
                AND (expiry_date IS NULL OR expiry_date > NOW())
            ");
            $stmt->execute([$licenseKey]);
            $license = $stmt->fetch();
            
            if (!$license) {
                $this->logValidation($licenseKey, $domain, 'invalid', 'License not found or expired');
                return ['valid' => false, 'error' => 'Invalid or expired license'];
            }
            
            // Check domain restriction
            if ($license['domain'] && $license['domain'] !== $domain) {
                $this->logValidation($licenseKey, $domain, 'domain_mismatch', 'Domain mismatch');
                return ['valid' => false, 'error' => 'License not valid for this domain'];
            }
            
            // Remote validation (optional, for additional security)
            if ($checkRemote && $this->licenseServer) {
                $remoteValidation = $this->validateRemote($licenseKey, $domain);
                if (!$remoteValidation['valid']) {
                    return $remoteValidation;
                }
            }
            
            // Update last validation
            $stmt = $this->pdo->prepare("UPDATE licenses SET updated_at = NOW() WHERE license_key = ?");
            $stmt->execute([$licenseKey]);
            
            $this->logValidation($licenseKey, $domain, 'valid', 'License validated successfully');
            
            return [
                'valid' => true,
                'license' => $license,
                'features' => json_decode($license['features'], true) ?: []
            ];
            
        } catch (Exception $e) {
            $this->logValidation($licenseKey, $domain, 'error', $e->getMessage());
            return ['valid' => false, 'error' => 'Validation error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Activate license for a domain
     */
    public function activateLicense($licenseKey, $domain, $customerData = []) {
        try {
            $domain = $this->cleanDomain($domain);
            
            // Check if license exists and is inactive
            $stmt = $this->pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
            $stmt->execute([$licenseKey]);
            $license = $stmt->fetch();
            
            if (!$license) {
                return ['success' => false, 'error' => 'License key not found'];
            }
            
            if ($license['status'] === 'active' && $license['domain'] && $license['domain'] !== $domain) {
                return ['success' => false, 'error' => 'License already activated for another domain'];
            }
            
            // Activate license
            $stmt = $this->pdo->prepare("
                UPDATE licenses 
                SET domain = ?, status = 'active', activation_date = NOW(), updated_at = NOW(),
                    customer_email = ?, customer_name = ?
                WHERE license_key = ?
            ");
            $stmt->execute([
                $domain, 
                $customerData['email'] ?? $license['customer_email'],
                $customerData['name'] ?? $license['customer_name'],
                $licenseKey
            ]);
            
            return ['success' => true, 'message' => 'License activated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Activation error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create new license
     */
    public function createLicense($customerData, $features = []) {
        try {
            $licenseKey = $this->generateLicenseKey();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO licenses (license_key, customer_email, customer_name, features, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $licenseKey,
                $customerData['email'],
                $customerData['name'],
                json_encode($features)
            ]);
            
            return ['success' => true, 'license_key' => $licenseKey];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'License creation error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get license information
     */
    public function getLicenseInfo($licenseKey) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
            $stmt->execute([$licenseKey]);
            $license = $stmt->fetch();
            
            if (!$license) {
                return ['found' => false, 'error' => 'License not found'];
            }
            
            return [
                'found' => true,
                'license' => $license,
                'features' => json_decode($license['features'], true) ?: []
            ];
            
        } catch (Exception $e) {
            return ['found' => false, 'error' => 'Error retrieving license: ' . $e->getMessage()];
        }
    }
    
    /**
     * Deactivate license
     */
    public function deactivateLicense($licenseKey) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE licenses 
                SET status = 'inactive', domain = NULL, updated_at = NOW()
                WHERE license_key = ?
            ");
            $stmt->execute([$licenseKey]);
            
            return ['success' => true, 'message' => 'License deactivated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Deactivation error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if feature is available for license
     */
    public function hasFeature($licenseKey, $feature) {
        $validation = $this->validateLicense($licenseKey, $_SERVER['HTTP_HOST'], false);
        
        if (!$validation['valid']) {
            return false;
        }
        
        return isset($validation['features'][$feature]) && $validation['features'][$feature];
    }
    
    /**
     * Clean domain name
     */
    private function cleanDomain($domain) {
        $domain = strtolower($domain);
        $domain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain);
        $domain = rtrim($domain, '/');
        return $domain;
    }
    
    /**
     * Log validation attempt
     */
    private function logValidation($licenseKey, $domain, $result, $message = '') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO license_validations (license_key, domain, ip_address, validation_result, error_message, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $licenseKey,
                $domain,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $result,
                $message,
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Log validation errors silently
            error_log("License validation logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Remote license validation (optional)
     */
    private function validateRemote($licenseKey, $domain) {
        if (!$this->licenseServer) {
            return ['valid' => true]; // Skip remote validation if no server configured
        }
        
        try {
            $postData = [
                'license_key' => $licenseKey,
                'domain' => $domain,
                'action' => 'validate'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->licenseServer . 'validate');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                return $result ?: ['valid' => false, 'error' => 'Invalid server response'];
            }
            
            return ['valid' => false, 'error' => 'Remote validation failed'];
            
        } catch (Exception $e) {
            // If remote validation fails, continue with local validation
            return ['valid' => true];
        }
    }
}

// Global license check function
function checkOrderlyyLicense() {
    global $pdo;
    
    // Skip license check for installer
    if (basename($_SERVER['PHP_SELF']) === 'install.php') {
        return true;
    }
    
    try {
        $licenseSystem = new OrderlyyLicense($pdo);
        
        // Get license key from config or database
        $licenseKey = defined('ORDERLYY_LICENSE_KEY') ? ORDERLYY_LICENSE_KEY : null;
        
        if (!$licenseKey) {
            // Try to get from branding settings
            $stmt = $pdo->prepare("SELECT setting_value FROM branding_settings WHERE setting_key = 'license_key'");
            $stmt->execute();
            $licenseKey = $stmt->fetchColumn();
        }
        
        if (!$licenseKey) {
            return false; // No license key found
        }
        
        $validation = $licenseSystem->validateLicense($licenseKey, $_SERVER['HTTP_HOST']);
        return $validation['valid'];
        
    } catch (Exception $e) {
        error_log("License check error: " . $e->getMessage());
        return false;
    }
}

// License enforcement function
function enforceLicense() {
    if (!checkOrderlyyLicense()) {
        // Redirect to license activation page or show error
        if (file_exists('license_activation.php')) {
            header('Location: license_activation.php');
        } else {
            die('Invalid or missing license. Please contact support.');
        }
        exit();
    }
}
?>