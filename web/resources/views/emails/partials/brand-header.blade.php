{{-- Uploaded site logo as the email brand mark (embedded when sending). --}}
@php
    $brand = $emailBrand ?? [];
    $logoPath = $brand['logo_path'] ?? null;
    $logoSrc = null;

    if ($logoPath && isset($message) && is_object($message) && method_exists($message, 'embed')) {
        try {
            $logoSrc = $message->embed($logoPath);
        } catch (\Throwable) {
            $logoSrc = null;
        }
    }

    $logoSrc = $logoSrc ?: ($brand['logo_url'] ?? asset('images/logo.png'));
    $shortName = $brand['short_name'] ?? 'TICH in Africa';
    $institution = $brand['institution_name'] ?? $shortName;
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;">
    <tr>
        <td align="left" style="vertical-align:middle;">
            <img
                src="{{ $logoSrc }}"
                alt="{{ $shortName }}"
                width="64"
                height="64"
                style="display:block;width:64px;height:64px;object-fit:contain;border:0;border-radius:8px;"
            >
        </td>
        <td align="left" style="padding-left:14px;vertical-align:middle;">
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:#1669a6;line-height:1.3;">
                {{ $shortName }}
            </p>
            <p style="margin:4px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#6b6e72;line-height:1.4;">
                {{ $institution }}
            </p>
        </td>
    </tr>
</table>
