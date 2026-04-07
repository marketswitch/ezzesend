@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-container">

    <div class="container-top">
        <div class="container-top__left w-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="container-top__title">@lang('All Buyers')</h5>
                    <p class="container-top__desc">
                        @lang('All customers who made at least one purchase.')
                    </p>
                </div>

                <a href="{{ route('user.ecommerce.rfm.export', 'all') }}" class="btn btn--dark">
                    <i class="las la-download"></i> @lang('Export Contacts')
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard-container__body">

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">@lang('Buyers List')</h6>
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
                            @forelse($segments as $segment)
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
                                        <span class="badge buyers-segment-badge bg-secondary">
                                            {{ $segment['segment'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        @lang('No buyers found')
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
    /* Improve segment badge visibility inside buyers table */
    .buyers-segment-badge {
        color: #fff !important;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 999px;
        min-width: 88px;
        display: inline-block;
        text-align: center;
    }

    .buyers-segment-badge.bg-secondary,
    .buyers-segment-badge.bg-info,
    .buyers-segment-badge.bg-primary,
    .buyers-segment-badge.bg-success,
    .buyers-segment-badge.bg-warning,
    .buyers-segment-badge.bg-danger {
        color: #fff !important;
    }

    .buyers-table td,
    .buyers-table th {
        white-space: normal;
        vertical-align: middle;
    }
</style>
@endpush

