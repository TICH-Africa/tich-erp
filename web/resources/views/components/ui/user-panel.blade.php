@props([
    'user' => null,
    'showNotifications' => true,
    'logoutUrl' => null,
])

@php
$name = 'User';

if ($user) {
    if (method_exists($user, 'fullName')) {
        $name = $user->fullName();
    } elseif (!empty($user->name)) {
        $name = $user->name;
    } elseif (!empty($user->first_name) || !empty($user->surname)) {
        $name = trim(($user->first_name ?? '') . ' ' . ($user->surname ?? ''));
    } elseif (!empty($user->email)) {
        $name = explode('@', $user->email)[0];
    }
}
@endphp

<div class="flex items-center gap-3">
    <span class="text-sm font-medium text-gray-700">{{ $name }}</span>

    @if($logoutUrl)
        <form method="POST" action="{{ $logoutUrl }}" onsubmit="return confirm('Sign out?')">
            @csrf
            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-gray-300 rounded-md hover:bg-red-50 hover:border-red-200 transition-colors">
                Sign out
            </button>
        </form>
    @endif
</div>
