@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-container">

    <div class="container-top">
        <div class="container-top__left">
            <h5 class="container-top__title">@lang('Abandoned Cart')</h5>
            <p class="container-top__desc">
                @lang('Customers who started checkout but did not complete their purchase.')
            </p>
        </div>
    </div>

    <div class="dashboard-container__body">

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body text-center py-5">

                <h5 class="mb-3">@lang('No abandoned cart data available')</h5>

                <p class="text-muted mb-4">
                    @lang('Abandoned cart tracking will appear here once your WooCommerce or Shopify store sends cart and checkout activity.')
                </p>

                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{{ route('user.ecommerce.catalog') }}" class="btn btn--base">
                        @lang('Open Catalog')
                    </a>

                    <a href="{{ route('user.ecommerce.shopify.index') }}" class="btn btn--outline">
                        @lang('Open Shopify')
                    </a>

                    <a href="{{ route('user.ecommerce.catalog') }}" class="btn btn--outline">
                        @lang('View Catalog')
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
