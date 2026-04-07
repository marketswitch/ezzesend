@extends($activeTemplate . 'layouts.master')

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════
     KITCHEN ORDER DASHBOARD
     - Real-time new order alerts via Pusher (same pattern as inbox)
     - Web Audio API alarm (no audio file needed, generated in-browser)
     - Full-screen flash + pulsing card on new order
     - Staff clicks "Accept" to acknowledge and stop alarm
     - Auto-polls every 30s as fallback if Pusher disconnects
══════════════════════════════════════════════════════════════════════ --}}

<style>
/* ── Layout ─────────────────────────────────────────────────────────── */
.kitchen-wrap   { padding: 1rem; }
.kitchen-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: .75rem; }
.kitchen-title  { font-size: 1.1rem; font-weight: 600; }
.kitchen-stats  { display: flex; gap: .75rem; }
.stat-chip      { background: var(--bs-light, #f8f9fa); border: 1px solid #dee2e6; border-radius: 20px; padding: .3rem .85rem; font-size: .8rem; font-weight: 500; }
.stat-chip.live { background: #fff3cd; border-color: #ffc107; color: #856404; }

/* ── Columns ────────────────────────────────────────────────────────── */
.order-columns  { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
.order-col      { background: #f8f9fa; border-radius: 12px; padding: .75rem; }
.col-header     { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #888; margin-bottom: .75rem; display: flex; align-items: center; justify-content: space-between; }
.col-count      { background: #dee2e6; border-radius: 20px; padding: 2px 8px; font-size: .7rem; }
.col-count.has  { background: #1a1a1a; color: #fff; }

/* ── Order card ─────────────────────────────────────────────────────── */
.order-card {
    background: #fff;
    border-radius: 10px;
    border: 1.5px solid #e9ecef;
    padding: .875rem;
    margin-bottom: .625rem;
    transition: all .2s;
    position: relative;
    overflow: hidden;
}
.order-card.is-new {
    border-color: #ffc107;
    box-shadow: 0 0 0 3px rgba(255,193,7,.25);
    animation: pulse-card 1s ease-in-out infinite;
}
@keyframes pulse-card {
    0%, 100% { box-shadow: 0 0 0 3px rgba(255,193,7,.25); }
    50%       { box-shadow: 0 0 0 8px rgba(255,193,7,.0); }
}
.card-ref   { font-size: .8rem; font-weight: 700; color: #1a1a1a; }
.card-table { font-size: .75rem; color: #888; margin-bottom: .4rem; }
.card-items { font-size: .8rem; color: #444; line-height: 1.5; margin-bottom: .5rem; border-top: 1px solid #f0f0f0; padding-top: .4rem; }
.card-item-line { display: flex; gap: .4rem; }
.card-meta  { display: flex; align-items: center; justify-content: space-between; }
.card-total { font-size: .85rem; font-weight: 700; }
.card-time  { font-size: .7rem; color: #aaa; }
.card-notes { font-size: .75rem; color: #e67e22; margin-top: .35rem; background: #fff8f0; border-radius: 6px; padding: .25rem .5rem; }
.card-payment { display: inline-block; font-size: .7rem; font-weight: 600; padding: 2px 7px; border-radius: 10px; background: #e9ecef; color: #555; margin-bottom: .4rem; }

.card-actions { display: flex; gap: .4rem; margin-top: .625rem; }
.btn-accept { flex: 1; padding: .45rem; background: #1a1a1a; color: #fff; border: none; border-radius: 8px; font-size: .8rem; font-weight: 600; cursor: pointer; transition: opacity .15s; }
.btn-accept:hover { opacity: .85; }
.btn-ready  { flex: 1; padding: .45rem; background: #198754; color: #fff; border: none; border-radius: 8px; font-size: .8rem; font-weight: 600; cursor: pointer; }
.btn-reject { padding: .45rem .65rem; background: #fff; color: #dc3545; border: 1.5px solid #dc3545; border-radius: 8px; font-size: .8rem; cursor: pointer; }

/* ── Full-screen flash overlay ──────────────────────────────────────── */
.flash-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(255,193,7,.18);
    pointer-events: none;
    opacity: 0;
    transition: opacity .15s;
}
.flash-overlay.flashing { animation: flash-bg 3s ease-out forwards; }
@keyframes flash-bg {
    0%   { opacity: 1; }
    20%  { opacity: .8; }
    40%  { opacity: .5; }
    60%  { opacity: .3; }
    80%  { opacity: .1; }
    100% { opacity: 0; }
}

/* ── Toast popup ────────────────────────────────────────────────────── */
.order-toast {
    position: fixed; top: 1.25rem; right: 1.25rem; z-index: 9998;
    background: #1a1a1a; color: #fff;
    border-radius: 14px; padding: 1rem 1.25rem;
    min-width: 280px; max-width: 340px;
    box-shadow: 0 8px 30px rgba(0,0,0,.35);
    transform: translateX(calc(100% + 2rem));
    transition: transform .35s cubic-bezier(.34,1.56,.64,1);
    border-left: 4px solid #ffc107;
}
.order-toast.show { transform: translateX(0); }
.toast-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: #ffc107; margin-bottom: .3rem; }
.toast-ref   { font-size: 1rem; font-weight: 700; margin-bottom: .2rem; }
.toast-items { font-size: .8rem; color: #ccc; margin-bottom: .75rem; }
.toast-ack   { width: 100%; padding: .5rem; background: #ffc107; color: #1a1a1a; border: none; border-radius: 8px; font-size: .85rem; font-weight: 700; cursor: pointer; }
.toast-ack:hover { background: #ffca2c; }

/* ── Connection badge ───────────────────────────────────────────────── */
.conn-badge { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 500; padding: .3rem .75rem; border-radius: 20px; }
.conn-badge .dot { width: 8px; height: 8px; border-radius: 50%; }
.conn-badge.live  { background: #d1e7dd; color: #0a3622; }
.conn-badge.live .dot  { background: #198754; animation: blink 1.5s infinite; }
.conn-badge.offline { background: #f8d7da; color: #58151c; }
.conn-badge.offline .dot { background: #dc3545; }
@keyframes blink { 0%,100% { opacity:1; } 50% { opacity: .3; } }

/* ── Empty state ────────────────────────────────────────────────────── */
.empty-col { text-align: center; padding: 2rem 1rem; color: #bbb; font-size: .8rem; }
.empty-col .empty-icon { font-size: 2rem; margin-bottom: .5rem; }

/* ── Alarm button ───────────────────────────────────────────────────── */
.alarm-btn {
    background: none; border: 1.5px solid #dee2e6;
    border-radius: 8px; padding: .3rem .7rem;
    font-size: .8rem; cursor: pointer;
    display: flex; align-items: center; gap: .35rem;
    color: #555;
}
.alarm-btn.muted { color: #aaa; border-color: #f0f0f0; }
</style>

{{-- Flash overlay --}}
<div class="flash-overlay" id="flashOverlay"></div>

{{-- New order toast --}}
<div class="order-toast" id="orderToast">
    <div class="toast-label">🔔 New Order!</div>
    <div class="toast-ref"  id="toastRef">—</div>
    <div class="toast-items" id="toastItems">—</div>
    <button class="toast-ack" onclick="acknowledgeAlarm()">✓ Acknowledge</button>
</div>

{{-- Dashboard --}}
<div class="kitchen-wrap">

    {{-- Header --}}
    <div class="kitchen-header">
        <div>
            <div class="kitchen-title">
                🍽️ {{ $restaurant->name_en }} — Kitchen
            </div>
            @if ($branches->count() > 1)
                <div style="font-size:.8rem;color:#888;margin-top:.2rem">
                    @foreach ($branches as $b)
                        <a href="?branch={{ $b->id }}"
                           style="margin-inline-end:.75rem;{{ $branchId == $b->id ? 'font-weight:700;color:#1a1a1a' : 'color:#888' }}">
                            {{ $b->name_en }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <span class="conn-badge offline" id="connBadge">
                <span class="dot"></span>
                <span id="connText">Connecting…</span>
            </span>

            <button class="alarm-btn" id="alarmToggle" onclick="toggleAlarm()" title="Toggle sound">
                <span id="alarmIcon">🔔</span>
                <span id="alarmLabel">Sound on</span>
            </button>

            <button class="btn-accept" style="width:auto;padding:.35rem .875rem"
                    onclick="location.reload()">↻ Refresh</button>
        </div>
    </div>

    {{-- Stats row --}}
    <div class="kitchen-stats" style="margin-bottom:1rem">
        <span class="stat-chip live" id="statReceived">
            📥 Received: <b id="cntReceived">{{ $liveOrders->where('status','received')->count() }}</b>
        </span>
        <span class="stat-chip">
            🔥 Preparing: <b id="cntPreparing">{{ $liveOrders->where('status','preparing')->count() }}</b>
        </span>
        <span class="stat-chip">
            ✅ Ready: <b id="cntReady">{{ $liveOrders->where('status','ready')->count() }}</b>
        </span>
    </div>

    {{-- Order columns --}}
    <div class="order-columns">

        {{-- Received --}}
        <div class="order-col">
            <div class="col-header">
                📥 Received
                <span class="col-count {{ $liveOrders->where('status','received')->count() ? 'has' : '' }}"
                      id="colCountReceived">
                    {{ $liveOrders->where('status','received')->count() }}
                </span>
            </div>
            <div id="colReceived">
                @forelse ($liveOrders->where('status','received') as $order)
                    @include('templates.basic.user.menu.orders.partials.order_card', ['order' => $order])
                @empty
                    <div class="empty-col"><div class="empty-icon">🕐</div>Waiting for orders…</div>
                @endforelse
            </div>
        </div>

        {{-- Preparing --}}
        <div class="order-col">
            <div class="col-header">
                🔥 Preparing
                <span class="col-count {{ $liveOrders->where('status','preparing')->count() ? 'has' : '' }}"
                      id="colCountPreparing">
                    {{ $liveOrders->where('status','preparing')->count() }}
                </span>
            </div>
            <div id="colPreparing">
                @forelse ($liveOrders->where('status','preparing') as $order)
                    @include('templates.basic.user.menu.orders.partials.order_card', ['order' => $order])
                @empty
                    <div class="empty-col"><div class="empty-icon">🍳</div>Nothing cooking…</div>
                @endforelse
            </div>
        </div>

        {{-- Ready --}}
        <div class="order-col">
            <div class="col-header">
                ✅ Ready
                <span class="col-count {{ $liveOrders->where('status','ready')->count() ? 'has' : '' }}"
                      id="colCountReady">
                    {{ $liveOrders->where('status','ready')->count() }}
                </span>
            </div>
            <div id="colReady">
                @forelse ($liveOrders->where('status','ready') as $order)
                    @include('templates.basic.user.menu.orders.partials.order_card', ['order' => $order])
                @empty
                    <div class="empty-col"><div class="empty-icon">🛎️</div>Nothing ready yet…</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Recent history --}}
    @if ($recentOrders->count())
    <div style="margin-top:1.5rem">
        <div class="col-header" style="margin-bottom:.75rem">
            Recent (served / rejected)
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;font-size:.8rem;border-collapse:collapse">
                <thead>
                    <tr style="background:#f8f9fa;text-align:left">
                        <th style="padding:.5rem .75rem">Ref</th>
                        <th style="padding:.5rem .75rem">Table</th>
                        <th style="padding:.5rem .75rem">Total</th>
                        <th style="padding:.5rem .75rem">Status</th>
                        <th style="padding:.5rem .75rem">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                    <tr style="border-top:1px solid #f0f0f0">
                        <td style="padding:.5rem .75rem;font-weight:600">{{ $order->order_ref }}</td>
                        <td style="padding:.5rem .75rem;color:#888">{{ $order->table?->label ?? 'Takeaway' }}</td>
                        <td style="padding:.5rem .75rem">{{ $order->displayTotal() }}</td>
                        <td style="padding:.5rem .75rem">{!! $order->statusBadge() !!}</td>
                        <td style="padding:.5rem .75rem;color:#aaa">{{ $order->created_at->format('H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@push('script')
<script>
// ══════════════════════════════════════════════════════════════════════
// KITCHEN DASHBOARD — Real-time order notifications
// ══════════════════════════════════════════════════════════════════════

const RESTAURANT_ID = {{ $restaurant->id }};
const PUSHER_KEY    = document.querySelector('meta[name="P-A-ID"]')?.content    || '';
const PUSHER_CLUSTER= document.querySelector('meta[name="P-CLUSTER"]')?.content || 'mt1';
const STATUS_URL    = '{{ route("user.menu.orders.status", ":id") }}';
const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content || '';

let alarmMuted      = false;
let alarmInterval   = null;
let audioCtx        = null;

// ── Web Audio alarm ────────────────────────────────────────────────────
// Generates a loud repeating beep entirely in-browser.
// No audio file needed — works offline.
function getAudioCtx() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioCtx;
}

function beep(freq = 880, duration = 0.15, volume = 0.7) {
    try {
        const ctx  = getAudioCtx();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.type            = 'square';
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(volume, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);

        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + duration);
    } catch (e) {
        // AudioContext blocked before user interaction — will unlock on first click
    }
}

// Three-beep pattern: high-low-high (unmistakable kitchen alarm)
function playAlarmPattern() {
    beep(880, 0.12, 0.8);
    setTimeout(() => beep(660, 0.12, 0.7), 180);
    setTimeout(() => beep(880, 0.20, 0.9), 360);
}

function startAlarm() {
    if (alarmMuted) return;
    playAlarmPattern();
    // Repeat every 2.5 seconds until acknowledged
    alarmInterval = setInterval(() => {
        if (!alarmMuted) playAlarmPattern();
    }, 2500);
}

function stopAlarm() {
    clearInterval(alarmInterval);
    alarmInterval = null;
}

function toggleAlarm() {
    alarmMuted = !alarmMuted;
    document.getElementById('alarmIcon').textContent  = alarmMuted ? '🔕' : '🔔';
    document.getElementById('alarmLabel').textContent = alarmMuted ? 'Sound off' : 'Sound on';
    document.getElementById('alarmToggle').classList.toggle('muted', alarmMuted);
    if (alarmMuted) stopAlarm();
}

// Unlock AudioContext on first user interaction (browser requirement)
document.addEventListener('click', () => {
    try { getAudioCtx().resume(); } catch(e) {}
}, { once: true });

// ── Flash + toast ──────────────────────────────────────────────────────
function fireAlert(data) {
    // 1. Full-screen yellow flash
    const overlay = document.getElementById('flashOverlay');
    overlay.classList.remove('flashing');
    void overlay.offsetWidth; // reflow to restart animation
    overlay.classList.add('flashing');

    // 2. Toast popup
    document.getElementById('toastRef').textContent   = '🧾 ' + data.order_ref
        + (data.table_label ? ' — ' + data.table_label : '');
    document.getElementById('toastItems').textContent = data.items_summary || '…';
    const toast = document.getElementById('orderToast');
    toast.classList.add('show');

    // 3. Alarm
    startAlarm();

    // 4. Browser notification (if permission granted)
    if (Notification.permission === 'granted') {
        new Notification('🔔 New Order — ' + data.order_ref, {
            body: data.items_summary,
            icon: '/assets/images/logo_icon/logo.png',
        });
    }

    // 5. Add card to Received column
    prependOrderCard(data);
    updateCounts();
}

function acknowledgeAlarm() {
    stopAlarm();
    document.getElementById('orderToast').classList.remove('show');
    // Remove pulse from all new cards
    document.querySelectorAll('.order-card.is-new').forEach(c => c.classList.remove('is-new'));
}

// ── Inject new order card into Received column ─────────────────────────
function prependOrderCard(data) {
    const col = document.getElementById('colReceived');

    // Remove empty-state placeholder if present
    col.querySelector('.empty-col')?.remove();

    const card = document.createElement('div');
    card.className   = 'order-card is-new';
    card.id          = 'order-card-' + data.order_id;
    card.dataset.id  = data.order_id;
    card.dataset.ref = data.order_ref;

    card.innerHTML = `
        <div class="card-table">📍 ${escHtml(data.table_label)} &nbsp;·&nbsp; <span class="card-time">${escHtml(data.created_at)}</span></div>
        <div class="card-ref">${escHtml(data.order_ref)}</div>
        <span class="card-payment">${escHtml(data.payment_method.toUpperCase())}</span>
        <div class="card-items">${escHtml(data.items_summary)}</div>
        ${data.notes ? `<div class="card-notes">📝 ${escHtml(data.notes)}</div>` : ''}
        <div class="card-meta">
            <span class="card-total">${escHtml(data.currency)} ${escHtml(data.total)}</span>
        </div>
        <div class="card-actions">
            <button class="btn-accept" onclick="updateStatus(${data.order_id}, 'preparing', this)">
                🔥 Accept
            </button>
            <button class="btn-reject" onclick="updateStatus(${data.order_id}, 'rejected', this)">
                ✗
            </button>
        </div>
    `;

    col.prepend(card);
}

// ── Status update (accept / ready / reject) ───────────────────────────
function updateStatus(orderId, newStatus, btn) {
    if (!confirm(`Move order to "${newStatus}"?`)) return;

    btn.disabled    = true;
    btn.textContent = '…';

    fetch(`/user/menu/orders/${orderId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({ status: newStatus }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Remove card from current column, reload page to re-bucket
            const card = document.getElementById('order-card-' + orderId);
            if (card) {
                card.style.opacity     = '0';
                card.style.transform   = 'scale(.95)';
                card.style.transition  = 'all .25s';
                setTimeout(() => { card.remove(); updateCounts(); }, 250);
            }
            // Full reload after a moment to get the card into the right column
            setTimeout(() => location.reload(), 400);
        } else {
            alert(data.message || 'Error updating status.');
            btn.disabled    = false;
            btn.textContent = '🔥 Accept';
        }
    })
    .catch(() => {
        alert('Network error. Please refresh.');
        btn.disabled = false;
    });
}

// ── Counts ────────────────────────────────────────────────────────────
function updateCounts() {
    const received  = document.querySelectorAll('#colReceived .order-card').length;
    const preparing = document.querySelectorAll('#colPreparing .order-card').length;
    const ready     = document.querySelectorAll('#colReady .order-card').length;

    document.getElementById('cntReceived').textContent  = received;
    document.getElementById('cntPreparing').textContent = preparing;
    document.getElementById('cntReady').textContent     = ready;

    document.getElementById('colCountReceived').textContent  = received;
    document.getElementById('colCountPreparing').textContent = preparing;
    document.getElementById('colCountReady').textContent     = ready;

    ['colCountReceived','colCountPreparing','colCountReady'].forEach((id, i) => {
        const cnt = [received, preparing, ready][i];
        document.getElementById(id).classList.toggle('has', cnt > 0);
    });

    // Flash the browser tab title when there are new received orders
    if (received > 0) {
        document.title = `(${received}) 🔔 Kitchen`;
    } else {
        document.title = '🍽️ Kitchen Dashboard';
    }
}

// ── Pusher connection ─────────────────────────────────────────────────
function connectPusher() {
    if (!PUSHER_KEY) {
        setConnStatus(false, 'No Pusher key');
        return;
    }

    const pusher = new Pusher(PUSHER_KEY, {
        cluster:       PUSHER_CLUSTER,
        forceTLS:      true,
        authEndpoint:  null, // set dynamically below
    });

    pusher.connection.bind('connected', () => {
        setConnStatus(true);

        const SOCKET_ID    = pusher.connection.socket_id;
        const CHANNEL_NAME = `private-menu-new-order-${RESTAURANT_ID}`;

        // Use same auth pattern as the existing inbox
        pusher.config.authEndpoint = makeAuthEndPointForPusher(SOCKET_ID, CHANNEL_NAME);

        const channel = pusher.subscribe(CHANNEL_NAME);

        channel.bind('pusher:subscription_succeeded', () => {
            console.log('[Kitchen] Subscribed to', CHANNEL_NAME);
        });

        channel.bind('menu-new-order', (payload) => {
            console.log('[Kitchen] New order received:', payload);
            fireAlert(payload.data ?? payload);
        });

        channel.bind('pusher:subscription_error', (err) => {
            console.error('[Kitchen] Channel auth failed:', err);
            setConnStatus(false, 'Auth failed');
        });
    });

    pusher.connection.bind('disconnected', () => setConnStatus(false, 'Disconnected'));
    pusher.connection.bind('unavailable',  () => setConnStatus(false, 'Unavailable'));
    pusher.connection.bind('failed',       () => setConnStatus(false, 'Failed'));
    pusher.connection.bind('error',        (err) => {
        console.error('[Kitchen] Pusher error:', err);
        setConnStatus(false, 'Error');
    });
}

function setConnStatus(online, label = 'Live') {
    const badge = document.getElementById('connBadge');
    const text  = document.getElementById('connText');
    badge.className = 'conn-badge ' + (online ? 'live' : 'offline');
    text.textContent = online ? '● Live' : '⚠ ' + label;
}

// ── Fallback auto-refresh (every 30s if Pusher disconnects) ───────────
setInterval(() => {
    // Only auto-reload when tab is visible and there are live orders
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 30_000);

// ── Browser notification permission ───────────────────────────────────
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// ── Helpers ───────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

// ── Init ──────────────────────────────────────────────────────────────
connectPusher();
updateCounts();
</script>
@endpush

@endsection
