@extends('layouts.app')
@section('title', $page_title)
@section('content')
    <div id="app">
        <add-good-return-component></add-good-return-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
