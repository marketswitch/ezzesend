@extends($activeTemplate . 'layouts.master')

@section('content')
<style>
    .shopify-webhooks strong {
        color: #222;
        font-weight: 600;
    }

    .shopify-webhooks div,
    .shopify-webhooks p,
    .shopify-webhooks li,
    .shopify-webhooks code {
        color: #444;
    }

    .shopify-webhooks .card {
        background: #fff !important;
        color: #222 !important;
    }

    .shopify-webhooks .card-header {
        background: #f8f9fa;
    }

    .shopify-webhooks code {
        display: inline-block;
        padding: 6px 10px;
        background: #f4f4f4;
        border-radius: 6px;
        word-break: break-all;
    }
</style>

<div class="dashboard-body shopify-webhooks">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Shopify Webhooks</h4>
            <p class="text-muted mb-0">Webhook configuration for this store. Built to support more webhook events in future multi-store upgrades.</p>
        </div>
        <div>
            <a href="{{ route('user.ecommerce.shopify.index') }}" class="btn btn-sm btn-outline--secondary">
                Back to Stores
            </a>
        </div>
    </div>

    <div class="row gy-4">

        <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Store Webhook Info</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Store URL:</strong>
                        <div>{{ $store->store_url }}</div>
                    </div>

                    <div class="mb-3">
                        <strong>Active Event:</strong>
                        <div>orders/create</div>
                    </div>

                    <div class="mb-3">
                        <strong>Delivery Method:</strong>
                        <div>HTTP POST</div>
                    </div>

                    <div class="mb-0">
                        <strong>Format:</strong>
                        <div>JSON</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Webhook Endpoint</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Order Webhook URL:</strong>
                        <div class="mt-2">
                            <code>{{ url('/api/shopify/webhook/order') }}</code>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Recommended Shopify Event:</strong>
                        <div class="mt-2">
                            <code>orders/create</code>
                        </div>
                    </div>

                    <div class="mb-0">
                        <strong>Status:</strong>
                        <div class="mt-2">
                            @if(!empty($store->access_token))
                                <span class="badge badge--success">Ready</span>
                            @else
                                <span class="badge badge--warning">Store Not Fully Connected</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Webhook Roadmap</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">This store is prepared for future webhook expansion, including:</p>
                    <ul class="mb-0">
                        <li>Order creation notifications</li>
                        <li>App uninstall webhook handling</li>
                        <li>Order status update events</li>
                        <li>Fulfillment and shipping notifications</li>
                        <li>Store-specific webhook monitoring for future multi-store plans</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection