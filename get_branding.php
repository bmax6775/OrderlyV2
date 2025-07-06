<?php
// Helper function to get branding settings
require_once 'config.php';

function getBrandingSettings() {
    global $pdo;
    
    $settings = [];
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM branding_settings");
        $stmt->execute();
        
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Set defaults if not found
        $defaults = [
            'app_name' => 'Orderlyy',
            'logo_url' => '',
            'primary_color' => '#ffc500',
            'secondary_color' => '#000000',
            'accent_color' => '#333333',
            'background_color' => '#ffffff',
            'card_background' => '#ffffff',
            'text_color' => '#212529',
            'success_color' => '#28a745',
            'warning_color' => '#ffc107',
            'danger_color' => '#dc3545',
            'info_color' => '#17a2b8',
            'tagline' => 'The go-to orderly solution for ecommerce teams',
            'footer_text' => 'Powered by Orderlyy - Professional Order Management'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }
        
    } catch (PDOException $e) {
        // Return defaults if table doesn't exist yet
        return $defaults;
    }
    
    return $settings;
}

function updateBrandingSetting($key, $value, $userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO branding_settings (setting_key, setting_value, updated_by, updated_at) 
            VALUES (?, ?, ?, NOW())
            ON CONFLICT (setting_key) 
            DO UPDATE SET setting_value = ?, updated_by = ?, updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $userId, $value, $userId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Get current branding settings
$branding = getBrandingSettings();
?>