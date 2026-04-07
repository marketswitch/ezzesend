@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-container">
    <div class="container-top">
        <div class="container-top__left">
            <h5 class="container-top__title">@lang('Analytics')</h5>
            <p class="container-top__desc">@lang('Placeholder page for future analytics dashboard.')</p>
        </div>
    </div>
    <div class="dashboard-container__body"></div>
</div>
@endsection
