@extends('extends/intermediate')

@section('title', 'This is the title')

@push('head')
<link rel="stylesheet" href="/css/child.css" type="text/css" media="screen">
@endpush

@push("scripts")
<script src="js/child-scripts.js"></script>
@endpush

@section("sidebar")
<ul>
    <li>This is the</li>
    <li>Sidebar section</li>
    <li>Populated from the</li>
    <li>Child template</li>
</ul>
@endsection

@section('static')    
    @parent
    <p>This content comes from child.blade.php</p>
@endsection

