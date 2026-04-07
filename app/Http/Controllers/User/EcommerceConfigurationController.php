<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\ManageWooCommerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcommerceConfigurationController extends Controller
{
    use ManageWooCommerce;

    /**
     * Normalize phone number format.
     *
     * Rules:
     * - Remove any non-numeric characters except "+"
     * - Convert numbers starting with "00" to "+"
     * - Keep local numbers if no country code is provided
     */
    private function normalizePhone($phone)
    {
        if (!$phone) {
            return null;
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (strpos($phone, '00') === 0) {
            $phone = '+' . substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Build RFM segments from real commerce orders and customers data.
     */
    private function getRfmSegments()
    {
        $rows = DB::table('commerce_orders')
            ->select(
                'commerce_customer_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(order_total) as total_spent'),
                DB::raw('MAX(ordered_at) as last_order_at')
            )
            ->where('user_id', auth()->id())
            ->groupBy('commerce_customer_id')
            ->get();

        return $rows->map(function ($row) {
            $customer = DB::table('commerce_customers')
                ->where('id', $row->commerce_customer_id)
                ->where('user_id', auth()->id())
                ->select('customer_name', 'phone', 'provider', 'last_order_number', 'last_order_at')
                ->first();

            $lastOrderAt = \Carbon\Carbon::parse($row->last_order_at);

            $days = $lastOrderAt->lessThan(now())
                ? (int) floor($lastOrderAt->diffInDays(now()))
                : 0;

            if ((float) $row->total_spent > 300 && (int) $row->total_orders >= 5 && $days <= 7) {
                $segment = 'VIP';
            } elseif ((int) $row->total_orders >= 3 && $days <= 14) {
                $segment = 'Loyal';
            } elseif ($days <= 3) {
                $segment = 'New';
            } elseif ($days > 14 && (int) $row->total_orders >= 2) {
                $segment = 'At Risk';
            } else {
                $segment = 'Lost';
            }

            return [
                'customer_id' => $row->commerce_customer_id,
                'customer_name' => $customer->customer_name ?? 'Unknown',
                'phone' => isset($customer->phone) ? ltrim((string) $customer->phone, '+') : null,
                'provider' => $customer->provider ?? null,
                'last_order_number' => $customer->last_order_number ?? null,
                'last_order_at' => $customer->last_order_at ?? null,
                'orders' => (int) $row->total_orders,
                'spent' => (float) $row->total_spent,
                'days_since_last_order' => $days,
                'segment' => $segment,
            ];
        })->values();
    }

    /**
     * Fetch products based on the selected ecommerce channel.
     *
     * Current supported channel:
     * - WooCommerce
     */
    public function fetchProducts(Request $request)
    {
        $channel = $request->channel;

        if ($channel === 'woocommerce') {
            return $this->fetchWoocommerceProducts($request);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid channel',
        ], 400);
    }

    /**
     * Display the unified customers page.
     */
    public function unifiedCustomers(Request $request)
{
    $pageTitle = 'Customers';

    $provider = $request->provider;
    $search   = trim((string) $request->search);
    $segment  = trim((string) $request->segment);
    $sort     = trim((string) $request->sort);

    $customers = DB::table('commerce_customers')
        ->where('user_id', auth()->id())
        ->when($provider && in_array($provider, ['woocommerce', 'shopify']), function ($query) use ($provider) {
            $query->where('provider', $provider);
        })
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('last_order_number', 'like', "%{$search}%");
            });
        });

    $customers = $customers->get()->map(function ($customer) {
        $lastOrderAt = $customer->last_order_at ? \Carbon\Carbon::parse($customer->last_order_at) : null;

        $days = $lastOrderAt
            ? ($lastOrderAt->lessThan(now()) ? (int) floor($lastOrderAt->diffInDays(now())) : 0)
            : 9999;

        if ((float) ($customer->total_spent ?? 0) > 300 && (int) ($customer->orders_count ?? 0) >= 5 && $days <= 7) {
            $customer->segment = 'VIP';
        } elseif ((int) ($customer->orders_count ?? 0) >= 3 && $days <= 14) {
            $customer->segment = 'Loyal';
        } elseif ($days <= 3) {
            $customer->segment = 'New';
        } elseif ($days > 14 && (int) ($customer->orders_count ?? 0) >= 2) {
            $customer->segment = 'At Risk';
        } else {
            $customer->segment = 'Lost';
        }

        return $customer;
    });

    // filter by segment
    if ($segment && in_array($segment, ['VIP', 'Loyal', 'New', 'At Risk', 'Lost'])) {
        $customers = $customers->where('segment', $segment)->values();
    }

    // sorting
    switch ($sort) {
        case 'orders_desc':
            $customers = $customers->sortByDesc('orders_count')->values();
            break;

        case 'spent_desc':
            $customers = $customers->sortByDesc('total_spent')->values();
            break;

        case 'last_order_desc':
            $customers = $customers->sortByDesc('last_order_at')->values();
            break;

        case 'name_asc':
            $customers = $customers->sortBy('customer_name')->values();
            break;

        default:
            $customers = $customers->sortByDesc('id')->values();
            break;
    }

    // pagination
    $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
    $perPage = 20;
    $currentItems = $customers->slice(($currentPage - 1) * $perPage, $perPage)->values();

    $customers = new \Illuminate\Pagination\LengthAwarePaginator(
        $currentItems,
        $customers->count(),
        $perPage,
        $currentPage,
        [
            'path' => request()->url(),
            'query' => request()->query(),
        ]
    );

    // stats
    $stats = [
        'total' => DB::table('commerce_customers')
            ->where('user_id', auth()->id())
            ->count(),

        'woocommerce' => DB::table('commerce_customers')
            ->where('user_id', auth()->id())
            ->where('provider', 'woocommerce')
            ->count(),

        'shopify' => DB::table('commerce_customers')
            ->where('user_id', auth()->id())
            ->where('provider', 'shopify')
            ->count(),
    ];

    return view('templates.basic.user.ecommerce.customers', compact(
        'pageTitle',
        'customers',
        'stats',
        'provider',
        'search',
        'segment',
        'sort'
    ));
}

    /**
     * Display the unified orders page.
     */
    public function unifiedOrders(Request $request)
    {
        $pageTitle = 'Orders';

        $provider = $request->provider;
        $search   = trim((string) $request->search);

        $orders = DB::table('commerce_orders as co')
            ->leftJoin('commerce_customers as cc', 'cc.id', '=', 'co.commerce_customer_id')
            ->where('co.user_id', auth()->id())
            ->when($provider && in_array($provider, ['woocommerce', 'shopify']), function ($query) use ($provider) {
                $query->where('co.provider', $provider);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('co.order_number', 'like', "%{$search}%")
                        ->orWhere('co.external_order_id', 'like', "%{$search}%")
                        ->orWhere('co.order_status', 'like', "%{$search}%")
                        ->orWhere('co.currency', 'like', "%{$search}%")
                        ->orWhere('cc.customer_name', 'like', "%{$search}%")
                        ->orWhere('cc.phone', 'like', "%{$search}%");
                });
            })
            ->select(
                'co.*',
                'cc.customer_name',
                'cc.phone',
                'cc.email'
            )
            ->orderByDesc('co.id')
            ->paginate(20)
            ->appends($request->all());

        $stats = [
            'total' => DB::table('commerce_orders')
                ->where('user_id', auth()->id())
                ->count(),

            'woocommerce' => DB::table('commerce_orders')
                ->where('user_id', auth()->id())
                ->where('provider', 'woocommerce')
                ->count(),

            'shopify' => DB::table('commerce_orders')
                ->where('user_id', auth()->id())
                ->where('provider', 'shopify')
                ->count(),
        ];

        return view('templates.basic.user.ecommerce.orders', compact(
            'pageTitle',
            'orders',
            'stats',
            'provider',
            'search'
        ));
    }

    /**
     * Display the unified sync center page.
     */
    public function syncCenter()
    {
        $pageTitle = 'Sync Center';

        $stats = [
            'woocommerce_configs' => DB::table('ecommerce_configurations')
                ->where('user_id', auth()->id())
                ->where('provider', 1)
                ->where('status', 1)
                ->count(),

            'shopify_stores' => DB::table('shopify_stores')
                ->where('user_id', auth()->id())
                ->count(),

            'customers' => DB::table('commerce_customers')
                ->where('user_id', auth()->id())
                ->count(),

            'orders' => DB::table('commerce_orders')
                ->where('user_id', auth()->id())
                ->count(),
        ];

        $wooConfig = DB::table('ecommerce_configurations')
            ->where('user_id', auth()->id())
            ->where('provider', 1)
            ->where('status', 1)
            ->orderBy('id')
            ->first();

        $shopifyStore = DB::table('shopify_stores')
            ->where('user_id', auth()->id())
            ->orderBy('id')
            ->first();

        return view('templates.basic.user.ecommerce.sync_center', compact(
            'pageTitle',
            'stats',
            'wooConfig',
            'shopifyStore'
        ));
    }

    /**
     * Display the unified catalog page.
     */
    public function catalog(Request $request)
    {
        $pageTitle = 'Catalog';

        $stats = [
            'woocommerce_configs' => DB::table('ecommerce_configurations')
                ->where('user_id', auth()->id())
                ->where('provider', 1)
                ->where('status', 1)
                ->count(),

            'shopify_stores' => DB::table('shopify_stores')
                ->where('user_id', auth()->id())
                ->count(),

            'customers' => DB::table('commerce_customers')
                ->where('user_id', auth()->id())
                ->count(),

            'orders' => DB::table('commerce_orders')
                ->where('user_id', auth()->id())
                ->count(),
        ];

        $shopifyStore = DB::table('shopify_stores')
            ->where('user_id', auth()->id())
            ->orderBy('id')
            ->first();

        return view('templates.basic.user.ecommerce.catalog', compact(
            'pageTitle',
            'stats',
            'shopifyStore'
        ));
    }


    /**
     * Handle WooCommerce order webhook.
     *
     * Main responsibilities of this method:
     * 1. Identify which WooCommerce store sent the webhook
     * 2. Match that store with a saved ecommerce configuration
     * 3. Extract customer and order data from the payload
     * 4. Save/update unified commerce customer record
     * 5. Save/update unified commerce order record
     * 6. Prevent duplicate WhatsApp template sending for the same new order
     * 7. Send WhatsApp status update if an existing order status changed
     * 8. Write message delivery logs into commerce_message_logs
     *
     * Important note:
     * This method currently handles WooCommerce only.
     * Shopify uses its own webhook flow/controller.
     */
    public function wooOrderWebhook(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('WooCommerce Webhook Received', $data);

            /**
             * ------------------------------------------------------------------
             * STEP 1: Detect the source store URL
             * ------------------------------------------------------------------
             */
            $storeUrl = $request->header('x-wc-webhook-source')
                ?? $request->get('store_url')
                ?? $request->get('domain')
                ?? null;

            if (!$storeUrl) {
                Log::warning('WooCommerce webhook: store URL not found');

                return response()->json([
                    'status' => 'store_not_found',
                ], 400);
            }

            $normalizedStoreUrl = trim($storeUrl);
            $normalizedStoreUrl = preg_replace('#^https?://#', '', $normalizedStoreUrl);
            $normalizedStoreUrl = rtrim($normalizedStoreUrl, '/');

            /**
             * ------------------------------------------------------------------
             * STEP 2: Match the incoming store with saved WooCommerce config
             * ------------------------------------------------------------------
             */
            $configs = DB::table('ecommerce_configurations')
                ->where('provider', 1)
                ->where('status', 1)
                ->get();

            $matchedConfig = null;

            foreach ($configs as $config) {
                $configData = json_decode($config->config, true);

                if (!empty($configData['domain_name'])) {
                    $savedDomain = trim($configData['domain_name']);
                    $savedDomain = preg_replace('#^https?://#', '', $savedDomain);
                    $savedDomain = rtrim($savedDomain, '/');

                    if (strtolower($savedDomain) === strtolower($normalizedStoreUrl)) {
                        $matchedConfig = $config;
                        break;
                    }
                }
            }

            if (!$matchedConfig) {
                Log::warning('WooCommerce webhook: no matching ecommerce configuration found', [
                    'incoming_store' => $normalizedStoreUrl,
                ]);

                return response()->json([
                    'status' => 'config_not_found',
                ], 404);
            }

            /**
             * ------------------------------------------------------------------
             * STEP 3: Extract customer and order details from webhook payload
             * ------------------------------------------------------------------
             */
            $phone = $data['billing']['phone'] ?? null;

            $customerName = trim(
                ($data['billing']['first_name'] ?? 'Customer') . ' ' . ($data['billing']['last_name'] ?? '')
            );
            $customerName = trim($customerName) ?: 'Customer';

            $email = $data['billing']['email'] ?? null;
            $externalCustomerId = isset($data['customer_id']) ? (string) $data['customer_id'] : null;
            $externalOrderId    = isset($data['id']) ? (string) $data['id'] : null;
            $orderNumber        = isset($data['number']) ? '#' . $data['number'] : ($externalOrderId ?: 'Order');
            $orderTotal         = (float) ($data['total'] ?? 0);
            $currency           = $data['currency'] ?? null;
            $orderStatus        = $data['status'] ?? 'created';
            $orderedAt          = !empty($data['date_created'])
                ? date('Y-m-d H:i:s', strtotime($data['date_created']))
                : now();

            if (!$phone) {
                Log::warning('WooCommerce webhook: customer phone not found', [
                    'store_ref' => $matchedConfig->id,
                    'order_id' => $externalOrderId,
                ]);

                return response()->json([
                    'status' => 'ignored_no_phone',
                ], 200);
            }

            $phone = ltrim($this->normalizePhone($phone), '+');

            /**
             * ------------------------------------------------------------------
             * STEP 4: Detect duplicate orders
             * ------------------------------------------------------------------
             */
            $existingOrder = DB::table('commerce_orders')
                ->where('user_id', $matchedConfig->user_id)
                ->where('provider', 'woocommerce')
                ->where('store_ref', $matchedConfig->id)
                ->where('external_order_id', $externalOrderId)
                ->first();

            /**
             * ------------------------------------------------------------------
             * STEP 5: Create or update unified commerce customer
             * ------------------------------------------------------------------
             */
            $commerceCustomer = DB::table('commerce_customers')
                ->where('user_id', $matchedConfig->user_id)
                ->where('provider', 'woocommerce')
                ->where('store_ref', $matchedConfig->id)
                ->where('phone', $phone)
                ->first();

            if ($commerceCustomer) {
                $newOrdersCount = (int) $commerceCustomer->orders_count;
                $newTotalSpent  = (float) $commerceCustomer->total_spent;

                if (!$existingOrder) {
                    $newOrdersCount += 1;
                    $newTotalSpent += $orderTotal;
                }

                DB::table('commerce_customers')
                    ->where('id', $commerceCustomer->id)
                    ->update([
                        'external_customer_id' => $externalCustomerId ?: $commerceCustomer->external_customer_id,
                        'customer_name' => $customerName,
                        'email' => $email,
                        'orders_count' => $newOrdersCount,
                        'total_spent' => $newTotalSpent,
                        'last_order_number' => $orderNumber,
                        'last_order_at' => $orderedAt,
                        'updated_at' => now(),
                    ]);

                $commerceCustomerId = $commerceCustomer->id;
            } else {
                $commerceCustomerId = DB::table('commerce_customers')->insertGetId([
                    'user_id' => $matchedConfig->user_id,
                    'provider' => 'woocommerce',
                    'store_ref' => $matchedConfig->id,
                    'external_customer_id' => $externalCustomerId,
                    'customer_name' => $customerName,
                    'phone' => $phone,
                    'email' => $email,
                    'orders_count' => 1,
                    'total_spent' => $orderTotal,
                    'last_order_number' => $orderNumber,
                    'last_order_at' => $orderedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /**
             * ------------------------------------------------------------------
             * STEP 6: Detect status change before updating the order row
             * ------------------------------------------------------------------
             */
            $oldStatus     = $existingOrder->order_status ?? null;
            $newStatus     = $orderStatus;
            $statusChanged = $existingOrder && $oldStatus !== $newStatus;

            /**
             * ------------------------------------------------------------------
             * STEP 7: Create or update unified commerce order
             * ------------------------------------------------------------------
             */
            if ($existingOrder) {
                $commerceOrderId = $existingOrder->id;

                DB::table('commerce_orders')
                    ->where('id', $commerceOrderId)
                    ->update([
                        'commerce_customer_id' => $commerceCustomerId,
                        'order_number' => $orderNumber,
                        'order_total' => $orderTotal,
                        'currency' => $currency,
                        'order_status' => $orderStatus,
                        'raw_payload' => json_encode($data),
                        'ordered_at' => $orderedAt,
                        'updated_at' => now(),
                    ]);
            } else {
                $commerceOrderId = DB::table('commerce_orders')->insertGetId([
                    'user_id' => $matchedConfig->user_id,
                    'provider' => 'woocommerce',
                    'store_ref' => $matchedConfig->id,
                    'commerce_customer_id' => $commerceCustomerId,
                    'external_order_id' => $externalOrderId,
                    'order_number' => $orderNumber,
                    'order_total' => $orderTotal,
                    'currency' => $currency,
                    'order_status' => $orderStatus,
                    'raw_payload' => json_encode($data),
                    'ordered_at' => $orderedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /**
             * ------------------------------------------------------------------
             * STEP 8: Resolve the WhatsApp account to send from
             * ------------------------------------------------------------------
             */
            $whatsappAccount = DB::table('whatsapp_accounts')
                ->where('user_id', $matchedConfig->user_id)
                ->whereNotNull('access_token')
                ->whereNotNull('phone_number_id')
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            if (!$whatsappAccount || !$whatsappAccount->access_token || !$whatsappAccount->phone_number_id) {
                DB::table('commerce_message_logs')->insert([
                    'user_id' => $matchedConfig->user_id,
                    'provider' => 'woocommerce',
                    'store_ref' => $matchedConfig->id,
                    'commerce_customer_id' => $commerceCustomerId,
                    'commerce_order_id' => $commerceOrderId,
                    'customer_name' => $customerName,
                    'phone' => $phone,
                    'message_type' => 'template',
                    'message_template' => $existingOrder ? 'order_status_update' : 'order_confirmation',
                    'status' => 'failed',
                    'response' => 'WhatsApp account not configured.',
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'status' => 'invalid_whatsapp',
                ], 400);
            }

            /**
             * ------------------------------------------------------------------
             * STEP 9: If this is an existing order and the status changed,
             * send a plain text status update and log it.
             * ------------------------------------------------------------------
             */
            if ($statusChanged) {
                $statusResponse = \Illuminate\Support\Facades\Http::withToken($whatsappAccount->access_token)
                    ->post("https://graph.facebook.com/v18.0/{$whatsappAccount->phone_number_id}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $phone,
                        'type' => 'text',
                        'text' => [
                            'body' => "Your order {$orderNumber} status updated to: {$newStatus}",
                        ],
                    ]);

                $statusResponseData = $statusResponse->json();
                $statusMessageId    = $statusResponseData['messages'][0]['id'] ?? null;
                $statusError        = $statusResponseData['error']['message'] ?? null;

                DB::table('commerce_message_logs')->insert([
                    'user_id' => $matchedConfig->user_id,
                    'provider' => 'woocommerce',
                    'store_ref' => $matchedConfig->id,
                    'commerce_customer_id' => $commerceCustomerId,
                    'commerce_order_id' => $commerceOrderId,
                    'customer_name' => $customerName,
                    'phone' => $phone,
                    'message_type' => 'text',
                    'message_template' => 'order_status_update',
                    'status' => $statusResponse->successful() ? 'sent' : 'failed',
                    'response' => $statusMessageId
                        ? "Message ID: {$statusMessageId}"
                        : ($statusError ?? 'Unknown response'),
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('WooCommerce order status update processed', [
                    'store_ref' => $matchedConfig->id,
                    'order_id' => $commerceOrderId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'message_status' => $statusResponse->successful() ? 'sent' : 'failed',
                ]);

                return response()->json([
                    'status' => $statusResponse->successful() ? 'status_sent' : 'status_failed',
                    'store_ref' => $matchedConfig->id,
                    'customer_id' => $commerceCustomerId,
                    'order_id' => $commerceOrderId,
                    'existing_order' => true,
                ]);
            }

            /**
             * ------------------------------------------------------------------
             * STEP 10: Stop here if order already exists and no new status update
             * is needed.
             * ------------------------------------------------------------------
             */
            if ($existingOrder) {
                Log::info('WooCommerce webhook skipped duplicate order send', [
                    'store_ref' => $matchedConfig->id,
                    'order_id' => $commerceOrderId,
                    'external_order_id' => $externalOrderId,
                ]);

                return response()->json([
                    'status' => 'success',
                    'store_ref' => $matchedConfig->id,
                    'customer_id' => $commerceCustomerId,
                    'order_id' => $commerceOrderId,
                    'existing_order' => true,
                ]);
            }

            /**
             * ------------------------------------------------------------------
             * STEP 11: Validate required WhatsApp template for new orders
             * ------------------------------------------------------------------
             */
            $template = DB::table('templates')
                ->where('whatsapp_account_id', $whatsappAccount->id)
                ->where('name', 'order_confirmation')
                ->where('status', 1)
                ->first();

            if (!$template) {
                DB::table('commerce_message_logs')->insert([
                    'user_id' => $matchedConfig->user_id,
                    'provider' => 'woocommerce',
                    'store_ref' => $matchedConfig->id,
                    'commerce_customer_id' => $commerceCustomerId,
                    'commerce_order_id' => $commerceOrderId,
                    'customer_name' => $customerName,
                    'phone' => $phone,
                    'message_type' => 'template',
                    'message_template' => 'order_confirmation',
                    'status' => 'failed',
                    'response' => 'Template order_confirmation not found for this WhatsApp account.',
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'status' => 'template_not_found',
                ], 400);
            }

            /**
             * ------------------------------------------------------------------
             * STEP 12: Send WhatsApp order confirmation template for new orders
             * ------------------------------------------------------------------
             */
            $response = \Illuminate\Support\Facades\Http::withToken($whatsappAccount->access_token)
                ->post("https://graph.facebook.com/v18.0/{$whatsappAccount->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'template',
                    'template' => [
                        'name' => 'order_confirmation',
                        'language' => ['code' => 'en_US'],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $customerName],
                                ['type' => 'text', 'text' => $orderNumber],
                            ],
                        ]],
                    ],
                ]);

            $responseData = $response->json();
            $messageId    = $responseData['messages'][0]['id'] ?? null;
            $errorMsg     = $responseData['error']['message'] ?? null;

            DB::table('commerce_message_logs')->insert([
                'user_id' => $matchedConfig->user_id,
                'provider' => 'woocommerce',
                'store_ref' => $matchedConfig->id,
                'commerce_customer_id' => $commerceCustomerId,
                'commerce_order_id' => $commerceOrderId,
                'customer_name' => $customerName,
                'phone' => $phone,
                'message_type' => 'template',
                'message_template' => 'order_confirmation',
                'status' => $response->successful() ? 'sent' : 'failed',
                'response' => $messageId
                    ? "Message ID: {$messageId}"
                    : ($errorMsg ?? 'Unknown response'),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('WooCommerce webhook processed successfully', [
                'store_ref' => $matchedConfig->id,
                'customer_id' => $commerceCustomerId,
                'order_id' => $commerceOrderId,
                'message_status' => $response->successful() ? 'sent' : 'failed',
            ]);

            return response()->json([
                'status' => $response->successful() ? 'sent' : 'failed',
                'store_ref' => $matchedConfig->id,
                'customer_id' => $commerceCustomerId,
                'order_id' => $commerceOrderId,
                'existing_order' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('WooCommerce Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the WooCommerce-specific message logs page.
     */
    public function wooCommerceLogs(Request $request)
    {
        $pageTitle = 'WooCommerce Logs';

        $logs = DB::table('commerce_message_logs as cml')
            ->leftJoin('commerce_orders as co', 'co.id', '=', 'cml.commerce_order_id')
            ->where('cml.user_id', auth()->id())
            ->where('cml.provider', 'woocommerce')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('cml.customer_name', 'like', "%{$search}%")
                        ->orWhere('cml.phone', 'like', "%{$search}%")
                        ->orWhere('co.order_number', 'like', "%{$search}%")
                        ->orWhere('cml.status', 'like', "%{$search}%");
                });
            })
            ->select(
                'cml.*',
                'co.order_number',
                'co.order_status',
                'co.currency',
                'co.order_total'
            )
            ->orderByDesc('cml.id')
            ->paginate(20);

        return view('templates.basic.user.ecommerce.woo_commerce_logs', compact(
            'pageTitle',
            'logs'
        ));
    }

    /**
     * Display the top-level RFM dashboard page.
     */
    public function rfmDashboard()
    {
        $pageTitle = 'RFM Dashboard';
        $segments  = $this->getRfmSegments();

        return view('templates.basic.user.ecommerce.rfm.dashboard', compact('pageTitle', 'segments'));
    }

    /**
     * Display the top-level segments page.
     */
    public function segments()
    {
        $pageTitle = 'Segments';
        $segments  = $this->getRfmSegments();

        return view('templates.basic.user.ecommerce.segments.index', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the analytics placeholder page.
     */
    public function analytics()
    {
        $pageTitle = 'Analytics';

        return view('templates.basic.user.ecommerce.analytics.index', compact('pageTitle'));
    }

    /**
     * Display the health check placeholder page.
     */
    public function healthCheck()
    {
        $pageTitle = 'Health Check';

        return view('templates.basic.user.ecommerce.health.index', compact('pageTitle'));
    }

    /**
     * Display the VIP customers RFM page using real data.
     */
    public function rfmVipCustomers()
    {
        $pageTitle = 'VIP Customers';
        $segments  = $this->getRfmSegments()->where('segment', 'VIP')->values();

        return view('templates.basic.user.ecommerce.rfm.vip_customers', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the loyal customers RFM page.
     */
    public function rfmLoyalCustomers()
    {
        $pageTitle = 'Loyal Customers';
        $segments  = $this->getRfmSegments()->where('segment', 'Loyal')->values();

        return view('templates.basic.user.ecommerce.rfm.loyal_customers', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the new customers RFM page.
     */
    public function rfmNewCustomers()
    {
        $pageTitle = 'New Customers';
        $segments  = $this->getRfmSegments()->where('segment', 'New')->values();

        return view('templates.basic.user.ecommerce.rfm.new_customers', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the at-risk customers RFM page.
     */
    public function rfmAtRisk()
    {
        $pageTitle = 'At Risk';
        $segments  = $this->getRfmSegments()->where('segment', 'At Risk')->values();

        return view('templates.basic.user.ecommerce.rfm.at_risk', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the lost customers RFM page.
     */
    public function rfmLostCustomers()
    {
        $pageTitle = 'Lost Customers';
        $segments  = $this->getRfmSegments()->where('segment', 'Lost')->values();

        return view('templates.basic.user.ecommerce.rfm.lost_customers', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the customer filters segment page.
     */
    public function segmentCustomerFilters()
    {
        $pageTitle = 'Customer Filters';
        $segments  = $this->getRfmSegments();

        return view('templates.basic.user.ecommerce.segments.customer_filters', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the buyers segment page.
     */
    public function segmentBuyers()
    {
        $pageTitle = 'Buyers';
        $segments  = $this->getRfmSegments();

        return view('templates.basic.user.ecommerce.segments.buyers', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the repeat customers segment page.
     */
    public function segmentRepeatCustomers()
    {
        $pageTitle = 'Repeat Customers';
        $minOrders = request()->get('min_orders', 2);

        $segments = $this->getRfmSegments()
            ->filter(function ($item) use ($minOrders) {
                return $item['orders'] >= (int) $minOrders;
            })
            ->values();

        return view('templates.basic.user.ecommerce.segments.repeat_customers', compact(
            'pageTitle',
            'segments',
            'minOrders'
        ));
    }

    /**
     * Display the high value customers segment page.
     */
    public function segmentHighValueCustomers()
    {
        $pageTitle = 'High Value Customers';
        $segments  = $this->getRfmSegments()->where('spent', '>', 300)->values();

        return view('templates.basic.user.ecommerce.segments.high_value_customers', compact(
            'pageTitle',
            'segments'
        ));
    }

    /**
     * Display the abandoned cart segment page.
     */
    public function segmentAbandonedCart()
    {
        $pageTitle = 'Abandoned Cart';

        return view('templates.basic.user.ecommerce.segments.abandoned_cart', compact('pageTitle'));
    }

    /**
     * Display the message history segment page.
     */
    public function segmentMessageHistory()
    {
        $pageTitle = 'Message History';

        $logs = DB::table('commerce_message_logs')
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        return view('templates.basic.user.ecommerce.segments.message_history', compact(
            'pageTitle',
            'logs'
        ));
    }

    /**
     * Manual WooCommerce order sync placeholder endpoint.
     */
    public function syncWooOrders(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'WooCommerce orders sync endpoint is ready. Logic can be connected next.',
        ]);
    }

    /**
     * Sync WooCommerce customers using ORDERS endpoint.
     *
     * Why orders:
     * - billing.phone is usually better than customers endpoint
     * - billing.email is available more often
     * - current Woo customers API contains many incomplete rows
     *
     * Matching priority:
     * 1. external_customer_id
     * 2. phone
     * 3. email
     */
    public function syncWooCustomers(Request $request)
    {
        try {
            $userId = auth()->id();

            $config = DB::table('ecommerce_configurations')
                ->where('user_id', $userId)
                ->where('provider', 1)
                ->where('status', 1)
                ->first();

            if (!$config) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please connect WooCommerce store first.',
                ]);
            }

            $cfg = json_decode($config->config, true);

            if (
                empty($cfg['domain_name']) ||
                empty($cfg['consumer_key']) ||
                empty($cfg['consumer_secret'])
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'WooCommerce configuration is incomplete.',
                ]);
            }

            $domain = rtrim($cfg['domain_name'], '/');

            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                $cfg['consumer_key'],
                $cfg['consumer_secret']
            )->get($domain . '/wp-json/wc/v3/orders?per_page=50');

            if ($response->failed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to fetch WooCommerce orders.',
                ]);
            }

            $orders = $response->json();

            $imported = 0;
            $updated  = 0;

            foreach ($orders as $order) {
                $customerId = $order['customer_id'] ?? null;
                $billing    = $order['billing'] ?? [];
                $shipping   = $order['shipping'] ?? [];

                $email = $billing['email'] ?? null;
                $phone = $billing['phone'] ?? ($shipping['phone'] ?? null);
                $phone = $phone ? ltrim($this->normalizePhone($phone), '+') : null;

                if (empty($email) && empty($phone)) {
                    continue;
                }

                $name = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));

                if (empty($name)) {
                    $name = $email ? explode('@', $email)[0] : 'Customer';
                }

                $existing = DB::table('commerce_customers')
                    ->where('user_id', $userId)
                    ->where('provider', 'woocommerce')
                    ->where(function ($q) use ($config, $customerId, $phone, $email) {
                        $q->where('store_ref', $config->id);

                        $q->where(function ($inner) use ($customerId, $phone, $email) {
                            if (!empty($customerId)) {
                                $inner->orWhere('external_customer_id', (string) $customerId);
                            }

                            if (!empty($phone)) {
                                $inner->orWhere('phone', $phone);
                            }

                            if (!empty($email)) {
                                $inner->orWhere('email', $email);
                            }
                        });
                    })
                    ->first();

                if ($existing) {
                    DB::table('commerce_customers')
                        ->where('id', $existing->id)
                        ->update([
                            'external_customer_id' => $customerId ?: $existing->external_customer_id,
                            'customer_name' => $name,
                            'phone' => $phone ?: $existing->phone,
                            'email' => $email ?: $existing->email,
                            'last_order_number' => '#' . ($order['number'] ?? ''),
                            'last_order_at' => !empty($order['date_created'])
                                ? date('Y-m-d H:i:s', strtotime($order['date_created']))
                                : now(),
                            'updated_at' => now(),
                        ]);

                    $updated++;
                } else {
                    DB::table('commerce_customers')->insert([
                        'user_id' => $userId,
                        'provider' => 'woocommerce',
                        'store_ref' => $config->id,
                        'external_customer_id' => $customerId,
                        'customer_name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'orders_count' => 1,
                        'total_spent' => $order['total'] ?? 0,
                        'last_order_number' => '#' . ($order['number'] ?? ''),
                        'last_order_at' => !empty($order['date_created'])
                            ? date('Y-m-d H:i:s', strtotime($order['date_created']))
                            : now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $imported++;
                }
            }

            return response()->json([
                'status' => true,
                'message' => "Sync done: {$imported} imported, {$updated} updated",
            ]);
        } catch (\Exception $e) {
            Log::error('Woo sync error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Manual WooCommerce product sync placeholder endpoint.
     */
    public function syncWooProducts(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'WooCommerce products sync endpoint is ready. Logic can be connected next.',
        ]);
    }

    /**
     * Manual WooCommerce full sync placeholder endpoint.
     */
    public function syncWooAll(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'WooCommerce full sync endpoint is ready. Logic can be connected next.',
        ]);
    }

    /**
     * Retry failed WooCommerce sync placeholder endpoint.
     */
    public function retryWooFailedSync(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'WooCommerce failed sync retry endpoint is ready. Logic can be connected next.',
        ]);
    }

    /**
     * Display WooCommerce sync history placeholder behavior.
     */
    public function wooSyncHistory()
    {
        return redirect()->route('user.ecommerce.sync.center');
    }

    /**
     * Refresh summary metrics placeholder endpoint.
     */
    public function refreshMetrics(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Metrics refresh endpoint is ready.',
        ]);
    }

    /**
     * Rebuild summary data placeholder endpoint.
     */
    public function rebuildSummary(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Summary rebuild endpoint is ready.',
        ]);
    }

    /**
     * Create Contact List from RFM Segment.
     */
    public function createRfmList($segment)
    {
        $user = getParentUser();

        $segment  = ucfirst(strtolower($segment));
        $segments = $this->getRfmSegments();
        $filtered = collect($segments)->where('segment', $segment);

        if ($filtered->isEmpty()) {
            return back()->with('error', 'No customers found for this segment');
        }

        $phones = $filtered->pluck('phone')->filter()->unique()->values();

        if ($phones->isEmpty()) {
            return back()->with('error', 'No valid phone numbers found');
        }

        $contacts = DB::table('contacts')
            ->where('user_id', $user->id)
            ->where(function ($q) use ($phones) {
                foreach ($phones as $phone) {
                    $q->orWhere('mobile', $phone)
                        ->orWhere(DB::raw("CONCAT(mobile_code, mobile)"), $phone);
                }
            })
            ->pluck('id')
            ->unique()
            ->toArray();

        if (empty($contacts)) {
            return back()->with('error', 'No matching contacts found in system');
        }

        $list = new \App\Models\ContactList();
        $list->user_id = $user->id;
        $list->name = 'RFM - ' . $segment . ' - ' . now()->format('Y-m-d H:i');
        $list->save();

        $list->contact()->sync($contacts);

        return back()->with('success', 'Contact list created successfully: ' . $list->name);
    }

    /**
     * Export contacts from the selected RFM segment as CSV.
     */
    public function exportRfmContacts($segment)
    {
        $user = getParentUser();

        $segment  = strtolower(trim($segment));
        $segments = collect($this->getRfmSegments());

        if ($segment !== 'all') {
            $segments = $segments->filter(function ($item) use ($segment) {
                return strtolower($item['segment']) === strtolower($segment);
            });
        }

        if ($segments->isEmpty()) {
            return back()->with('error', 'No contacts found for export');
        }

        $phones = $segments->pluck('phone')
            ->filter()
            ->map(function ($phone) {
                return ltrim((string) $phone, '+');
            })
            ->unique()
            ->values();

        if ($phones->isEmpty()) {
            return back()->with('error', 'No valid phone numbers found for export');
        }

        $contacts = DB::table('contacts')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($phones) {
                foreach ($phones as $phone) {
                    $query->orWhere('mobile', $phone)
                        ->orWhere(DB::raw("CONCAT(mobile_code, mobile)"), $phone);
                }
            })
            ->select('id', 'firstname', 'lastname', 'mobile_code', 'mobile')
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->values();

        if ($contacts->isEmpty()) {
            $contacts = $segments->map(function ($row) {
                return (object) [
                    'firstname' => $row['customer_name'] ?? '',
                    'lastname' => '',
                    'mobile_code' => '',
                    'mobile' => $row['phone'] ?? '',
                ];
            })->filter(function ($c) {
                return !empty($c->mobile);
            })->values();

            if ($contacts->isEmpty()) {
                return back()->with('error', 'No valid phone numbers found for export');
            }
        }

        $fileName = 'rfm_' . $segment . '_contacts_' . now()->format('Y_m_d_H_i') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function () use ($contacts) {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['firstname', 'lastname', 'mobile_code', 'mobile']);

            foreach ($contacts as $contact) {
                fputcsv($output, [
                    $contact->firstname ?? '',
                    $contact->lastname ?? '',
                    $contact->mobile_code ?? '',
                    $contact->mobile ?? '',
                ]);
            }

            fclose($output);
        }, $fileName, $headers);
    }
}