@extends($activeTemplate . 'layouts.master')

@section('content')
    <style>
        .commerce-card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            border-radius: 12px;
        }

        .commerce-card .card-header {
            background: #f9fafb !important;
            border-bottom: 1px solid #ececec !important;
        }

        .commerce-table th,
        .commerce-table td {
            color: #222 !important;
            vertical-align: middle;
        }

        .commerce-muted {
            color: #666 !important;
            font-size: 13px;
        }

        .commerce-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
        }

        .commerce-badge-woo {
            background: #7f54b3 !important;
            color: #fff !important;
        }

        .commerce-badge-shopify {
            background: #16a34a !important;
            color: #fff !important;
        }

        .commerce-badge-vip {
            background: #111827 !important;
            color: #fff !important;
        }

        .commerce-badge-loyal {
            background: #2563eb !important;
            color: #fff !important;
        }

        .commerce-badge-new {
            background: #16a34a !important;
            color: #fff !important;
        }

        .commerce-badge-risk {
            background: #f59e0b !important;
            color: #fff !important;
        }

        .commerce-badge-lost {
            background: #dc2626 !important;
            color: #fff !important;
        }

        .commerce-stat-card {
            background: #fff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px;
            padding: 18px;
            height: 100%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        }

        .commerce-stat-label {
            font-size: 13px;
            color: #666 !important;
            margin-bottom: 6px;
        }

        .commerce-stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #111 !important;
            line-height: 1;
        }

        .commerce-filter-form .form--control,
        .commerce-filter-form .form-select {
            min-height: 46px;
        }

        .commerce-empty {
            padding: 32px 12px;
            text-align: center;
            color: #666 !important;
        }
    </style>

    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __($pageTitle) }}</h5>
                <p class="container-top__desc">
                    @lang('View all synced commerce customers from WooCommerce and Shopify in one place.')
                </p>
            </div>
        </div>

        <div class="dashboard-container__body">

            <div class="row gy-4 mb-4">
                <div class="col-md-4">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Total Customers')</div>
                        <div class="commerce-stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('WooCommerce Customers')</div>
                        <div class="commerce-stat-value">{{ $stats['woocommerce'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Shopify Customers')</div>
                        <div class="commerce-stat-value">{{ $stats['shopify'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="card commerce-card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">@lang('Filters')</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="commerce-filter-form">
                        <div class="row gy-3">
                            <div class="col-md-3">
                                <label class="form-label">@lang('Provider')</label>
                                <select name="provider" class="form-select form--control">
                                    <option value="">@lang('All Providers')</option>
                                    <option value="woocommerce" {{ request('provider') === 'woocommerce' ? 'selected' : '' }}>
                                        @lang('WooCommerce')
                                    </option>
                                    <option value="shopify" {{ request('provider') === 'shopify' ? 'selected' : '' }}>
                                        @lang('Shopify')
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">@lang('Segment')</label>
                                <select name="segment" class="form-select form--control">
                                    <option value="">@lang('All Segments')</option>
                                    <option value="VIP" {{ request('segment') === 'VIP' ? 'selected' : '' }}>VIP</option>
                                    <option value="Loyal" {{ request('segment') === 'Loyal' ? 'selected' : '' }}>Loyal</option>
                                    <option value="New" {{ request('segment') === 'New' ? 'selected' : '' }}>New</option>
                                    <option value="At Risk" {{ request('segment') === 'At Risk' ? 'selected' : '' }}>At Risk</option>
                                    <option value="Lost" {{ request('segment') === 'Lost' ? 'selected' : '' }}>Lost</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">@lang('Sort By')</label>
                                <select name="sort" class="form-select form--control">
                                    <option value="">@lang('Newest First')</option>
                                    <option value="orders_desc" {{ request('sort') === 'orders_desc' ? 'selected' : '' }}>
                                        @lang('Highest Orders')
                                    </option>
                                    <option value="spent_desc" {{ request('sort') === 'spent_desc' ? 'selected' : '' }}>
                                        @lang('Highest Spending')
                                    </option>
                                    <option value="last_order_desc" {{ request('sort') === 'last_order_desc' ? 'selected' : '' }}>
                                        @lang('Latest Order')
                                    </option>
                                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>
                                        @lang('Name A-Z')
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">@lang('Search')</label>
                                <input type="search"
                                       name="search"
                                       class="form--control"
                                       placeholder="@lang('Search name, phone, email, order...')"
                                       value="{{ request('search') }}">
                            </div>

                            <div class="col-md-12 d-flex justify-content-end gap-2">
                                <a href="{{ route('user.ecommerce.customers') }}" class="btn btn--dark">
                                    @lang('Reset')
                                </a>
                                <button type="submit" class="btn btn--base">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    @lang('Filter')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card commerce-card">
                <div class="card-header">
                    <h6 class="mb-0">@lang('Customers List')</h6>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table--responsive--lg commerce-table">
                            <thead>
                                <tr>
                                    <th>@lang('Customer')</th>
                                    <th>@lang('Provider')</th>
                                    <th>@lang('Segment')</th>
                                    <th>@lang('Phone')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Orders')</th>
                                    <th>@lang('Total Spent')</th>
                                    <th>@lang('Last Order')</th>
                                    <th>@lang('Last Order At')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    <tr>
                                        <td>
                                            <div>{{ $customer->customer_name ?: '-' }}</div>
                                            <div class="commerce-muted">#{{ $customer->id }}</div>
                                        </td>

                                        <td>
                                            @if($customer->provider === 'woocommerce')
                                                <span class="commerce-badge commerce-badge-woo">WooCommerce</span>
                                            @elseif($customer->provider === 'shopify')
                                                <span class="commerce-badge commerce-badge-shopify">Shopify</span>
                                            @else
                                                <span class="commerce-badge">{{ ucfirst($customer->provider ?? 'Unknown') }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            @php $seg = $customer->segment ?? '-' @endphp

                                            @if($seg === 'VIP')
                                                <span class="commerce-badge commerce-badge-vip">VIP</span>
                                            @elseif($seg === 'Loyal')
                                                <span class="commerce-badge commerce-badge-loyal">Loyal</span>
                                            @elseif($seg === 'New')
                                                <span class="commerce-badge commerce-badge-new">New</span>
                                            @elseif($seg === 'At Risk')
                                                <span class="commerce-badge commerce-badge-risk">At Risk</span>
                                            @elseif($seg === 'Lost')
                                                <span class="commerce-badge commerce-badge-lost">Lost</span>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div>{{ $customer->phone ?: '-' }}</div>
                                        </td>

                                        <td>
                                            <div>{{ $customer->email ?: '-' }}</div>
                                        </td>

                                        <td>
                                            <div>{{ $customer->orders_count ?? 0 }}</div>
                                        </td>

                                        <td>
                                            <div>{{ number_format((float) ($customer->total_spent ?? 0), 2) }}</div>
                                        </td>

                                        <td>
                                            <div>{{ $customer->last_order_number ?: '-' }}</div>
                                        </td>

                                        <td>
                                            <div>
                                                {{ $customer->last_order_at ? showDateTime($customer->last_order_at) : '-' }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
                                            <div class="commerce-empty">
                                                @lang('No customers found.')
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($customers, 'links'))
                        <div class="mt-4">
                            {{ $customers->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection