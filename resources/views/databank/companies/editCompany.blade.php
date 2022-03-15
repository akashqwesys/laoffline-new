@extends('layouts.app')

@section('content')
    <div id="app">
        <create-company-component scope="{{ $employees['scope'] }}" :id="{{ $employees['editedId'] ?? 0 }}"></create-company-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>    
@endsection