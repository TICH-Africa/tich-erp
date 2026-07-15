<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    private string $key;

    public function __construct()
    {
        $this->key = config('app.key');
    }

    public function encrypt(string $plaintext): string
    {
        return Crypt::encryptString($plaintext);
    }

    public function decrypt(string $ciphertext): string
    {
        return Crypt::decryptString($ciphertext);
    }

    public function encryptArray(array $data): string
    {
        return $this->encrypt(json_encode($data));
    }

    public function decryptArray(string $ciphertext): array
    {
        return json_decode($this->decrypt($ciphertext), true);
    }

    public function hashPII(string $value): string
    {
        $salt = config('tich.pii_salt', env('PII_SALT', 'tich-erp-pii-salt-2026'));
        return hash_hmac('sha256', strtolower(trim($value)), $salt);
    }

    public function verifyPII(string $plaintext, string $storedHash): bool
    {
        return hash_equals($storedHash, $this->hashPII($plaintext));
    }

    public function encryptAmount(string $amount): string
    {
        return $this->encrypt($amount);
    }
}