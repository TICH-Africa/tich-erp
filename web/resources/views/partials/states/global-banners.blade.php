<div class="tich-toast-stack tich-toast-stack--system" id="tich-system-toasts" aria-live="polite">
    <div
        id="tich-offline-banner"
        class="tich-toast tich-toast--warning tich-system-toast"
        role="alert"
        aria-live="assertive"
        hidden
    >
        <div class="tich-toast__content">
            <p class="tich-toast__message">You are offline</p>
            <p class="tich-toast__detail">Some features may not be available until your connection returns.</p>
        </div>
        <button type="button" class="tich-toast__close" data-system-toast-dismiss aria-label="Dismiss notification">&times;</button>
    </div>

    <div
        id="tich-slow-banner"
        class="tich-toast tich-toast--warning tich-system-toast"
        role="status"
        hidden
    >
        <div class="tich-toast__content">
            <p class="tich-toast__message">Slow connection detected</p>
            <p class="tich-toast__detail">Pages may load slowly.</p>
        </div>
        <button type="button" class="tich-toast__close" data-system-toast-dismiss aria-label="Dismiss notification">&times;</button>
    </div>

    <div
        id="tich-session-banner"
        class="tich-toast tich-toast--error tich-system-toast"
        role="alert"
        aria-live="assertive"
        hidden
    >
        <div class="tich-toast__content">
            <p class="tich-toast__message">Your session has expired</p>
            <p class="tich-toast__detail">
                <a href="{{ route('login') }}" class="tich-toast__link">Sign in again</a>
            </p>
        </div>
        <button type="button" class="tich-toast__close" data-system-toast-dismiss aria-label="Dismiss notification">&times;</button>
    </div>
</div>
