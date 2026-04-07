@extends($activeTemplate . 'layouts.master')

@section('content')
    <style>
        .commerce-stat-card {
            background: #fff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 14px;
            padding: 18px;
            height: 100%;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        }

        .commerce-stat-label {
            font-size: 13px;
            color: #6b7280 !important;
            margin-bottom: 8px;
        }

        .commerce-stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #111827 !important;
            line-height: 1.2;
        }

        .commerce-sync-panel {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            height: 100%;
            background: #fff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        }

        .commerce-sync-panel h6 {
            margin-bottom: 8px;
            font-weight: 700;
            color: #111827;
        }

        .commerce-muted {
            color: #6b7280 !important;
            font-size: 14px;
            line-height: 1.6;
        }

        .commerce-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .commerce-badge-woo {
            background: #7f54b3 !important;
            color: #fff !important;
        }

        .commerce-badge-shopify {
            background: #16a34a !important;
            color: #fff !important;
        }

        .commerce-sync-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .commerce-status-box {
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 14px 16px;
            background: #fcfcfc;
        }

        .commerce-status-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .commerce-status-row:last-child {
            border-bottom: 0;
        }

        .commerce-status-label {
            color: #6b7280;
            font-size: 14px;
        }

        .commerce-status-value {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }

        .commerce-action-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
    </style>

    @php
        $wooStores = $stats['woocommerce_configs'] ?? 0;
        $shopifyStores = $stats['shopify_stores'] ?? 0;
        $totalStores = $wooStores + $shopifyStores;
        $customersCount = $stats['customers'] ?? 0;
        $ordersCount = $stats['orders'] ?? 0;
    @endphp

    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __($pageTitle) }}</h5>
                <p class="container-top__desc">
                    @lang('Manage store connections and run the available synchronization tools from one place.')
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
                        <div class="commerce-stat-label">@lang('Unified Customers Stored')</div>
                        <div class="commerce-stat-value">{{ $customersCount }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="commerce-stat-card">
                        <div class="commerce-stat-label">@lang('Unified Orders Stored')</div>
                        <div class="commerce-stat-value">{{ $ordersCount }}</div>
                    </div>
                </div>
            </div>

            <div class="row gy-4 mb-4">
                <div class="col-lg-6">
                    <div class="commerce-sync-panel">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="commerce-action-title">@lang('WooCommerce Sync')</div>
                                <div class="commerce-muted">
                                    @lang('Use WooCommerce sync tools to import customers, orders, and products into the platform.')
                                </div>
                            </div>
                            <span class="commerce-badge commerce-badge-woo">@lang('WooCommerce')</span>
                        </div>

                        <div class="commerce-status-box mb-3">
                            <div class="commerce-status-row">
                                <div class="commerce-status-label">@lang('Connection Status')</div>
                                <div class="commerce-status-value">
                                    {{ $wooConfig ? __('Connected') : __('Not Connected') }}
                                </div>
                            </div>
                            <div class="commerce-status-row">
                                <div class="commerce-status-label">@lang('Available Config')</div>
                                <div class="commerce-status-value">
                                    {{ $wooConfig ? ('#' . $wooConfig->id) : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="commerce-sync-actions">
                            <form action="{{ route('user.ecommerce.woocommerce.sync.orders') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn--base btn--sm">@lang('Sync Orders')</button>
                            </form>

                            <form action="{{ route('user.ecommerce.woocommerce.sync.customers') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline--base btn--sm">@lang('Sync Customers')</button>
                            </form>

                            <form action="{{ route('user.ecommerce.woocommerce.sync.products') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline--dark btn--sm">@lang('Sync Products')</button>
                            </form>

                            <form action="{{ route('user.ecommerce.woocommerce.sync.all') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn--dark btn--sm">@lang('Full Sync')</button>
                            </form>
                        </div>

                        <div class="commerce-sync-actions mt-2">
                            <form action="{{ route('user.ecommerce.woocommerce.retry.failed.sync') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline--warning btn--sm">@lang('Retry Failed')</button>
                            </form>

                            <a href="{{ route('user.ecommerce.woocommerce.sync.history') }}" class="btn btn-outline--info btn--sm">
                                @lang('Sync History')
                            </a>

                            <a href="{{ route('user.ecommerce.woocommerce.config') }}" class="btn btn-outline--base btn--sm">
                                @lang('Open Config')
                            </a>

                            <a href="{{ route('user.ecommerce.logs') }}" class="btn btn-outline--dark btn--sm">
                                @lang('View Logs')
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="commerce-sync-panel">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="commerce-action-title">@lang('Shopify Sync')</div>
                                <div class="commerce-muted">
                                    @lang('Use Shopify sync tools to import customer data, orders, and catalog items into the platform.')
                                </div>
                            </div>
                            <span class="commerce-badge commerce-badge-shopify">@lang('Shopify')</span>
                        </div>

                        <div class="commerce-status-box mb-3">
                            <div class="commerce-status-row">
                                <div class="commerce-status-label">@lang('Connection Status')</div>
                                <div class="commerce-status-value">
                                    {{ $shopifyStore ? __('Connected') : __('Not Connected') }}
                                </div>
                            </div>
                            <div class="commerce-status-row">
                                <div class="commerce-status-label">@lang('Connected Store')</div>
                                <div class="commerce-status-value">
                                    {{ $shopifyStore->store_url ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="commerce-sync-actions">
                            @if($shopifyStore)
                                <form action="{{ route('user.ecommerce.shopify.sync.orders', $shopifyStore->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn--base btn--sm">@lang('Sync Orders')</button>
                                </form>

                                <form action="{{ route('user.ecommerce.shopify.sync.customers', $shopifyStore->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline--base btn--sm">@lang('Sync Customers')</button>
                                </form>

                                <form action="{{ route('user.ecommerce.shopify.sync.products', $shopifyStore->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline--dark btn--sm">@lang('Sync Products')</button>
                                </form>

                                <form action="{{ route('user.ecommerce.shopify.sync.all', $shopifyStore->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn--dark btn--sm">@lang('Full Sync')</button>
                                </form>
                            @else
                                <a href="{{ route('user.ecommerce.shopify.connect') }}" class="btn btn--base btn--sm">
                                    @lang('Connect Store')
                                </a>
                            @endif
                        </div>

                        <div class="commerce-sync-actions mt-2">
                            @if($shopifyStore)
                                <form action="{{ route('user.ecommerce.shopify.retry.failed.sync', $shopifyStore->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline--warning btn--sm">@lang('Retry Failed')</button>
                                </form>

                                <a href="{{ route('user.ecommerce.shopify.sync.history', $shopifyStore->id) }}" class="btn btn-outline--info btn--sm">
                                    @lang('Sync History')
                                </a>
                            @endif

                            <a href="{{ route('user.ecommerce.shopify.index') }}" class="btn btn-outline--base btn--sm">
                                @lang('View Stores')
                            </a>

                            <a href="{{ route('user.ecommerce.shopify.connect') }}" class="btn btn-outline--dark btn--sm">
                                @lang('Connect Store')
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">@lang('System Actions')</h6>
                </div>

                <div class="card-body">
                    <div class="commerce-muted mb-3">
                        @lang('Use these actions to refresh dashboard counters and rebuild stored summary data after major sync operations.')
                    </div>

                    <div class="commerce-sync-actions">
                        <form action="{{ route('user.ecommerce.refresh.metrics') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn--base btn--sm">@lang('Refresh Metrics')</button>
                        </form>

                        <form action="{{ route('user.ecommerce.rebuild.summary') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline--dark btn--sm">@lang('Rebuild Summary')</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
