@extends($activeTemplate . 'layouts.master')

@section('content')
    {{-- =========================================================
         PAGE-SPECIFIC STYLES
         - Keep all Orders page visual overrides here
         - These styles are isolated to this page only
    ========================================================== --}}
    <style>
        .commerce-stat-card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            padding: 18px;
            height: 100%;
        }

        .commerce-stat-card__label {
            font-size: 13px;
            color: #6b7280 !important;
            margin-bottom: 8px;
        }

        .commerce-stat-card__value {
            font-size: 24px;
            font-weight: 700;
            color: #111827 !important;
            line-height: 1.1;
        }

        .commerce-orders-card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .commerce-orders-card .card-header {
            background: #f9fafb !important;
            border-bottom: 1px solid #e5e7eb !important;
            padding: 16px 20px;
        }

        .commerce-orders-table th {
            color: #374151 !important;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            background: #f8fafc !important;
        }

        .commerce-orders-table td {
            color: #111827 !important;
            vertical-align: middle;
            font-size: 14px;
        }

        .commerce-orders-muted {
            color: #6b7280 !important;
            font-size: 12px;
        }

        .commerce-orders-provider {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #eef2ff;
            color: #3730a3 !important;
        }

        .commerce-orders-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .commerce-orders-status--completed,
        .commerce-orders-status--processing,
        .commerce-orders-status--paid {
            background: #dcfce7;
            color: #166534 !important;
        }

        .commerce-orders-status--pending,
        .commerce-orders-status--on-hold {
            background: #fef3c7;
            color: #92400e !important;
        }

        .commerce-orders-status--cancelled,
        .commerce-orders-status--failed,
        .commerce-orders-status--refunded {
            background: #fee2e2;
            color: #991b1b !important;
        }

        .commerce-orders-status--default {
            background: #e5e7eb;
            color: #374151 !important;
        }

        .commerce-filter-card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            padding: 18px;
        }

        .commerce-filter-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151 !important;
            margin-bottom: 6px;
        }

        .commerce-empty-state {
            padding: 32px 16px;
            text-align: center;
            color: #6b7280 !important;
        }
    </style>

    {{-- =========================================================
         HELPER VARIABLES
         - This local helper array maps order status to badge class
         - Keeps the Blade table cleaner and easier to maintain
    ========================================================== --}}
    @php
        $statusClassMap = [
            'completed' => 'commerce-orders-status--completed',
            'processing' => 'commerce-orders-status--processing',
            'paid' => 'commerce-orders-status--paid',
            'pending' => 'commerce-orders-status--pending',
            'on-hold' => 'commerce-orders-status--on-hold',
            'cancelled' => 'commerce-orders-status--cancelled',
            'failed' => 'commerce-orders-status--failed',
            'refunded' => 'commerce-orders-status--refunded',
        ];
    @endphp

    {{-- =========================================================
         PAGE HEADER
         - Standard dashboard container header
         - Search and provider filter live here
    ========================================================== --}}
    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __($pageTitle) }}</h5>
                <p class="container-top__desc">
                    @lang('Track and review ecommerce orders from WooCommerce and Shopify in one shared view.')
                </p>
            </div>
        </div>

        {{-- =====================================================
             TOP STATS
             - Simple quick overview cards
             - Can be expanded later with revenue / AOV / completed count
        ====================================================== --}}
        <div class="dashboard-container__body">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-card__label">@lang('Total Orders')</div>
                        <div class="commerce-stat-card__value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-card__label">@lang('WooCommerce Orders')</div>
                        <div class="commerce-stat-card__value">{{ $stats['woocommerce'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-card__label">@lang('Shopify Orders')</div>
                        <div class="commerce-stat-card__value">{{ $stats['shopify'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            {{-- =================================================
                 FILTER BAR
                 - Provider filter
                 - Free text search
                 - Uses GET so filters remain shareable and paginated
            ================================================== --}}
            <div class="commerce-filter-card mb-4">
                <form action="" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="commerce-filter-label">@lang('Provider')</label>
                            <select name="provider" class="form--control form-two">
                                <option value="">@lang('All Providers')</option>
                                <option value="woocommerce" {{ $provider === 'woocommerce' ? 'selected' : '' }}>
                                    @lang('WooCommerce')
                                </option>
                                <option value="shopify" {{ $provider === 'shopify' ? 'selected' : '' }}>
                                    @lang('Shopify')
                                </option>
                            </select>
                        </div>

                        <div class="col-md-7">
                            <label class="commerce-filter-label">@lang('Search')</label>
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   class="form--control form-two"
                                   placeholder="@lang('Order number, external ID, status, currency, customer, phone')">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn--base w-100">
                                <i class="las la-filter"></i> @lang('Filter')
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- =================================================
                 ORDERS TABLE
                 - Unified orders listing
                 - Includes customer relation details from joined table
                 - Safe fallbacks are used for nullable fields
            ================================================== --}}
            <div class="commerce-orders-card">
                <div class="card-header">
                    <h6 class="mb-0">@lang('Unified Orders')</h6>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--responsive--lg commerce-orders-table mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Order')</th>
                                    <th>@lang('Provider')</th>
                                    <th>@lang('Customer')</th>
                                    <th>@lang('Phone')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    @php
                                        $statusKey = strtolower((string) ($order->order_status ?? ''));
                                        $statusClass = $statusClassMap[$statusKey] ?? 'commerce-orders-status--default';
                                    @endphp

                                    <tr>
                                        {{-- Order number + external reference --}}
                                        <td>
                                            <div class="fw-semibold">{{ $order->order_number ?? '-' }}</div>
                                            <div class="commerce-orders-muted">
                                                @lang('External ID'): {{ $order->external_order_id ?? '-' }}
                                            </div>
                                        </td>

                                        {{-- Provider label --}}
                                        <td>
                                            <span class="commerce-orders-provider">
                                                {{ ucfirst($order->provider ?? '-') }}
                                            </span>
                                        </td>

                                        {{-- Customer name + email --}}
                                        <td>
                                            <div class="fw-semibold">{{ $order->customer_name ?? '-' }}</div>
                                            <div class="commerce-orders-muted">{{ $order->email ?? '-' }}</div>
                                        </td>

                                        {{-- Phone --}}
                                        <td>
                                            <div>{{ $order->phone ?? '-' }}</div>
                                        </td>

                                        {{-- Order status badge --}}
                                        <td>
                                            <span class="commerce-orders-status {{ $statusClass }}">
                                                {{ $order->order_status ?? 'unknown' }}
                                            </span>
                                        </td>

                                        {{-- Total + currency --}}
                                        <td>
                                            <div class="fw-semibold">
                                                {{ isset($order->order_total) ? number_format((float) $order->order_total, 2) : '0.00' }}
                                            </div>
                                            <div class="commerce-orders-muted">{{ $order->currency ?? '-' }}</div>
                                        </td>

                                        {{-- Ordered at date --}}
                                        <td>
                                            <div>{{ $order->ordered_at ? showDateTime($order->ordered_at) : '-' }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="commerce-empty-state">
                                                @lang('No orders found yet.')
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- =================================================
                     PAGINATION AREA
                     - Standard Laravel pagination
                     - Preserves query string because controller uses appends()
                ================================================== --}}
                @if($orders->hasPages())
                    <div class="card-footer bg-white border-top">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection