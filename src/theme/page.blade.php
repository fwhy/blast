@extends('__layout')

@if($page->slug === '/' && !$page->fullPath)
@section('title')Index | {{$config['site']['name']}}@endsection
@section('content')
    @include('_index', ['pages' => $pages, 'config' => $config])
@endsection
@else
@section('title'){{$page->title}} | {{$config['site']['name']}}@endsection
@section('content'){!! $page->content !!}@endsection
@endif
