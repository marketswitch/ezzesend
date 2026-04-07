@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-container">

    <div class="container-top">
        <div class="container-top__left">
            <h5 class="container-top__title">@lang('Message History')</h5>
            <p class="container-top__desc">
                @lang('View all WhatsApp messages sent to customers from your stores.')
            </p>
        </div>
    </div>

    <div class="dashboard-container__body">

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">@lang('Message Logs')</h6>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>@lang('Customer')</th>
                                <th>@lang('Phone')</th>
                                <th>@lang('Provider')</th>
                                <th>@lang('Order ID')</th>
                                <th>@lang('Template')</th>
                                <th>@lang('Type')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Sent At')</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td class="fw-semibold">{{ $log->customer_name ?? '-' }}</td>
                                    <td>{{ $log->phone ?? '-' }}</td>
                                    <td>{{ ucfirst($log->provider ?? '-') }}</td>
                                    <td>#{{ $log->commerce_order_id ?? '-' }}</td>
                                    <td>{{ $log->message_template ?? '-' }}</td>
                                    <td>{{ ucfirst($log->message_type ?? '-') }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($log->status) {
                                                'sent' => 'bg-success',
                                                'failed' => 'bg-danger',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->sent_at ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        @lang('No message history found')
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
