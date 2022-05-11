@extends('layouts.app')

@section('content')
    <div id="app">
        <register-component :excel-access="{{ $employees['excelAccess'] ?? 0 }}"></register-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
