@extends('layouts.app')
@section('title', $page_title)
@section('content')
    <div id="app">
        <add-commission-component></add-commission-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
