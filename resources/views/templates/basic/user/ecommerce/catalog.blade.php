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
            font-size: 24px;
            font-weight: 700;
            color: #111 !important;
            line-height: 1.2;
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
        }

        .commerce-badge-woo {
            background: #7f54b3 !important;
            color: #fff !important;
        }

        .commerce-badge-shopify {
            background: #16a34a !important;
            color: #fff !important;
        }

        .commerce-summary-item {
            border: 1px solid #ececec;
            border-radius: 12px;
            padding: 18px;
            height: 100%;
            background: #fff;
        }

        .commerce-summary-item h6 {
            margin-bottom: 8px;
        }

        .commerce-summary-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .commerce-placeholder-note {
            background: #fff8e1;
            color: #8a6d3b;
            border: 1px solid #f2d38b;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 14px;
        }
    </style>

    @php
        $wooStores = $stats['woocommerce_configs'] ?? 0;
        $shopifyStores = $stats['shopify_stores'] ?? 0;
        $customersCount = $stats['customers'] ?? 0;
        $ordersCount = $stats['orders'] ?? 0;
        $totalStores = $wooStores + $shopifyStores;
    @endphp

    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __($pageTitle) }}</h5>
                <p class="container-top__desc">
                    @lang('Review your current commerce connections and unified ecommerce totals from one place.')
                </p>
            </div>
        </div>

        <div class="dashboard-container__body">

            <div class="row gy-4 mb-4">
                <div class="col-md-3">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Connected WooCommerce Stores')</div>
                        <div class="commerce-stat-value">{{ $wooStores }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Connected Shopify Stores')</div>
                        <div class="commerce-stat-value">{{ $shopifyStores }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Total Connected Stores')</div>
                        <div class="commerce-stat-value">{{ $totalStores }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Synced Orders')</div>
                        <div class="commerce-stat-value">{{ $ordersCount }}</div>
                    </div>
                </div>
            </div>

            <div class="row gy-4 mb-4">
                <div class="col-md-6">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Synced Customers')</div>
                        <div class="commerce-stat-value">{{ $customersCount }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="commerce-stat-card h-100">
                        <div class="commerce-stat-label">@lang('Catalog Overview')</div>
                        <div class="commerce-stat-value">{{ $totalStores }}</div>
                        <div class="commerce-muted mt-2">
                            @lang('Use the actions below to manage WooCommerce and Shopify catalog sources.')
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-4">
                <div class="col-lg-6">
                    <div class="commerce-summary-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">@lang('WooCommerce Catalog')</h6>
                                <div class="commerce-muted">@lang('Access WooCommerce product and configuration pages from here.')</div>
                            </div>
                            <span class="commerce-badge commerce-badge-woo">@lang('WooCommerce')</span>
                        </div>

                        <div class="commerce-muted">
                            @lang('Manage WooCommerce products and store settings from one place.')
                        </div>

                        <div class="commerce-summary-actions">
                            <a href="{{ route('user.ecommerce.woocommerce.products') }}" class="btn btn--base btn--sm">
                                @lang('View Products')
                            </a>

                            <a href="{{ route('user.ecommerce.woocommerce.config') }}" class="btn btn-outline--base btn--sm">
                                @lang('Open Config')
                            </a>

                            <a href="{{ route('user.ecommerce.woocommerce.logs') }}" class="btn btn-outline--dark btn--sm">
                                @lang('View Logs')
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
    <div class="commerce-summary-item">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="mb-1">@lang('Shopify Catalog')</h6>
                <div class="commerce-muted">@lang('Access Shopify store pages and logs from here.')</div>
            </div>
            <span class="commerce-badge commerce-badge-shopify">@lang('Shopify')</span>
        </div>

        <div class="commerce-muted">
            @lang('Manage connected Shopify stores, monitor sync logs, and access store-related pages from one place.')
        </div>

        <div class="commerce-summary-actions">

            <a href="{{ route('user.ecommerce.shopify.index') }}" class="btn btn--base btn--sm">
                @lang('View Stores')
            </a>

            <a href="{{ route('user.ecommerce.shopify.connect') }}" class="btn btn-outline--base btn--sm">
                @lang('Connect Store')
            </a>

            @if(!empty($shopifyStore))
                <a href="{{ route('user.ecommerce.shopify.logs', $shopifyStore->id) }}" class="btn btn-outline--dark btn--sm">
                    @lang('View Logs')
                </a>
            @endif

        </div>
    </div>
</div>
            </div>

        </div>
    </div>
@endsection
