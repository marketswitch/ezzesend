@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    /**
     * Build customer segment counters using the RFM dataset
     * passed from the controller.
     */
    $buyersCount = $segments->count();
    $repeatCount = $segments->where('orders', '>=', 2)->count();
    $highValueCount = $segments->where('spent', '>', 300)->count();
    $newCount = $segments->where('segment', 'New')->count();
@endphp

<div class="dashboard-container">

    <div class="container-top">
        <div class="container-top__left">
            <h5 class="container-top__title">@lang('Customer Segments')</h5>
            <p class="container-top__desc">
                @lang('Group customers based on purchase behavior, activity, and value.')
            </p>
        </div>
    </div>

    <div class="dashboard-container__body">
        <div class="row g-3">

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6>@lang('All Buyers')</h6>
                        <h3>{{ $buyersCount }}</h3>
                        <a href="{{ route('user.ecommerce.segments.buyers') }}" class="btn btn-sm btn--base mt-2">
                            @lang('View')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6>@lang('Repeat Customers')</h6>
                        <h3>{{ $repeatCount }}</h3>
                        <a href="{{ route('user.ecommerce.segments.repeat.customers') }}" class="btn btn-sm btn--base mt-2">
                            @lang('View')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6>@lang('High Value')</h6>
                        <h3>{{ $highValueCount }}</h3>
                        <a href="{{ route('user.ecommerce.segments.high.value.customers') }}" class="btn btn-sm btn--base mt-2">
                            @lang('View')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6>@lang('New Customers')</h6>
                        <h3>{{ $newCount }}</h3>
                        <a href="{{ route('user.ecommerce.segments.buyers') }}" class="btn btn-sm btn--base mt-2">
                            @lang('View')
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
