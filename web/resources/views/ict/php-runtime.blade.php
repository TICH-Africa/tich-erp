@extends('layouts.ict')

@section('title', 'PHP runtime check')

@section('ict-content')
    <x-page-toolbar
        title="PHP runtime check"
        meta="Web PHP used by this page (not CLI). Use this to confirm spreadsheet import dependencies."
    />

    <div class="tich-card tich-mt-4">
        <p class="tich-caption">PHP {{ $phpVersion }} · SAPI {{ $sapi }}</p>
        <p class="tich-caption tich-mt-2">Loaded php.ini: <code>{{ $iniFile }}</code></p>

        @if ($xlsxReady)
            <div class="tich-alert tich-alert--success tich-mt-4" role="status">
                <strong>Ready for .xlsx import.</strong> zip and xml look available on the web runtime.
            </div>
        @else
            <div class="tich-alert tich-alert--warning tich-mt-4" role="status">
                <strong>.xlsx import will fail on this server</strong> until the missing items below are installed for the <em>web</em> PHP (FPM/Apache), then PHP is restarted.
            </div>
        @endif

        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Check</th>
                    <th>Status</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($checks as $check)
                    <tr>
                        <td>{{ $check['label'] }}</td>
                        <td>
                            @if ($check['ok'])
                                <span class="tich-status-badge is-success">OK</span>
                            @else
                                <span class="tich-status-badge is-danger">Missing</span>
                            @endif
                        </td>
                        <td>{{ $check['detail'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="tich-mt-6">
            <h2 class="tich-h3">If something is Missing (Ubuntu/Debian)</h2>
            <pre class="tich-caption tich-mt-2" style="white-space:pre-wrap;background:var(--tich-surface-muted,#f5f6f6);padding:1rem;border-radius:8px;">sudo apt update
sudo apt install {{ $phpPkg }}-zip {{ $phpPkg }}-xml
sudo systemctl restart {{ $phpPkg }}-fpm
# or: sudo systemctl restart apache2</pre>
            <p class="tich-caption tich-mt-2">Then reload this page. All rows should show OK before Finance retries Chart of Accounts Excel upload.</p>
        </div>
    </div>
@endsection
