@extends('layouts.app')
@section('title', $page_title)
@section('content')
    <div id="app">
        <create-company-component></create-company-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
