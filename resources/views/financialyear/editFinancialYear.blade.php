@extends('layouts.app')
@section('title', $page_title)
@section('content')
    <div id="app">
        <create-financial-year-component scope="{{ $employees['scope'] }}" :id="{{ $employees['editedId'] ?? 0 }}"></create-financial-year-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
