@extends('layouts.print-document')

@section('document-content')
    @php($tableClass = 'tich-doc-table')

    @include('finance.reports.partials.' . str_replace('_', '-', $report), [
        'data' => $reportData,
        'tableClass' => $tableClass,
    ])
@endsection
