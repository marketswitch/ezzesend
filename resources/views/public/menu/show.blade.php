<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $lang === 'ar' ? $restaurant->name_ar : $restaurant->name_en }}</title>

    {{-- Prevent search engine indexing of menu pages --}}
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ── Reset ──────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: {{ $lang === 'ar' ? "'IBM Plex Sans Arabic', sans-serif" : "'Inter', sans-serif" }};
            background: #f5f5f5;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }
        [dir="rtl"] { text-align: right; }

        /* ── Cover & header ─────────────────────────────────────────── */
        .cover {
            position: relative;
            height: 200px;
            background: #1a1a1a;
            overflow: hidden;
        }
        .cover img { width: 100%; height: 100%; object-fit: cover; opacity: .85; }
        .cover-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,.7), transparent);
            display: flex; align-items: flex-end;
            padding: 1.25rem;
        }
        .cover-info { color: #fff; }
        .cover-logo {
            width: 56px; height: 56px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,.3);
            margin-bottom: .5rem;
        }
        .cover-name { font-size: 1.25rem; font-weight: 600; }
        .cover-table { font-size: .8rem; opacity: .8; margin-top: .2rem; }

        /* ── Language toggle ────────────────────────────────────────── */
        .lang-btn {
            position: absolute; top: 12px;
            {{ $lang === 'ar' ? 'left' : 'right' }}: 12px;
            background: rgba(255,255,255,.9);
            border: none; border-radius: 20px;
            padding: 5px 12px; font-size: .75rem;
            font-weight: 600; cursor: pointer;
            backdrop-filter: blur(4px);
        }

        /* ── Category nav ───────────────────────────────────────────── */
        .cat-nav {
            background: #fff;
            overflow-x: auto;
            white-space: nowrap;
            padding: .75rem 1rem;
            border-bottom: 1px solid #eee;
            position: sticky; top: 0; z-index: 90;
            -webkit-overflow-scrolling: touch;
        }
        .cat-nav::-webkit-scrollbar { display: none; }
        .cat-chip {
            display: inline-block;
            padding: .4rem .9rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 500;
            background: #f0f0f0;
            color: #555;
            cursor: pointer;
            transition: all .15s;
            margin-inline-end: .5rem;
            border: none;
        }
        .cat-chip.active {
            background: #1a1a1a;
            color: #fff;
        }

        /* ── Menu content ───────────────────────────────────────────── */
        .menu-body { padding: 0 0 120px; }
        .cat-section { padding: 1.25rem 1rem .5rem; }
        .cat-section-title {
            font-size: 1rem; font-weight: 600;
            margin-bottom: .75rem;
            color: #1a1a1a;
        }

        /* ── Item card ──────────────────────────────────────────────── */
        .item-card {
            background: #fff;
            border-radius: 12px;
            margin-bottom: .75rem;
            display: flex;
            align-items: center;
            padding: .875rem;
            gap: .875rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            cursor: pointer;
            transition: transform .1s;
            border: 1px solid transparent;
        }
        .item-card:active { transform: scale(.98); }
        .item-card.out-of-stock { opacity: .5; pointer-events: none; }
        .item-img {
            width: 80px; height: 80px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }
        .item-img-placeholder {
            width: 80px; height: 80px;
            border-radius: 8px;
            background: #f0f0f0;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .item-info { flex: 1; min-width: 0; }
        .item-name { font-size: .9rem; font-weight: 600; margin-bottom: .2rem; }
        .item-desc { font-size: .75rem; color: #777; line-height: 1.4; margin-bottom: .4rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .item-meta { display: flex; align-items: center; gap: .5rem; }
        .item-price { font-size: .9rem; font-weight: 700; color: #1a1a1a; }
        .item-calories { font-size: .7rem; color: #aaa; }
        .item-add-btn {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: #1a1a1a;
            color: #fff;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .item-qty-ctrl {
            display: flex; align-items: center; gap: .4rem;
            flex-shrink: 0;
        }
        .qty-btn {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 1px solid #ddd;
            background: #fff;
            font-size: .9rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .qty-num { font-size: .85rem; font-weight: 600; min-width: 16px; text-align: center; }

        /* ── Cart drawer ─────────────────────────────────────────────── */
        .cart-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #1a1a1a;
            color: #fff;
            padding: 1rem;
            display: flex; align-items: center; justify-content: space-between;
            z-index: 100;
            cursor: pointer;
            transform: translateY(100%);
            transition: transform .3s;
        }
        .cart-bar.show { transform: translateY(0); }
        .cart-bar-count {
            background: #fff; color: #1a1a1a;
            border-radius: 50%;
            width: 24px; height: 24px;
            font-size: .75rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .cart-bar-label { font-size: .9rem; font-weight: 600; flex: 1; margin-inline-start: .75rem; }
        .cart-bar-total { font-size: .9rem; font-weight: 700; }

        /* ── Cart sheet ──────────────────────────────────────────────── */
        .overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 200;
            opacity: 0; pointer-events: none;
            transition: opacity .25s;
        }
        .overlay.open { opacity: 1; pointer-events: all; }
        .cart-sheet {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #fff;
            border-radius: 20px 20px 0 0;
            z-index: 201;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform .3s;
            padding-bottom: env(safe-area-inset-bottom);
        }
        .cart-sheet.open { transform: translateY(0); }
        .sheet-handle {
            width: 40px; height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin: .75rem auto;
        }
        .sheet-title {
            font-size: 1rem; font-weight: 600;
            padding: 0 1rem .75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .cart-line {
            display: flex; align-items: flex-start;
            padding: .875rem 1rem;
            gap: .75rem;
            border-bottom: 1px solid #f5f5f5;
        }
        .cart-line-info { flex: 1; }
        .cart-line-name { font-size: .85rem; font-weight: 600; }
        .cart-line-mods { font-size: .75rem; color: #888; margin-top: .2rem; }
        .cart-line-price { font-size: .85rem; font-weight: 700; }
        .cart-totals {
            padding: 1rem;
            border-top: 2px solid #f0f0f0;
        }
        .total-row {
            display: flex; justify-content: space-between;
            font-size: .9rem; padding: .3rem 0;
        }
        .total-row.grand { font-weight: 700; font-size: 1rem; padding-top: .6rem; border-top: 1px solid #eee; }

        /* ── Checkout form ───────────────────────────────────────────── */
        .checkout-form { padding: 1rem; }
        .form-group { margin-bottom: .875rem; }
        .form-label { display: block; font-size: .8rem; font-weight: 500; margin-bottom: .35rem; color: #555; }
        .form-input, .form-select {
            width: 100%;
            padding: .65rem .875rem;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: .9rem;
            background: #fafafa;
            appearance: none;
        }
        .form-input:focus, .form-select:focus { outline: none; border-color: #1a1a1a; background: #fff; }
        .pay-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
        .pay-method {
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            padding: .6rem;
            text-align: center;
            font-size: .75rem; font-weight: 600;
            cursor: pointer;
            transition: all .15s;
        }
        .pay-method.selected { border-color: #1a1a1a; background: #1a1a1a; color: #fff; }
        .pay-icon { font-size: 1.1rem; margin-bottom: .2rem; }

        .place-order-btn {
            width: 100%;
            padding: .9rem;
            background: #1a1a1a;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer;
            margin-top: .5rem;
            transition: opacity .15s;
        }
        .place-order-btn:disabled { opacity: .5; cursor: not-allowed; }

        /* ── Modifier modal ──────────────────────────────────────────── */
        .mod-sheet { max-height: 80vh; overflow-y: auto; }
        .mod-group { padding: .875rem 1rem; border-bottom: 1px solid #f5f5f5; }
        .mod-group-title { font-size: .85rem; font-weight: 600; margin-bottom: .5rem; }
        .mod-required { font-size: .7rem; background: #fff3cd; color: #856404; padding: 2px 7px; border-radius: 10px; margin-inline-start: .4rem; }
        .mod-option {
            display: flex; align-items: center; justify-content: space-between;
            padding: .5rem 0;
            cursor: pointer;
        }
        .mod-option input[type="radio"], .mod-option input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: #1a1a1a;
            margin-inline-end: .6rem;
        }
        .mod-option-label { flex: 1; font-size: .85rem; }
        .mod-option-price { font-size: .8rem; color: #1a1a1a; font-weight: 600; }

        /* ── Success screen ──────────────────────────────────────────── */
        .success-screen {
            display: none;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .success-screen.show { display: block; }
        .success-icon { font-size: 4rem; margin-bottom: 1rem; }
        .success-title { font-size: 1.2rem; font-weight: 700; margin-bottom: .5rem; }
        .success-ref { font-size: .85rem; color: #777; }
        .success-msg { font-size: .85rem; color: #555; margin-top: .75rem; line-height: 1.6; }

        /* ── Skeleton loaders ────────────────────────────────────────── */
        @keyframes shimmer { to { background-position: -200% 0; } }
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
            border-radius: 8px;
        }
    </style>
</head>
<body>

{{-- ── Cover ──────────────────────────────────────────────────────────── --}}
<div class="cover">
    @if ($restaurant->cover)
        <img src="{{ asset('assets/images/menu/cover/' . $restaurant->cover) }}" alt="">
    @else
        <div style="width:100%;height:100%;background:linear-gradient(135deg,#1a1a1a,#444)"></div>
    @endif

    <div class="cover-overlay">
        <div class="cover-info">
            @if ($restaurant->logo)
                <img class="cover-logo" src="{{ asset('assets/images/menu/logo/' . $restaurant->logo) }}" alt="">
            @endif
            <div class="cover-name">
                {{ $lang === 'ar' ? $restaurant->name_ar : $restaurant->name_en }}
            </div>
            @if ($table)
                <div class="cover-table">
                    📍 {{ $table->label }}
                </div>
            @endif
        </div>
    </div>

    <button class="lang-btn" onclick="toggleLang()">
        {{ $lang === 'ar' ? 'EN' : 'عربي' }}
    </button>
</div>

{{-- ── Category nav ────────────────────────────────────────────────────── --}}
<nav class="cat-nav" id="catNav">
    @foreach ($categories as $category)
        <button class="cat-chip {{ $loop->first ? 'active' : '' }}"
                onclick="scrollToCategory('cat-{{ $category->id }}')"
                data-cat="{{ $category->id }}">
            {{ $lang === 'ar' ? $category->name_ar : $category->name_en }}
        </button>
    @endforeach
</nav>

{{-- ── Menu ──────────────────────────────────────────────────────────── --}}
<div class="menu-body" id="menuBody">
    @forelse ($categories as $category)
        <div class="cat-section" id="cat-{{ $category->id }}" data-category-id="{{ $category->id }}">
            <div class="cat-section-title">
                {{ $lang === 'ar' ? $category->name_ar : $category->name_en }}
            </div>

            @forelse ($category->availableItems as $item)
                <div class="item-card {{ $item->is_available ? '' : 'out-of-stock' }}"
                     onclick="openItem({{ $item->id }})">

                    @if ($item->image)
                        <img class="item-img"
                             src="{{ asset('assets/images/menu/item/' . $item->image) }}"
                             alt="{{ e($lang === 'ar' ? $item->name_ar : $item->name_en) }}"
                             loading="lazy">
                    @else
                        <div class="item-img-placeholder">🍽️</div>
                    @endif

                    <div class="item-info">
                        <div class="item-name">
                            {{ $lang === 'ar' ? $item->name_ar : $item->name_en }}
                        </div>
                        @if ($lang === 'ar' ? $item->description_ar : $item->description_en)
                            <div class="item-desc">
                                {{ $lang === 'ar' ? $item->description_ar : $item->description_en }}
                            </div>
                        @endif
                        <div class="item-meta">
                            <span class="item-price">
                                {{ $restaurant->currency }}
                                {{ MenuRestaurant::filsToDisplay($item->price_fils, $restaurant->currency) }}
                            </span>
                            @if ($item->calories)
                                <span class="item-calories">{{ $item->calories }} {{ $lang === 'ar' ? 'سعرة' : 'cal' }}</span>
                            @endif
                        </div>
                    </div>

                    <div id="item-ctrl-{{ $item->id }}">
                        @if ($item->is_available)
                            <button class="item-add-btn" onclick="event.stopPropagation(); addItem({{ $item->id }})">+</button>
                        @else
                            <span style="font-size:.75rem;color:#aaa">{{ $lang === 'ar' ? 'نفذ' : 'Sold out' }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p style="font-size:.8rem;color:#aaa;padding:.5rem 0">
                    {{ $lang === 'ar' ? 'لا توجد عناصر' : 'No items available' }}
                </p>
            @endforelse
        </div>
    @empty
        <div style="text-align:center;padding:3rem;color:#aaa">
            {{ $lang === 'ar' ? 'القائمة غير متاحة حالياً' : 'Menu not available right now' }}
        </div>
    @endforelse
</div>

{{-- ── Cart bar ─────────────────────────────────────────────────────────── --}}
<div class="cart-bar" id="cartBar" onclick="openCart()">
    <div class="cart-bar-count" id="cartCount">0</div>
    <div class="cart-bar-label">{{ $lang === 'ar' ? 'عرض السلة' : 'View Cart' }}</div>
    <div class="cart-bar-total" id="cartBarTotal">{{ $restaurant->currency }} 0.000</div>
</div>

{{-- ── Cart sheet ──────────────────────────────────────────────────────── --}}
<div class="overlay" id="overlay" onclick="closeAll()"></div>

<div class="cart-sheet" id="cartSheet">
    <div class="sheet-handle"></div>
    <div class="sheet-title" id="sheetTitle">
        {{ $lang === 'ar' ? 'سلة المشتريات' : 'Your Cart' }}
    </div>

    {{-- Cart items list --}}
    <div id="cartItems"></div>

    {{-- Totals --}}
    <div class="cart-totals" id="cartTotals" style="display:none">
        <div class="total-row">
            <span>{{ $lang === 'ar' ? 'المجموع الفرعي' : 'Subtotal' }}</span>
            <span id="summarySubtotal"></span>
        </div>
        <div class="total-row grand">
            <span>{{ $lang === 'ar' ? 'الإجمالي' : 'Total' }}</span>
            <span id="summaryTotal"></span>
        </div>
    </div>

    {{-- Checkout form --}}
    <div class="checkout-form" id="checkoutForm" style="display:none">
        <div class="form-group">
            <label class="form-label">{{ $lang === 'ar' ? 'الاسم' : 'Your Name' }}</label>
            <input type="text" class="form-input" id="custName"
                   placeholder="{{ $lang === 'ar' ? 'اختياري' : 'Optional' }}"
                   autocomplete="given-name">
        </div>
        <div class="form-group">
            <label class="form-label">
                {{ $lang === 'ar' ? 'رقم الواتساب (لتأكيد الطلب)' : 'WhatsApp Number (for confirmation)' }}
            </label>
            <input type="tel" class="form-input" id="custPhone"
                   placeholder="+965 XXXXXXXX"
                   autocomplete="tel">
        </div>
        <div class="form-group">
            <label class="form-label">{{ $lang === 'ar' ? 'طريقة الدفع' : 'Payment Method' }}</label>
            <div class="pay-methods">
                <div class="pay-method selected" data-method="cash" onclick="selectPayment('cash')">
                    <div class="pay-icon">💵</div>
                    {{ $lang === 'ar' ? 'نقداً' : 'Cash' }}
                </div>
                <div class="pay-method" data-method="knet" onclick="selectPayment('knet')">
                    <div class="pay-icon">💳</div>
                    KNET
                </div>
                <div class="pay-method" data-method="card" onclick="selectPayment('card')">
                    <div class="pay-icon">💳</div>
                    {{ $lang === 'ar' ? 'بطاقة' : 'Card' }}
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">{{ $lang === 'ar' ? 'ملاحظات' : 'Notes' }}</label>
            <input type="text" class="form-input" id="orderNotes" maxlength="200"
                   placeholder="{{ $lang === 'ar' ? 'أي طلبات خاصة؟' : 'Any special requests?' }}">
        </div>
        <button class="place-order-btn" id="placeOrderBtn" onclick="placeOrder()">
            {{ $lang === 'ar' ? '🛒 تأكيد الطلب' : '🛒 Place Order' }}
        </button>
    </div>

    {{-- Success screen --}}
    <div class="success-screen" id="successScreen">
        <div class="success-icon">✅</div>
        <div class="success-title">{{ $lang === 'ar' ? 'تم تقديم طلبك!' : 'Order Placed!' }}</div>
        <div class="success-ref" id="successRef"></div>
        <div class="success-msg" id="successMsg"></div>
    </div>
</div>

{{-- ── Modifier modal ───────────────────────────────────────────────────── --}}
<div class="cart-sheet mod-sheet" id="modSheet">
    <div class="sheet-handle"></div>
    <div class="sheet-title" id="modSheetTitle"></div>
    <div id="modGroups"></div>
    <div style="padding:1rem">
        <button class="place-order-btn" onclick="confirmModifiers()">
            {{ $lang === 'ar' ? 'إضافة للسلة' : 'Add to Cart' }}
        </button>
    </div>
</div>

{{-- ── Item data (server-rendered JSON for JS) ─────────────────────────── --}}
{{-- ⚡ SECURITY: prices embedded here are read-only references for display.
     The server ALWAYS re-calculates totals when the order is submitted. --}}
<script>
const LANG     = @json($lang);
const CURRENCY = @json($restaurant->currency);
const SLUG     = @json($restaurant->slug);
const TABLE_TOKEN = @json($table?->token ?? null);

// Item catalog (display only — server recalculates on submit)
const ITEMS = @json(
    $categories->flatMap->availableItems->keyBy('id')->map(function($item) {
        return [
            'id'           => $item->id,
            'name_ar'      => $item->name_ar,
            'name_en'      => $item->name_en,
            'price_fils'   => $item->price_fils,
            'has_modifiers'=> $item->modifierGroups->isNotEmpty(),
            'modifiers'    => $item->modifierGroups->map(function($g) {
                return [
                    'id'          => $g->id,
                    'name_ar'     => $g->name_ar,
                    'name_en'     => $g->name_en,
                    'type'        => $g->type,
                    'is_required' => (bool) $g->is_required,
                    'options'     => $g->availableOptions->map(function($o) {
                        return [
                            'id'             => $o->id,
                            'name_ar'        => $o->name_ar,
                            'name_en'        => $o->name_en,
                            'price_add_fils' => $o->price_add_fils,
                        ];
                    }),
                ];
            }),
        ];
    })
);

// ── Cart state ─────────────────────────────────────────────────────────
let cart    = [];   // [{item_id, qty, modifier_option_ids, display_price_fils}]
let paymentMethod = 'cash';
let pendingItemId = null;
let idempotencyKey = null;

// ── Language toggle ─────────────────────────────────────────────────────
function toggleLang() {
    const url = new URL(location.href);
    url.searchParams.set('lang', LANG === 'ar' ? 'en' : 'ar');
    location.href = url.toString();
}

// ── Currency format ─────────────────────────────────────────────────────
function formatPrice(fils) {
    const divisor  = CURRENCY === 'KWD' ? 1000 : 100;
    const decimals = CURRENCY === 'KWD' ? 3 : 2;
    return CURRENCY + ' ' + (fils / divisor).toFixed(decimals);
}

// ── Open item (with modifiers) or add directly ─────────────────────────
function openItem(itemId) {
    const item = ITEMS[itemId];
    if (!item) return;

    if (item.has_modifiers) {
        pendingItemId = itemId;
        renderModifierSheet(item);
        openSheet('modSheet');
    } else {
        addItem(itemId, []);
    }
}

function addItem(itemId, modifierOptionIds = []) {
    const item = ITEMS[itemId];
    if (!item) return;

    const modFils = modifierOptionIds.reduce((sum, oid) => {
        for (const g of item.modifiers) {
            const opt = g.options.find(o => o.id === oid);
            if (opt) return sum + opt.price_add_fils;
        }
        return sum;
    }, 0);

    // Check if identical combination already in cart
    const existingIdx = cart.findIndex(l =>
        l.item_id === itemId &&
        JSON.stringify(l.modifier_option_ids.sort()) === JSON.stringify([...modifierOptionIds].sort())
    );

    if (existingIdx >= 0) {
        cart[existingIdx].qty++;
    } else {
        cart.push({
            item_id:             itemId,
            qty:                 1,
            modifier_option_ids: modifierOptionIds,
            display_price_fils:  item.price_fils + modFils,  // display only
        });
    }

    updateCartBar();
    renderItemCtrl(itemId);
}

function removeFromCart(idx) {
    if (cart[idx].qty > 1) {
        cart[idx].qty--;
    } else {
        cart.splice(idx, 1);
    }
    updateCartBar();
    renderCartItems();
}

// ── Modifier sheet ──────────────────────────────────────────────────────
function renderModifierSheet(item) {
    document.getElementById('modSheetTitle').textContent =
        LANG === 'ar' ? item.name_ar : item.name_en;

    const container = document.getElementById('modGroups');
    container.innerHTML = '';

    item.modifiers.forEach(group => {
        const div = document.createElement('div');
        div.className = 'mod-group';

        const reqBadge = group.is_required
            ? `<span class="mod-required">${LANG === 'ar' ? 'مطلوب' : 'Required'}</span>` : '';

        div.innerHTML = `<div class="mod-group-title">
            ${LANG === 'ar' ? group.name_ar : group.name_en}${reqBadge}
        </div>`;

        group.options.forEach(opt => {
            const inputType = group.type === 'multi' ? 'checkbox' : 'radio';
            const priceStr  = opt.price_add_fils !== 0
                ? ' (+' + formatPrice(Math.abs(opt.price_add_fils)) + ')' : '';

            const row = document.createElement('label');
            row.className = 'mod-option';
            row.innerHTML = `
                <input type="${inputType}" name="mod_${group.id}" value="${opt.id}">
                <span class="mod-option-label">${LANG === 'ar' ? opt.name_ar : opt.name_en}</span>
                <span class="mod-option-price">${priceStr}</span>
            `;
            div.appendChild(row);
        });

        container.appendChild(div);
    });
}

function confirmModifiers() {
    const item = ITEMS[pendingItemId];
    if (!item) return;

    let selectedOptionIds = [];
    let valid = true;

    item.modifiers.forEach(group => {
        const inputs = document.querySelectorAll(`[name="mod_${group.id}"]:checked`);
        if (group.is_required && inputs.length === 0) {
            valid = false;
            alert((LANG === 'ar' ? 'يرجى اختيار: ' : 'Please select: ') +
                  (LANG === 'ar' ? group.name_ar : group.name_en));
        }
        inputs.forEach(i => selectedOptionIds.push(parseInt(i.value)));
    });

    if (!valid) return;

    addItem(pendingItemId, selectedOptionIds);
    closeAll();
}

// ── Cart rendering ──────────────────────────────────────────────────────
function renderItemCtrl(itemId) {
    const qty = cart.filter(l => l.item_id === itemId).reduce((s, l) => s + l.qty, 0);
    const ctrl = document.getElementById('item-ctrl-' + itemId);
    if (!ctrl) return;

    if (qty === 0) {
        ctrl.innerHTML = `<button class="item-add-btn" onclick="event.stopPropagation();addItem(${itemId})">+</button>`;
    } else {
        ctrl.innerHTML = `<div class="item-qty-ctrl">
            <button class="qty-btn" onclick="event.stopPropagation();quickRemove(${itemId})">−</button>
            <span class="qty-num">${qty}</span>
            <button class="qty-btn" onclick="event.stopPropagation();addItem(${itemId})">+</button>
        </div>`;
    }
}

function quickRemove(itemId) {
    const idx = cart.findLastIndex(l => l.item_id === itemId);
    if (idx >= 0) removeFromCart(idx);
    renderItemCtrl(itemId);
}

function renderCartItems() {
    const container = document.getElementById('cartItems');
    if (cart.length === 0) {
        container.innerHTML = `<p style="text-align:center;padding:2rem;color:#aaa">${LANG === 'ar' ? 'السلة فارغة' : 'Your cart is empty'}</p>`;
        document.getElementById('cartTotals').style.display = 'none';
        document.getElementById('checkoutForm').style.display = 'none';
        return;
    }

    let html = '';
    cart.forEach((line, idx) => {
        const item = ITEMS[line.item_id];
        const name = LANG === 'ar' ? item.name_ar : item.name_en;
        const mods = line.modifier_option_ids.map(oid => {
            for (const g of item.modifiers) {
                const o = g.options.find(o => o.id === oid);
                if (o) return LANG === 'ar' ? o.name_ar : o.name_en;
            }
            return '';
        }).filter(Boolean).join(', ');

        html += `<div class="cart-line">
            <div class="cart-line-info">
                <div class="cart-line-name">${name} × ${line.qty}</div>
                ${mods ? `<div class="cart-line-mods">${mods}</div>` : ''}
            </div>
            <div>
                <div class="cart-line-price">${formatPrice(line.display_price_fils * line.qty)}</div>
                <div style="display:flex;gap:.4rem;margin-top:.4rem;justify-content:flex-end">
                    <button class="qty-btn" onclick="removeFromCart(${idx});renderItemCtrl(${line.item_id})">−</button>
                    <button class="qty-btn" onclick="addItem(${line.item_id});renderItemCtrl(${line.item_id})">+</button>
                </div>
            </div>
        </div>`;
    });

    container.innerHTML = html;

    const subtotal = cart.reduce((s, l) => s + l.display_price_fils * l.qty, 0);
    document.getElementById('summarySubtotal').textContent = formatPrice(subtotal);
    document.getElementById('summaryTotal').textContent    = formatPrice(subtotal);
    document.getElementById('cartTotals').style.display    = 'block';
    document.getElementById('checkoutForm').style.display  = 'block';
}

function updateCartBar() {
    const count    = cart.reduce((s, l) => s + l.qty, 0);
    const subtotal = cart.reduce((s, l) => s + l.display_price_fils * l.qty, 0);

    document.getElementById('cartCount').textContent   = count;
    document.getElementById('cartBarTotal').textContent = formatPrice(subtotal);

    const bar = document.getElementById('cartBar');
    if (count > 0) bar.classList.add('show');
    else           bar.classList.remove('show');
}

// ── Sheet control ───────────────────────────────────────────────────────
function openCart() {
    renderCartItems();
    openSheet('cartSheet');
}

function openSheet(id) {
    document.getElementById('overlay').classList.add('open');
    document.getElementById(id).classList.add('open');
}

function closeAll() {
    document.getElementById('overlay').classList.remove('open');
    document.querySelectorAll('.cart-sheet').forEach(s => s.classList.remove('open'));
}

// ── Payment method ──────────────────────────────────────────────────────
function selectPayment(method) {
    paymentMethod = method;
    document.querySelectorAll('.pay-method').forEach(el => {
        el.classList.toggle('selected', el.dataset.method === method);
    });
}

// ── Place order ─────────────────────────────────────────────────────────
async function placeOrder() {
    if (cart.length === 0) return;

    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.textContent = LANG === 'ar' ? 'جاري الإرسال...' : 'Placing order...';

    // Generate idempotency key once per page session
    if (!idempotencyKey) {
        idempotencyKey = crypto.randomUUID();
    }

    // ⚡ SECURITY: send only item IDs, quantities, modifier IDs — never prices
    const payload = {
        idempotency_key:  idempotencyKey,
        customer_name:    document.getElementById('custName').value.trim().slice(0, 100),
        customer_phone:   document.getElementById('custPhone').value.trim(),
        notes:            document.getElementById('orderNotes').value.trim().slice(0, 200),
        payment_method:   paymentMethod,
        table_token:      TABLE_TOKEN || undefined,
        items: cart.map(l => ({
            item_id:             l.item_id,
            qty:                 l.qty,
            modifier_option_ids: l.modifier_option_ids,
        })),
    };

    try {
        const res = await fetch(`/menu/${SLUG}/order`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (data.success) {
            // Show success
            document.getElementById('cartItems').style.display    = 'none';
            document.getElementById('cartTotals').style.display   = 'none';
            document.getElementById('checkoutForm').style.display = 'none';

            document.getElementById('successRef').textContent  = (LANG === 'ar' ? 'رقم الطلب: ' : 'Order ref: ') + data.order_ref;
            document.getElementById('successMsg').textContent  = data.message;
            document.getElementById('successScreen').classList.add('show');

            // Reset cart
            cart = [];
            updateCartBar();
            document.querySelectorAll('.item-qty-ctrl').forEach(el => {
                const itemId = el.closest('[id^="item-ctrl-"]')?.id?.replace('item-ctrl-', '');
                if (itemId) renderItemCtrl(parseInt(itemId));
            });
        } else {
            alert(data.message || (LANG === 'ar' ? 'حدث خطأ' : 'Something went wrong.'));
            btn.disabled = false;
            btn.textContent = LANG === 'ar' ? '🛒 تأكيد الطلب' : '🛒 Place Order';
        }
    } catch (err) {
        alert(LANG === 'ar' ? 'تعذر الاتصال. تحقق من الإنترنت.' : 'Connection error. Check your internet.');
        btn.disabled = false;
        btn.textContent = LANG === 'ar' ? '🛒 تأكيد الطلب' : '🛒 Place Order';
    }
}

// ── Category scroll spy ─────────────────────────────────────────────────
function scrollToCategory(id) {
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const catId = entry.target.dataset.categoryId;
            document.querySelectorAll('.cat-chip').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.cat === catId);
            });
        }
    });
}, { threshold: 0.3 });

document.querySelectorAll('[data-category-id]').forEach(el => observer.observe(el));
</script>
</body>
</html>
