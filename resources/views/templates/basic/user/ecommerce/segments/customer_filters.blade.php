@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    /**
     * Read filter values from query string and apply them on the
     * already prepared RFM dataset coming from the controller.
     */
    $selectedProvider = request()->get('provider', '');
    $minOrders = (int) request()->get('min_orders', 1);

    $filteredSegments = collect($segments)
        ->filter(function ($segment) use ($selectedProvider, $minOrders) {
            $providerMatch = $selectedProvider ? (($segment['provider'] ?? '') === $selectedProvider) : true;
            $ordersMatch = ($segment['orders'] ?? 0) >= $minOrders;

            return $providerMatch && $ordersMatch;
        })
        ->values();

    $availableProviders = collect($segments)
        ->pluck('provider')
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="dashboard-container">

    <div class="container-top">
        <div class="container-top__left w-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="container-top__title">@lang('Customer Filters')</h5>
                    <p class="container-top__desc">
                        @lang('Filter customers by provider and minimum order count.')
                    </p>
                </div>

                <a href="{{ route('user.ecommerce.rfm.export', 'all') }}" class="btn btn--dark">
                    <i class="las la-download"></i> @lang('Export Contacts')
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard-container__body">

        {{-- Filter bar --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('user.ecommerce.segments.customer.filters') }}" class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label fw-semibold">@lang('Provider')</label>
                        <select name="provider" class="form--control">
                            <option value="">@lang('All Providers')</option>
                            @foreach($availableProviders as $provider)
                                <option value="{{ $provider }}" @selected($selectedProvider === $provider)>
                                    {{ ucfirst($provider) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <label class="form-label fw-semibold">@lang('Minimum Orders')</label>
                        <input type="number" min="1" step="1" name="min_orders" value="{{ $minOrders }}" class="form--control">
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <button type="submit" class="btn btn--base">
                            <i class="las la-filter"></i> @lang('Apply Filter')
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results table --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">
                    @lang('Filtered Customers')
                    <span class="text-muted">({{ $filteredSegments->count() }})</span>
                </h6>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 buyers-table">
                        <thead>
                            <tr>
                                <th>@lang('Customer')</th>
                                <th>@lang('Phone')</th>
                                <th>@lang('Provider')</th>
                                <th>@lang('Last Order #')</th>
                                <th>@lang('Last Order Date')</th>
                                <th>@lang('Orders')</th>
                                <th>@lang('Spent')</th>
                                <th>@lang('Days Since Last Order')</th>
                                <th>@lang('Segment')</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($filteredSegments as $segment)
                                @php
                                    $badgeClass = match ($segment['segment']) {
                                        'VIP' => 'bg-success',
                                        'Loyal' => 'bg-primary',
                                        'New' => 'bg-info',
                                        'At Risk' => 'bg-warning',
                                        'Lost' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp

                                <tr>
                                    <td class="fw-semibold">{{ $segment['customer_name'] ?? '-' }}</td>
                                    <td>{{ $segment['phone'] ?? '-' }}</td>
                                    <td>{{ $segment['provider'] ?? '-' }}</td>
                                    <td>{{ $segment['last_order_number'] ?? '-' }}</td>
                                    <td>{{ $segment['last_order_at'] ?? '-' }}</td>
                                    <td>{{ $segment['orders'] }}</td>
                                    <td>{{ number_format((float) $segment['spent'], 2) }}</td>
                                    <td>{{ $segment['days_since_last_order'] }}</td>
                                    <td>
                                        <span class="badge buyers-segment-badge {{ $badgeClass }}">
                                            {{ $segment['segment'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        @lang('No customers found for the selected filters')
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('style')
<style>
    .buyers-segment-badge {
        color: #fff !important;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 999px;
        min-width: 88px;
        display: inline-block;
        text-align: center;
    }

    .buyers-table td,
    .buyers-table th {
        white-space: normal;
        vertical-align: middle;
    }
</style>
@endpush
