<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    // AES-256-CBC encryption key from Laravel app.key
    private string $key;

    public function __construct()
    {
        $this->key = config('app.key');
    }

    // Encrypt plaintext using Laravel's Crypt facade
    public function encrypt(string $plaintext): string
    {
        return Crypt::encryptString($plaintext);
    }

    // Decrypt ciphertext back to plaintext
    public function decrypt(string $ciphertext): string
    {
        return Crypt::decryptString($ciphertext);
    }

    // Encrypt an array by JSON encoding then encrypting
    public function encryptArray(array $data): string
    {
        return $this->encrypt(json_encode($data));
    }

    // Decrypt and JSON decode to array
    public function decryptArray(string $ciphertext): array
    {
        return json_decode($this->decrypt($ciphertext), true);
    }

    // Generate deterministic HMAC-SHA256 hash for PII values
    // Uses configurable salt for consistent hashing across the system
    public function hashPII(string $value): string
    {
        $salt = config('tich.pii_salt', env('PII_SALT', 'tich-erp-pii-salt-2026'));
        return hash_hmac('sha256', strtolower(trim($value)), $salt);
    }

    // Verify a plaintext value against a stored PII hash
    public function verifyPII(string $plaintext, string $storedHash): bool
    {
        return hash_equals($storedHash, $this->hashPII($plaintext));
    }

    // Encrypt monetary amounts
    public function encryptAmount(string $amount): string
    {
        return $this->encrypt($amount);
    }
}