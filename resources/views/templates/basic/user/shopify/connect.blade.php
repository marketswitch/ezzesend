@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-body">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Shopify Connect</h5>
                </div>
                <div class="card-body">

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

                    <form action="{{ route('user.ecommerce.shopify.connect') }}" method="GET">
                        <div class="form-group mb-3">
                            <label class="form-label">Store URL</label>
                            <input type="text" name="store_url" class="form-control"
                                   value="{{ old('store_url', $shopifyStore->store_url ?? '') }}"
                                   placeholder="your-store.myshopify.com" required>
                            <small class="text-muted">Enter your Shopify store domain.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">WhatsApp Account</label>
                            <select name="whatsapp_account_id" class="form-control" required>
                                <option value="">Select WhatsApp account</option>
                                @foreach($whatsappAccounts as $account)
                                    <option value="{{ $account->id }}"
                                        {{ (old('whatsapp_account_id', $shopifyStore->whatsapp_account_id ?? '') == $account->id) ? 'selected' : '' }}>
                                        {{ $account->phone_number ?: ('Account #' . $account->id) }} - {{ $account->phone_number_status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn--base w-100">
                            Connect Shopify
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection