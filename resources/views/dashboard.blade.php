@extends('layouts.app')

@section('title', 'Dashboard | Aqualytics')
@section('header-title', 'Dashboard')

@section('content')
    <div data-cy="dashboard-context" data-dashboard-context="{{ $dashboardContext }}">
        @include('dashboard.'.str_replace('_', '-', $dashboardContext))
    </div>
@endsection
