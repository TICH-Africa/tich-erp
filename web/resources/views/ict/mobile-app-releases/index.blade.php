@extends('layouts.ict')

@section('title', 'Mobile app releases')

@section('ict-content')
    <x-page-toolbar
        title="Mobile app releases"
        :meta="'Publish Android APK and iOS install links for staff and students. Public page: ' . $publicUrl"
    />

    <p class="tich-text tich-mt-2">
        Uploading here replaces the current live release for that platform. Android needs an <strong>APK</strong> for direct download (use a release APK, not Play-only AAB).
        For iOS, paste an <strong>App Store or TestFlight URL</strong>, or upload an IPA for enterprise distribution.
    </p>

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
        <section class="tich-card tich-card--pad">
            <h2 class="tich-h2">Android</h2>
            @if ($android)
                <p class="tich-caption tich-mt-2">
                    Live: v{{ $android->version_label }}
                    @if ($android->build_number) ({{ $android->build_number }}) @endif
                    — {{ $android->created_at?->format('j M Y, H:i') }}
                    @if ($android->formattedFileSize()) · {{ $android->formattedFileSize() }} @endif
                </p>
            @else
                <p class="tich-caption tich-mt-2">Nothing published.</p>
            @endif

            <form method="post" action="{{ route('ict.mobile-app.store') }}" enctype="multipart/form-data" class="tich-mt-4">
                @csrf
                <input type="hidden" name="platform" value="android">
                <div class="tich-mt-3">
                    <label class="tich-label" for="android-version">Version name</label>
                    <input id="android-version" class="tich-input" name="version_label" required placeholder="1.0.0" value="{{ old('platform') === 'android' ? old('version_label') : '' }}">
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="android-build">Build number (optional)</label>
                    <input id="android-build" class="tich-input" type="number" name="build_number" min="1" placeholder="1">
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="android-file">APK file</label>
                    <input id="android-file" class="tich-input" type="file" name="file" accept=".apk,application/vnd.android.package-archive" required>
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="android-play">Google Play URL (optional)</label>
                    <input id="android-play" class="tich-input" type="url" name="external_url" placeholder="https://play.google.com/store/apps/details?id=...">
                    <p class="tich-caption tich-mt-1">If set, the public page can show a Play Store button instead of or alongside APK.</p>
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="android-notes">Release notes (optional)</label>
                    <textarea id="android-notes" class="tich-input" name="release_notes" rows="3"></textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Publish Android</button>
            </form>
        </section>

        <section class="tich-card tich-card--pad">
            <h2 class="tich-h2">iOS</h2>
            @if ($ios)
                <p class="tich-caption tich-mt-2">
                    Live: v{{ $ios->version_label }}
                    @if ($ios->build_number) ({{ $ios->build_number }}) @endif
                    — {{ $ios->created_at?->format('j M Y, H:i') }}
                </p>
                @if ($ios->external_url)
                    <p class="tich-caption"><a href="{{ $ios->external_url }}" target="_blank" rel="noopener">{{ $ios->external_url }}</a></p>
                @endif
            @else
                <p class="tich-caption tich-mt-2">Nothing published.</p>
            @endif

            <form method="post" action="{{ route('ict.mobile-app.store') }}" enctype="multipart/form-data" class="tich-mt-4">
                @csrf
                <input type="hidden" name="platform" value="ios">
                <div class="tich-mt-3">
                    <label class="tich-label" for="ios-version">Version name</label>
                    <input id="ios-version" class="tich-input" name="version_label" required placeholder="1.0.0">
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="ios-build">Build number (optional)</label>
                    <input id="ios-build" class="tich-input" type="number" name="build_number" min="1" placeholder="1">
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="ios-url">App Store or TestFlight URL</label>
                    <input id="ios-url" class="tich-input" type="url" name="external_url" placeholder="https://apps.apple.com/... or https://testflight.apple.com/...">
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="ios-file">IPA file (optional)</label>
                    <input id="ios-file" class="tich-input" type="file" name="file" accept=".ipa,.zip">
                    <p class="tich-caption tich-mt-1">Required only for enterprise IPA sideload; most users should use the store link.</p>
                </div>
                <div class="tich-mt-3">
                    <label class="tich-label" for="ios-notes">Release notes (optional)</label>
                    <textarea id="ios-notes" class="tich-input" name="release_notes" rows="3"></textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Publish iOS</button>
            </form>
        </section>
    </div>

    @if ($history->isNotEmpty())
        <div class="tich-table-panel tich-mt-8">
            <h2 class="tich-h3">Recent uploads</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Version</th>
                            <th>Published</th>
                            <th>Uploaded</th>
                            <th>By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $row)
                            <tr>
                                <td>{{ ucfirst($row->platform) }}</td>
                                <td>{{ $row->version_label }} @if ($row->build_number) <span class="tich-caption">({{ $row->build_number }})</span> @endif</td>
                                <td>
                                    @if ($row->is_published)
                                        <span class="tich-badge tich-badge--success">Live</span>
                                    @else
                                        <span class="tich-badge tich-badge--neutral">Archived</span>
                                    @endif
                                </td>
                                <td>{{ $row->created_at?->format('j M Y, H:i') }}</td>
                                <td>{{ $row->uploader?->email ?? '—' }}</td>
                                <td>
                                    @if ($row->is_published)
                                        <form method="post" action="{{ route('ict.mobile-app.destroy', $row) }}" style="display:inline;" onsubmit="return confirm('Remove this release from the public download page?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tich-btn tich-btn-secondary tich-btn-sm">Unpublish</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection