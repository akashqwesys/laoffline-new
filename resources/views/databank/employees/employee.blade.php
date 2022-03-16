@extends('layouts.app')
@section('title', $page_title)
@section('content')
    <div id="app">
        <employee-component :excel-access="{{ $employees['excelAccess'] ?? 0 }}"></employee-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
