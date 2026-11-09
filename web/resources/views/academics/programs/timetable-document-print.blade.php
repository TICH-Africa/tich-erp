@extends('layouts.print-document')

@section('document-content')
    @if (empty($documentHtml))
        <p class="tich-doc-note">This uploaded timetable document cannot be shown here. Use the download button to open the original file.</p>
    @else
        <div class="tich-doc-uploaded">{!! $documentHtml !!}</div>
    @endif
@endsection
