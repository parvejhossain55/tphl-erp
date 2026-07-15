<?php

/**
 * FreshsalesService
 *
 * Webhook signature verification and data mapper for Freshsales leads.
 */
class FreshsalesService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Verify incoming webhook HMAC signature for security.
     */
    public function verifySignature(string $rawPayload, string $receivedSig): bool
    {
        $secret = $this->config['freshsales_webhook_secret'] ?? '';
        if (empty($secret)) {
            return true; // Dev bypass
        }
        // Support direct comparison for static secret (Custom Headers setup)
        if ($receivedSig === $secret) {
            return true;
        }
        $expected = hash_hmac('sha256', $rawPayload, $secret);
        return hash_equals($expected, $receivedSig);
    }

    /**
     * Map Freshsales lead source name to standard platform name
     */
    public function mapPlatform(?string $sourceName): string
    {
        if (empty($sourceName)) {
            return 'other';
        }
        $s = strtolower(trim($sourceName));

        if (str_contains($s, 'facebook') || str_contains($s, ' fb ') || $s === 'fb') {
            return 'facebook';
        }
        if (str_contains($s, 'instagram') || str_contains($s, 'ig')) {
            return 'instagram';
        }
        if (str_contains($s, 'whatsapp') || str_contains($s, 'wa')) {
            return 'whatsapp';
        }
        if (str_contains($s, 'google') || str_contains($s, 'google ads')) {
            return 'google';
        }
        if (str_contains($s, 'youtube')) {
            return 'youtube';
        }
        return $s;
    }

    /**
     * Update a contact in Freshsales via CRM API
     */
    public function updateContact(string $contactId, array $data): bool
    {
        $apiKey = $this->config['freshsales_api_key'] ?? '';
        $domain = $this->config['freshsales_domain'] ?? '';
        if (empty($apiKey) || empty($domain)) {
            return false;
        }

        // Freshsales standard API endpoint
        $url = "https://{$domain}.myfreshworks.com/crm/sales/api/contacts/{$contactId}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['contact' => $data]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Token token={$apiKey}",
            "Content-Type: application/json"
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $status === 200 || $status === 201;
    }
}
