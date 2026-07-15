<?php

namespace Tests\Unit;

use App\Services\EncryptionService;
use Tests\TestCase;

class TichSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $key = 'base64:' . base64_encode(random_bytes(32));
        config(['app.key' => $key]);
        config(['app.cipher' => 'AES-256-CBC']);
    }

    public function test_encrypt_and_decrypt_returns_original_value(): void
    {
        $svc = new EncryptionService();
        $plain = 'Gross salary: KES 185,000';

        $cipher = $svc->encrypt($plain);
        $decrypted = $svc->decrypt($cipher);

        $this->assertEquals($plain, $decrypted);
        $this->assertNotEquals($plain, $cipher);
    }

    public function test_encrypt_array_roundtrip(): void
    {
        $svc = new EncryptionService();
        $data = [
            'national_id' => '22987654',
            'kra_pin' => 'A000123456B',
            'bank_account' => '1234567890',
        ];

        $cipher = $svc->encryptArray($data);
        $decrypted = $svc->decryptArray($cipher);

        $this->assertEquals($data, $decrypted);
    }

    public function test_hash_pii_produces_deterministic_hash(): void
    {
        $svc = new EncryptionService();
        $id = '22987654';

        $hash1 = $svc->hashPII($id);
        $hash2 = $svc->hashPII($id);
        $hash3 = $svc->hashPII('DIFFERENT');

        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertEquals(64, strlen($hash1));
    }

    public function test_verify_pii_correctly_validates(): void
    {
        $svc = new EncryptionService();
        $id = '22987654';
        $hash = $svc->hashPII($id);

        $this->assertTrue($svc->verifyPII($id, $hash));
        $this->assertFalse($svc->verifyPII('WRONG_ID', $hash));
        $this->assertFalse($svc->verifyPII('', $hash));
    }

    public function test_encrypt_amount_works(): void
    {
        $svc = new EncryptionService();
        $cipher = $svc->encryptAmount('185000.00');
        $decrypted = $svc->decrypt($cipher);

        $this->assertEquals('185000.00', $decrypted);
    }
}