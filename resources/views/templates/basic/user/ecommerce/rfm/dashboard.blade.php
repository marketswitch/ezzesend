@extends($activeTemplate . 'layouts.master')

@section('content')
    @php
        /**
         * RFM summary counters for dashboard cards.
         */
        $vipCount = $segments->where('segment', 'VIP')->count();
        $loyalCount = $segments->where('segment', 'Loyal')->count();
        $newCount = $segments->where('segment', 'New')->count();
        $atRiskCount = $segments->where('segment', 'At Risk')->count();
        $lostCount = $segments->where('segment', 'Lost')->count();
        $totalCount = $segments->count();
    @endphp

    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left w-100">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="container-top__title mb-2">@lang('RFM Dashboard')</h5>
                        <p class="container-top__desc mb-0">
                            @lang('Customer segmentation based on recency, frequency, and monetary value.')
                        </p>
                    </div>

                    {{-- Export all available RFM contacts using the import-compatible CSV format --}}
                    <div class="text-end">
                        <a href="{{ route('user.ecommerce.rfm.export', 'all') }}" class="btn btn--dark btn-shadow">
                            <i class="las la-file-export"></i> @lang('Export All Contacts')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-container__body">
            {{-- Summary cards --}}
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small mb-2">@lang('Total')</div>
                            <h3 class="mb-0">{{ $totalCount }}</h3>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small mb-2">@lang('VIP')</div>
                            <h3 class="mb-3">{{ $vipCount }}</h3>

                            {{-- Export VIP customers using import-compatible CSV format --}}
                            <a href="{{ route('user.ecommerce.rfm.export', 'VIP') }}" class="btn btn-sm btn-success w-100">
                                <i class="las la-download"></i> @lang('Export Contacts')
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small mb-2">@lang('Loyal')</div>
                            <h3 class="mb-3">{{ $loyalCount }}</h3>

                            {{-- Export loyal customers using import-compatible CSV format --}}
                            <a href="{{ route('user.ecommerce.rfm.export', 'Loyal') }}" class="btn btn-sm btn-primary w-100">
                                <i class="las la-download"></i> @lang('Export Contacts')
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small mb-2">@lang('New')</div>
                            <h3 class="mb-3">{{ $newCount }}</h3>

                            {{-- Export new customers using import-compatible CSV format --}}
                            <a href="{{ route('user.ecommerce.rfm.export', 'New') }}" class="btn btn-sm btn-info w-100">
                                <i class="las la-download"></i> @lang('Export Contacts')
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small mb-2">@lang('At Risk')</div>
                            <h3 class="mb-0">{{ $atRiskCount }}</h3>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small mb-2">@lang('Lost')</div>
                            <h3 class="mb-0">{{ $lostCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RFM detail table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">@lang('RFM Customer Segments')</h6>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
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
                                @forelse($segments as $segment)
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
                                        <td class="fw-semibold">{{ $segment['customer_name'] ?? ('#' . $segment['customer_id']) }}</td>
                                        <td>{{ $segment['phone'] ?? '-' }}</td>
                                        <td>{{ $segment['provider'] ?? '-' }}</td>
                                        <td>{{ $segment['last_order_number'] ?? '-' }}</td>
                                        <td>{{ $segment['last_order_at'] ?? '-' }}</td>
                                        <td>{{ $segment['orders'] }}</td>
                                        <td>{{ number_format((float) $segment['spent'], 2) }}</td>
                                        <td>{{ $segment['days_since_last_order'] }}</td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $segment['segment'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            @lang('No RFM data found')
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
