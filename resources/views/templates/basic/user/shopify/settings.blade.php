<style>
    .shopify-settings strong {
        color: #222;
        font-weight: 600;
    }

    .shopify-settings div {
        color: #444;
    }

    .shopify-settings .card {
        background: #fff !important;
        color: #222 !important;
    }

    .shopify-settings .card-header {
        background: #f8f9fa;
    }

    .shopify-settings .template-box {
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 16px;
        background: #fafafa;
        height: 100%;
    }

    .shopify-settings .template-code {
        background: #f4f4f4;
        border-radius: 8px;
        padding: 12px;
        font-size: 13px;
        white-space: pre-line;
        color: #222;
    }

    .shopify-settings .template-name {
        font-weight: 700;
        color: #111;
    }

    .shopify-settings .template-help {
        font-size: 13px;
        color: #666 !important;
    }
</style>

@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    $requiredTemplates = collect([
        [
            'name' => 'order_confirmation',
            'label' => 'Order Confirmation',
            'body' => "Hi {{1}} 👋\n\nYour order {{2}} has been confirmed ✅\n\nWe will contact you shortly 🚀\n\nThank you for shopping with us 💙",
        ],
        [
            'name' => 'order_shipped',
            'label' => 'Order Shipped',
            'body' => "Hi {{1}} 👋\n\nYour order {{2}} has been shipped 🚚\n\nWe will share delivery updates with you soon.\n\nThank you for shopping with us 💙",
        ],
        [
            'name' => 'abandoned_cart',
            'label' => 'Abandoned Cart',
            'body' => "Hi {{1}} 👋\n\nIt looks like you left some items in your cart 🛒\n\nComplete your order here: {{2}}\n\nWe are here if you need any help 💙",
        ],
    ]);

    $existingTemplates = \Illuminate\Support\Facades\DB::table('templates')
        ->where('whatsapp_account_id', $store->whatsapp_account_id)
        ->pluck('name')
        ->map(fn($name) => strtolower(trim($name)))
        ->toArray();
@endphp

<div class="dashboard-body shopify-settings">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Shopify Settings</h4>
            <p class="text-muted mb-0">Manage this store configuration</p>
        </div>
    </div>

    <div class="row gy-4">

        {{-- STORE INFO --}}
        <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Store Info</h5>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <strong>Store URL:</strong>
                        <div>{{ $store->store_url }}</div>
                    </div>

                    <div class="mb-3">
                        <strong>Status:</strong>
                        <div>
                            @if(!empty($store->access_token))
                                <span class="badge badge--success">Connected</span>
                            @else
                                <span class="badge badge--danger">Not Connected</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Created:</strong>
                        <div>{{ $store->created_at }}</div>
                    </div>

                    <div>
                        <strong>Updated:</strong>
                        <div>{{ $store->updated_at }}</div>
                    </div>

                </div>
            </div>
        </div>

        {{-- WHATSAPP --}}
        <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">WhatsApp Account</h5>
                </div>
                <div class="card-body">

                    @if($linkedWhatsapp)

                        <div class="mb-3">
                            <strong>Phone:</strong>
                            <div>{{ $linkedWhatsapp->phone_number }}</div>
                        </div>

                        <div class="mb-3">
                            <strong>Status:</strong>
                            <div>{{ $linkedWhatsapp->phone_number_status }}</div>
                        </div>

                        <div>
                            <strong>Account ID:</strong>
                            <div>{{ $linkedWhatsapp->id }}</div>
                        </div>

                    @else

                        <div class="alert alert-warning">
                            No WhatsApp account linked
                        </div>

                    @endif

                </div>
            </div>
        </div>

        {{-- REQUIRED TEMPLATES --}}
        <div class="col-12">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Required E-commerce Templates</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        To activate all e-commerce automation features, this WhatsApp account should have these 3 approved templates.
                        Use the exact template names below. Header is not required for these templates.
                    </div>

                    <div class="row gy-4">
                        @foreach($requiredTemplates as $template)
                            @php
                                $exists = in_array(strtolower($template['name']), $existingTemplates);
                            @endphp

                            <div class="col-lg-4">
                                <div class="template-box">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="template-name">{{ $template['label'] }}</div>
                                        <div>
                                            @if($exists)
                                                <span class="badge badge--success">Exists</span>
                                            @else
                                                <span class="badge badge--danger">Missing</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <strong>Template Name:</strong>
                                        <div><code>{{ $template['name'] }}</code></div>
                                    </div>

                                    <div class="mb-2">
                                        <strong>Header:</strong>
                                        <div>None</div>
                                    </div>

                                    <div class="mb-2">
                                        <strong>Body:</strong>
                                    </div>

                                    <div class="template-code">{{ $template['body'] }}</div>

                                    <div class="template-help mt-3">
                                        Variables used:
                                        <br>• {{ '{' }}{{ '{1}' }}{{ '}' }} = Customer Name
                                        <br>• {{ '{' }}{{ '{2}' }}{{ '}' }} = Order Number / Cart Link
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <strong>Suggested next step:</strong>
                        <div class="template-help mt-1">
                            Ask the client to create and approve these templates on the same WhatsApp account linked to this store.
                            Once approved, order confirmation, shipping updates, and abandoned cart automations can be enabled smoothly.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection