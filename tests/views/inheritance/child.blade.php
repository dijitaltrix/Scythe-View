@extends('inheritance/parent')

@section('title', 'This is the title')

@section('sidebar')
    @parent
    <ul>
        <li>This is the</li>
        <li>Sidebar section</li>
        <li>Populated from the</li>
        <li>Child template</li>
    </ul>
@endsection