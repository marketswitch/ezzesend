@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Shopify Stores</h4>
            <p class="text-muted mb-0">
                Manage your connected Shopify stores. Multi-store support is prepared for future plan upgrades.
            </p>
        </div>
        <div>
            <a href="{{ route('user.ecommerce.shopify.connect') }}" class="btn btn--base">
                + Connect Store
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-3">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-3">
            {{ session('error') }}
        </div>
    @endif

    <div class="card custom--card">
        <div class="card-body">
            @if(isset($stores) && count($stores))
                <div class="table-responsive">
                    <table class="table table--responsive--lg">
                        <thead>
                            <tr>
                                <th>Store URL</th>
                                <th>WhatsApp</th>
                                <th>Status</th>
                                <th>Connected At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stores as $store)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $store->store_url }}</div>
                                    </td>

                                    <td>
                                        @if($store->whatsapp_account_id)
                                            <span class="badge badge--info">Linked</span>
                                        @else
                                            <span class="badge badge--warning">Not linked</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if(!empty($store->access_token))
                                            <span class="badge badge--success">Connected</span>
                                        @else
                                            <span class="badge badge--danger">Disconnected</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if(!empty($store->created_at))
                                            {{ \Carbon\Carbon::parse($store->created_at)->format('d M Y h:i A') }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex flex-wrap gap-2">

                                            <a href="{{ route('user.ecommerce.shopify.settings', $store->id) }}"
                                               class="btn btn-sm btn-outline--primary">
                                                Settings
                                            </a>

                                            <a href="{{ route('user.ecommerce.shopify.webhooks', $store->id) }}"
                                               class="btn btn-sm btn-outline--info">
                                                Webhooks
                                            </a>

                                            <a href="{{ route('user.ecommerce.shopify.logs', $store->id) }}"
                                               class="btn btn-sm btn-outline--dark">
                                                Logs
                                            </a>

                                            <form action="{{ route('user.ecommerce.shopify.disconnect', $store->id) }}"
                                                  method="POST"
                                                  style="display:inline;">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline--danger"
                                                        onclick="return confirm('Are you sure you want to disconnect this store?')">
                                                    Disconnect
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <h5 class="mb-2">No Shopify stores connected yet</h5>
                    <p class="text-muted mb-3">
                        Connect your first Shopify store to start sending automated WhatsApp messages.
                    </p>
                    <a href="{{ route('user.ecommerce.shopify.connect') }}" class="btn btn--base">
                        Connect Shopify Store
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection