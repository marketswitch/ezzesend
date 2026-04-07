@extends($activeTemplate . 'layouts.master')

@section('content')
    <style>
        .woo-log-card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            border-radius: 12px;
        }

        .woo-log-card .card-header {
            background: #f9fafb !important;
            border-bottom: 1px solid #ececec !important;
        }

        .woo-log-table th,
        .woo-log-table td {
            color: #222 !important;
            vertical-align: middle;
        }

        .woo-log-response {
            max-width: 320px;
            white-space: normal;
            word-break: break-word;
            font-size: 13px;
            color: #444 !important;
        }

        .woo-log-badge-success {
            background: #22c55e !important;
            color: #fff !important;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .woo-log-badge-danger {
            background: #ef4444 !important;
            color: #fff !important;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .woo-log-badge-warning {
            background: #f59e0b !important;
            color: #fff !important;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .woo-log-muted {
            color: #666 !important;
            font-size: 13px;
        }
    </style>

    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __($pageTitle) }}</h5>
                <p class="container-top__desc">
                    @lang('Track WooCommerce order messages, delivery attempts, and response logs.')
                </p>
            </div>
            <div class="container-top__right">
                <form class="search-form">
                    <input type="search"
                           class="form--control"
                           placeholder="@lang('Search customer, phone, order, status')..."
                           name="search"
                           value="{{ request()->search }}">
                    <span class="search-form__icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                </form>
            </div>
        </div>

        <div class="dashboard-container__body">
            <div class="card woo-log-card">
                <div class="card-header">
                    <h6 class="mb-0">@lang('WooCommerce Message Logs')</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table--responsive--lg woo-log-table">
                            <thead>
                                <tr>
                                    <th>@lang('Customer')</th>
                                    <th>@lang('Phone')</th>
                                    <th>@lang('Order')</th>
                                    <th>@lang('Order Status')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Message')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Response')</th>
                                    <th>@lang('Sent At')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>
                                            <div>{{ $log->customer_name ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $log->phone ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $log->order_number ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $log->order_status ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>
                                                @if(!is_null($log->order_total))
                                                    {{ $log->order_total }} {{ $log->currency ?? '' }}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div>{{ $log->message_template ?? '-' }}</div>
                                            <div class="woo-log-muted">{{ $log->message_type ?? '' }}</div>
                                        </td>
                                        <td>
                                            @if($log->status === 'sent')
                                                <span class="woo-log-badge-success">@lang('Sent')</span>
                                            @elseif($log->status === 'failed')
                                                <span class="woo-log-badge-danger">@lang('Failed')</span>
                                            @else
                                                <span class="woo-log-badge-warning">{{ ucfirst($log->status ?? 'pending') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="woo-log-response">{{ $log->response ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ showDateTime($log->sent_at ?? $log->created_at) }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="woo-log-muted">@lang('No WooCommerce logs found yet.')</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($logs, 'links'))
                        <div class="mt-4">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection