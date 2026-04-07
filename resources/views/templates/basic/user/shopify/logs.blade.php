@extends($activeTemplate . 'layouts.master')

@section('content')
<style>
    .shopify-logs strong,
    .shopify-logs th,
    .shopify-logs td,
    .shopify-logs p,
    .shopify-logs div,
    .shopify-logs small {
        color: #222 !important;
    }

    .shopify-logs .card {
        background: #fff !important;
        color: #222 !important;
    }

    .shopify-logs .card-header {
        background: #f8f9fa;
    }

    .shopify-logs .table td,
    .shopify-logs .table th {
        vertical-align: middle;
    }

.shopify-logs .log-response {
    max-width: 320px;
    white-space: normal;
    word-break: break-word;
    font-size: 12px;
    line-height: 1.5;
    color: #555 !important;
}

.shopify-logs .log-response small {
    display: inline-block;
    background: #f8f9fa;
    padding: 6px 10px;
    border-radius: 6px;
    margin-top: 4px;
}

    .shopify-logs .badge-success {
        background: #28a745;
    }

    .shopify-logs .badge-danger {
        background: #dc3545;
    }

    .shopify-logs .badge-warning {
        background: #ffc107;
        color: #222 !important;
    }

    .shopify-logs .meta-box {
        padding: 14px 16px;
        border-radius: 10px;
        background: #f8f9fa;
        height: 100%;
    }
</style>

<div class="dashboard-body shopify-logs">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Shopify Logs</h4>
            <p class="text-muted mb-0">Track real WhatsApp messages triggered by Shopify orders for this store.</p>
        </div>
        <div>
            <a href="{{ route('user.ecommerce.shopify.index') }}" class="btn btn-sm btn-outline--secondary">
                Back to Stores
            </a>
        </div>
    </div>

    <div class="row gy-4 mb-4">
        <div class="col-lg-4">
            <div class="meta-box">
                <strong>Store URL</strong>
                <div class="mt-2">{{ $store->store_url }}</div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="meta-box">
                <strong>Connection Status</strong>
                <div class="mt-2">
                    @if(!empty($store->access_token))
                        <span class="badge badge-success">Connected</span>
                    @else
                        <span class="badge badge-danger">Disconnected</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="meta-box">
                <strong>Total Logged Messages</strong>
                <div class="mt-2">{{ isset($logs) ? $logs->count() : 0 }}</div>
            </div>
        </div>
    </div>

    <div class="card custom--card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Message Activity</h5>
        </div>
        <div class="card-body p-0">
            @if(isset($logs) && count($logs))
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Response</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        {{ $log->customer_name ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $log->phone ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $log->order_number ?: '-' }}
                                    </td>

                                    <td>
                                        @if($log->status === 'sent')
                                            <span class="badge badge-success">Sent</span>
                                        @elseif($log->status === 'failed')
                                            <span class="badge badge-danger">Failed</span>
                                        @else
                                            <span class="badge badge-warning">{{ ucfirst($log->status) }}</span>
                                        @endif
                                    </td>

                                    <td>
    <div class="log-response">
        @if(!empty($log->response) && \Illuminate\Support\Str::startsWith($log->response, 'Message ID:'))
            <span class="badge badge-success">Delivered to Meta</span>
            <div class="mt-1">
                <small>{{ \Illuminate\Support\Str::limit($log->response, 60) }}</small>
            </div>
        @elseif(!empty($log->response))
            <span class="badge badge-danger">API Response</span>
            <div class="mt-1">
                <small>{{ \Illuminate\Support\Str::limit($log->response, 120) }}</small>
            </div>
        @else
            <span class="text-muted">No response</span>
        @endif
    </div>
</td>

                                    <td>
                                        @if(!empty($log->created_at))
                                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y h:i A') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <h5 class="mb-2">No logs found</h5>
                    <p class="text-muted mb-0">Once Shopify sends an order and WhatsApp is triggered, message logs will appear here.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection