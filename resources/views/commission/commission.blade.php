@extends('layouts.app')
@section('title', $page_title)
@section('content')
    <div id="app">
        <commission-component :excel-access="{{ $employees['excelAccess'] ?? 0 }}"></commission-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
