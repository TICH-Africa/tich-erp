<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;

class ErrorNavigationService
{
    public function __construct(
        protected AuthService $auth,
        protected StaffPortalService $staffPortal,
    ) {}

    public function homeUrl(?Authenticatable $user = null): string
    {
        $user ??= auth()->user();

        if (! $user) {
            return route('home');
        }

        return $this->auth->authenticatedHome($user);
    }

    public function homeLabel(?Authenticatable $user = null): string
    {
        $user ??= auth()->user();

        if (! $user) {
            return 'Go to homepage';
        }

        if ($this->auth->isEnrolledStudent($user)) {
            return 'Open student portal';
        }

        if ($this->staffPortal->isTeachingStaff($user)) {
            return 'Open staff portal';
        }

        return 'Go to dashboard';
    }

    /**
     * @return list<array{label: string, url: string, primary?: bool}>
     */
    public function actions(?Authenticatable $user = null): array
    {
        $user ??= auth()->user();

        $actions = [
            [
                'label' => $this->homeLabel($user),
                'url' => $this->homeUrl($user),
                'primary' => true,
            ],
        ];

        if (! $user) {
            $actions[] = [
                'label' => 'Sign in',
                'url' => route('login'),
                'primary' => false,
            ];
        }

        return $actions;
    }
}
