<?php

return [

    'genesis_hash' => '0000000000000000000000000000000000000000000000000000000000000000',

    'geo_lookup_enabled' => env('AUDIT_GEO_LOOKUP_ENABLED', true),

    'sensitive_keys' => [
        'password', 'password_hash', 'password_confirmation',
        'mfa_secret', 'mfa_secret_temp', 'mfa_backup_codes',
        'token', 'remember_token', 'code', 'otp', 'verification_code',
        'secret', 'backup_codes', 'plainTextToken',
    ],

    'actions' => [
        'auth.login.success' => ['module' => 'auth', 'sensitive' => false],
        'auth.login.failed' => ['module' => 'auth', 'sensitive' => false],
        'auth.login.locked' => ['module' => 'auth', 'sensitive' => false],
        'auth.logout' => ['module' => 'auth', 'sensitive' => false],
        'auth.register' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.setup_started' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.otp_sent' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.verify.success' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.verify.failed' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.enabled' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.disabled' => ['module' => 'auth', 'sensitive' => true],
        'auth.mfa.backup_used' => ['module' => 'auth', 'sensitive' => false],
        'rbac.role.assigned' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.role.revoked' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.permission.assigned' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.permission.revoked' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.role.permissions_synced' => ['module' => 'rbac', 'sensitive' => false],
        'access.denied' => ['module' => 'security', 'sensitive' => false],
        'audit.view' => ['module' => 'audit', 'sensitive' => false],
        'audit.export' => ['module' => 'audit', 'sensitive' => false],
    ],

];
