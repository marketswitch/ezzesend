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
            ->where('user_id', auth()->id())
            ->pluck('name')
            ->map(fn($name) => strtolower(trim($name)))
            ->toArray();
    @endphp

    <style>
        .woo-template-alert {
            background: #ffffff !important;
            border-left: 4px solid #f59e0b;
            color: #222 !important;
        }

        .woo-template-alert * {
            color: #222 !important;
        }

        .woo-template-card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            border-radius: 12px;
        }

        .woo-template-card .card-header {
            background: #f9fafb !important;
            border-bottom: 1px solid #ececec !important;
        }

        .woo-template-card .card-header h6,
        .woo-template-card strong,
        .woo-template-card .card-body,
        .woo-template-card .card-body div,
        .woo-template-card .card-body span {
            color: #222 !important;
        }

        .woo-template-card code {
            background: #f3f4f6;
            color: #e11d48;
            padding: 2px 6px;
            border-radius: 5px;
            font-size: 13px;
        }

        .woo-template-preview {
            background: #f8f9fa;
            border: 1px solid #ececec;
            border-radius: 8px;
            padding: 12px;
            white-space: pre-line;
            font-size: 13px;
            color: #222 !important;
        }

        .woo-template-muted {
            color: #666 !important;
            font-size: 13px;
        }

        .woo-template-badge-success {
            background: #22c55e !important;
            color: #fff !important;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .woo-template-badge-danger {
            background: #ef4444 !important;
            color: #fff !important;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>

    <div class="alert alert--info alert-dismissible mb-3 template-requirements woo-template-alert" role="alert">
        <div class="alert__content">
            <h4 class="alert__title">
                <i class="las la-info-circle"></i> @lang('How to Obtain Your WooCommerce API Keys')
            </h4>
            <ul class="ms-4">
                <li class="mb-0">@lang('Log in to your WordPress admin dashboard and navigate to WooCommerce settings.')</li>
                <li class="mb-0">@lang('Select the Advanced tab, then REST API, and click on "Add key".')</li>
                <li class="mb-0">@lang('Provide a description, assign a user and appropriate permissions, then generate the API key.')</li>
                <li class="mb-0">@lang('Copy both the Consumer Key and Consumer Secret for later use.')</li>
                <li class="mb-0">@lang('Your store URL typically follows the format: https://yourstore.com.')</li>
            </ul>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __($pageTitle) }}</h5>
                <p class="container-top__desc">
                    @lang('Configure your WooCommerce store settings easily using the form below.')
                    <a target="_blank" href="https://woocommerce.com/document/woocommerce-rest-api/">
                        <i class="la la-external-link"></i> @lang('WooCommerce Documentation')
                    </a>
                </p>
            </div>
            <div class="container-top__right">
                <div class="btn--group">
                    <button type="submit" form="woo-commerce-config-form" class="btn btn--base btn-shadow">
                        <i class="lab la-telegram"></i>
                        @lang('Submit')
                    </button>
                </div>
            </div>
        </div>

        <div class="dashboard-container__body">
            <form id="woo-commerce-config-form" method="POST"
                action="{{ route('user.ecommerce.woocommerce.config.store') }}">
                @csrf
                <div class="row gy-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-two">@lang('Domain Name')</label>
                            <input type="text" class="form--control form-two" name="domain_name"
                                placeholder="*********************"
                                value="{{ @$ecommerceConfig?->config?->domain_name }}"
                                required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-two">@lang('Consumer Key')</label>
                            <input type="text" class="form--control form-two" name="consumer_key"
                                placeholder="*********************"
                                value="{{ @$ecommerceConfig?->config?->consumer_key }}"
                                required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="label-two">@lang('Consumer Secret')</label>
                            <input type="text" class="form--control form-two" name="consumer_secret"
                                placeholder="*********************"
                                value="{{ @$ecommerceConfig?->config?->consumer_secret }}"
                                required>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-container mt-4">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">@lang('Required E-commerce Templates')</h5>
                <p class="container-top__desc">
                    @lang('To activate WooCommerce automation smoothly, your WhatsApp account should have these 3 approved templates using the exact names below.')
                </p>
            </div>
        </div>

        <div class="dashboard-container__body">
            <div class="alert woo-template-alert mb-4">
                <div class="alert__content">
                    <strong>@lang('Important:')</strong>
                    @lang('These templates should be created on the same WhatsApp account used by the customer. Header is not required for these templates.')
                </div>
            </div>

            <div class="row gy-4">
                @foreach($requiredTemplates as $template)
                    @php
                        $exists = in_array(strtolower($template['name']), $existingTemplates);
                    @endphp

                    <div class="col-lg-4">
                        <div class="card woo-template-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ $template['label'] }}</h6>
                                @if($exists)
                                    <span class="woo-template-badge-success">@lang('Exists')</span>
                                @else
                                    <span class="woo-template-badge-danger">@lang('Missing')</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>@lang('Template Name'):</strong>
                                    <div><code>{{ $template['name'] }}</code></div>
                                </div>

                                <div class="mb-3">
                                    <strong>@lang('Header'):</strong>
                                    <div>@lang('None')</div>
                                </div>

                                <div class="mb-2">
                                    <strong>@lang('Body'):</strong>
                                </div>

                                <div class="woo-template-preview">{{ $template['body'] }}</div>

                                <div class="mt-3 woo-template-muted">
                                    @lang('Variables used:')<br>
                                    • {{ '{' }}{{ '{1}' }}{{ '}' }} = @lang('Customer Name')<br>
                                    • {{ '{' }}{{ '{2}' }}{{ '}' }} = @lang('Order Number / Cart Link')
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <strong>@lang('Suggested next step'):</strong>
                <div class="woo-template-muted mt-1">
                    @lang('Ask the client to create and approve these templates before enabling order confirmation, shipping notifications, and abandoned cart reminders.')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush