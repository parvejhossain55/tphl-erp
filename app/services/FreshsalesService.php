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
}
