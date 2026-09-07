@php
    $separator = $separator ?? ' · ';
    $class = $class ?? 'tich-link';
@endphp
<a href="{{ route('privacy') }}" class="{{ $class }}" @if(!empty($newTab)) target="_blank" rel="noopener noreferrer" @endif>Privacy Policy</a>{{ $separator }}<a href="{{ route('terms') }}" class="{{ $class }}" @if(!empty($newTab)) target="_blank" rel="noopener noreferrer" @endif>Terms and Conditions</a>
