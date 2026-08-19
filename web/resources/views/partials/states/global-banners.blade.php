<div id="tich-offline-banner" class="tich-global-banner tich-global-banner--offline" role="alert" aria-live="assertive">
    <svg viewBox="0 0 24 24"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
    <span>You are offline. Some features may not be available.</span>
</div>

<div id="tich-slow-banner" class="tich-global-banner tich-global-banner--slow" role="status">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <span>Slow connection detected. Pages may load slowly.</span>
</div>

<div id="tich-session-banner" class="tich-global-banner tich-global-banner--session" role="alert" aria-live="assertive">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/><path d="M12 17v.01"/></svg>
    <span>Your session has expired. <a href="{{ route('login') }}" style="color:inherit;text-decoration:underline;font-weight:600;">Sign in again</a></span>
</div>
