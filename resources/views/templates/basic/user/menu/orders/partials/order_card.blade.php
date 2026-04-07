{{--
    Order card partial
    Usage: @include('templates.basic.user.menu.orders.partials.order_card', ['order' => $order])
--}}
<div class="order-card" id="order-card-{{ $order->id }}" data-id="{{ $order->id }}">

    <div class="card-table">
        📍 {{ $order->table?->label ?? 'Takeaway' }}
        &nbsp;·&nbsp;
        <span class="card-time">{{ $order->created_at->format('H:i') }}</span>
    </div>

    <div class="card-ref">{{ $order->order_ref }}</div>

    <span class="card-payment">{{ strtoupper($order->payment_method) }}</span>

    <div class="card-items">
        @foreach ($order->items as $line)
            <div class="card-item-line">
                <span style="font-weight:600;min-width:1.5rem">{{ $line->qty }}×</span>
                <span>{{ $line->item_name_en }}</span>
                @if ($line->modifiers_snapshot)
                    <span style="color:#aaa;font-size:.72rem">
                        ({{ collect($line->modifiers_snapshot)->pluck('name_en')->join(', ') }})
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    @if ($order->notes)
        <div class="card-notes">📝 {{ $order->notes }}</div>
    @endif

    <div class="card-meta">
        <span class="card-total">{{ $order->displayTotal() }}</span>
        {!! $order->statusBadge() !!}
    </div>

    {{-- Action buttons based on current status --}}
    <div class="card-actions">
        @if ($order->status === 'received')
            <button class="btn-accept"
                    onclick="updateStatus({{ $order->id }}, 'preparing', this)">
                🔥 Accept
            </button>
            <button class="btn-reject"
                    onclick="updateStatus({{ $order->id }}, 'rejected', this)">
                ✗
            </button>

        @elseif ($order->status === 'preparing')
            <button class="btn-ready"
                    onclick="updateStatus({{ $order->id }}, 'ready', this)">
                ✅ Ready
            </button>
            <button class="btn-reject"
                    onclick="updateStatus({{ $order->id }}, 'rejected', this)">
                ✗
            </button>

        @elseif ($order->status === 'ready')
            <button class="btn-accept" style="background:#0d6efd"
                    onclick="updateStatus({{ $order->id }}, 'served', this)">
                🛎️ Served
            </button>
        @endif
    </div>

</div>
