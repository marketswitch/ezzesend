<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyController extends Controller
{
    public function index()
    {
        $pageTitle = 'Shopify Stores';

        $stores = DB::table('shopify_stores')
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        return view('templates.basic.user.shopify.index', compact(
            'pageTitle',
            'stores'
        ));
    }

    public function connect(Request $request)
    {
        $store = $request->get('store_url') ?: $request->get('store');

        if (!$store) {
            $pageTitle = 'Shopify Connect';

            $whatsappAccounts = DB::table('whatsapp_accounts')
                ->where('user_id', auth()->id())
                ->select('id', 'phone_number', 'phone_number_status')
                ->get();

            $shopifyStore = DB::table('shopify_stores')
                ->where('user_id', auth()->id())
                ->first();

            return view('templates.basic.user.shopify.connect', compact(
                'pageTitle',
                'whatsappAccounts',
                'shopifyStore'
            ));
        }

        $request->validate([
            'store_url' => 'required|string|max:255',
            'whatsapp_account_id' => 'required|integer',
        ]);

        $store = trim($store);
        $store = preg_replace('#^https?://#', '', $store);
        $store = rtrim($store, '/');

        DB::table('shopify_stores')->updateOrInsert(
            ['user_id' => auth()->id()],
            [
                'store_url' => $store,
                'whatsapp_account_id' => $request->whatsapp_account_id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $clientId = env('SHOPIFY_CLIENT_ID');
        $redirectUri = env('SHOPIFY_REDIRECT_URI');

        if (!$clientId || !$redirectUri) {
            return back()->with('error', 'Shopify app credentials are missing in .env');
        }

        $installUrl = "https://{$store}/admin/oauth/authorize?" . http_build_query([
            'client_id'    => $clientId,
            'scope'        => 'read_orders,read_customers,read_products',
            'redirect_uri' => $redirectUri,
        ]);

        return redirect($installUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $store = $request->get('shop');

        if (!$code || !$store) {
            return redirect()->route('user.ecommerce.shopify.connect')
                ->with('error', 'Missing Shopify authorization data.');
        }

        $response = Http::post("https://{$store}/admin/oauth/access_token", [
            'client_id'     => env('SHOPIFY_CLIENT_ID'),
            'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
            'code'          => $code,
        ]);

        if (!$response->successful()) {
            return redirect()->route('user.ecommerce.shopify.connect')
                ->with('error', 'Failed to get Shopify access token.');
        }

        $data = $response->json();

        if (!isset($data['access_token'])) {
            return redirect()->route('user.ecommerce.shopify.connect')
                ->with('error', 'Shopify access token was not returned.');
        }

        DB::table('shopify_stores')->updateOrInsert(
            ['user_id' => auth()->id()],
            [
                'store_url'    => $store,
                'access_token' => $data['access_token'],
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        return redirect()->route('user.ecommerce.shopify.connect')
            ->with('success', 'Shopify connected successfully');
    }

    public function disconnect($id)
    {
        $store = DB::table('shopify_stores')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$store) {
            abort(404);
        }

        DB::table('shopify_stores')
            ->where('id', $store->id)
            ->update([
                'access_token' => null,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('user.ecommerce.shopify.index')
            ->with('success', 'Shopify store disconnected successfully.');
    }

    public function orderWebhook(Request $request)
{
    $order = $request->all();

    $storeUrl = $request->header('x-shopify-shop-domain')
        ?? $request->get('myshopify_domain')
        ?? $request->get('shop');

    $phone = $order['customer']['phone'] ?? ($order['phone'] ?? null);
    $name  = $order['customer']['first_name'] ?? 'Customer';
    $email = $order['customer']['email'] ?? null;

    $shopifyStore = DB::table('shopify_stores')
        ->where('store_url', $storeUrl)
        ->first();

    if (!$shopifyStore || !$phone) {
        return response()->json(['status' => 'error']);
    }

    if (empty($shopifyStore->access_token)) {
        return response()->json(['status' => 'disconnected']);
    }

    $wa = DB::table('whatsapp_accounts')
        ->where('id', $shopifyStore->whatsapp_account_id)
        ->first();

    if (!$wa || !$wa->access_token || !$wa->phone_number_id) {
        return response()->json(['status' => 'invalid_whatsapp']);
    }

    // Normalize phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);

    $orderNumber   = $order['name'] ?? 'Order';
    $customerName  = $name ?? 'Customer';
    $externalOrderId = (string) ($order['id'] ?? '');
    $orderTotal    = (float) ($order['total_price'] ?? 0);
    $currency      = $order['currency'] ?? null;
    $orderStatus   = $order['financial_status'] ?? ($order['fulfillment_status'] ?? 'created');
    $orderedAt     = !empty($order['created_at']) ? date('Y-m-d H:i:s', strtotime($order['created_at'])) : now();

    // Send WhatsApp template
    $response = Http::withToken($wa->access_token)
        ->post("https://graph.facebook.com/v18.0/{$wa->phone_number_id}/messages", [
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
                    ]
                ]]
            ]
        ]);

    $responseData = $response->json();

    $messageId = $responseData['messages'][0]['id'] ?? null;
    $errorMsg  = $responseData['error']['message'] ?? null;

    $cleanResponse = $messageId
        ? "Message ID: {$messageId}"
        : ($errorMsg ?? 'Unknown response');

    // Keep existing Shopify logs unchanged
    DB::table('shopify_message_logs')->insert([
        'store_id' => $shopifyStore->id,
        'customer_name' => $customerName,
        'phone' => $phone,
        'order_number' => $orderNumber,
        'status' => $response->successful() ? 'sent' : 'failed',
        'response' => $cleanResponse,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /*
    |--------------------------------------------------------------------------
    | Unified Commerce Layer
    |--------------------------------------------------------------------------
    | Save customer, order, and message log into the new shared tables
    | without affecting the old Shopify flow.
    */

    // 1) Create or update commerce customer
    $commerceCustomer = DB::table('commerce_customers')
        ->where('user_id', $shopifyStore->user_id)
        ->where('provider', 'shopify')
        ->where('store_ref', $shopifyStore->id)
        ->where('phone', $phone)
        ->first();

    if ($commerceCustomer) {
        DB::table('commerce_customers')
            ->where('id', $commerceCustomer->id)
            ->update([
                'customer_name'     => $customerName,
                'email'             => $email,
                'orders_count'      => (int) $commerceCustomer->orders_count + 1,
                'total_spent'       => (float) $commerceCustomer->total_spent + $orderTotal,
                'last_order_number' => $orderNumber,
                'last_order_at'     => $orderedAt,
                'updated_at'        => now(),
            ]);

        $commerceCustomerId = $commerceCustomer->id;
    } else {
        $commerceCustomerId = DB::table('commerce_customers')->insertGetId([
            'user_id'            => $shopifyStore->user_id,
            'provider'           => 'shopify',
            'store_ref'          => $shopifyStore->id,
            'external_customer_id' => isset($order['customer']['id']) ? (string) $order['customer']['id'] : null,
            'customer_name'      => $customerName,
            'phone'              => $phone,
            'email'              => $email,
            'orders_count'       => 1,
            'total_spent'        => $orderTotal,
            'last_order_number'  => $orderNumber,
            'last_order_at'      => $orderedAt,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    // 2) Save commerce order
    $commerceOrderId = DB::table('commerce_orders')->insertGetId([
        'user_id'             => $shopifyStore->user_id,
        'provider'            => 'shopify',
        'store_ref'           => $shopifyStore->id,
        'commerce_customer_id'=> $commerceCustomerId,
        'external_order_id'   => $externalOrderId,
        'order_number'        => $orderNumber,
        'order_total'         => $orderTotal,
        'currency'            => $currency,
        'order_status'        => $orderStatus,
        'raw_payload'         => json_encode($order),
        'ordered_at'          => $orderedAt,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    // 3) Save commerce message log
    DB::table('commerce_message_logs')->insert([
        'user_id'             => $shopifyStore->user_id,
        'provider'            => 'shopify',
        'store_ref'           => $shopifyStore->id,
        'commerce_customer_id'=> $commerceCustomerId,
        'commerce_order_id'   => $commerceOrderId,
        'customer_name'       => $customerName,
        'phone'               => $phone,
        'message_type'        => 'template',
        'message_template'    => 'order_confirmation',
        'status'              => $response->successful() ? 'sent' : 'failed',
        'response'            => $cleanResponse,
        'sent_at'             => now(),
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    Log::info('WhatsApp Response:', $responseData);

    return response()->json([
        'status' => $response->successful() ? 'sent' : 'failed',
    ]);
}

    public function settings($id)
    {
        $pageTitle = 'Shopify Settings';

        $store = DB::table('shopify_stores')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$store) {
            abort(404);
        }

        $whatsappAccounts = DB::table('whatsapp_accounts')
            ->where('user_id', auth()->id())
            ->select('id', 'phone_number', 'phone_number_status')
            ->get();

        $linkedWhatsapp = DB::table('whatsapp_accounts')
            ->where('id', $store->whatsapp_account_id)
            ->first();

        return view('templates.basic.user.shopify.settings', compact(
            'pageTitle',
            'store',
            'whatsappAccounts',
            'linkedWhatsapp'
        ));
    }

    public function webhooks($id)
    {
        $pageTitle = 'Shopify Webhooks';

        $store = DB::table('shopify_stores')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$store) {
            abort(404);
        }

        return view('templates.basic.user.shopify.webhooks', compact(
            'pageTitle',
            'store'
        ));
    }

    public function logs($id)
    {
        $pageTitle = 'Shopify Logs';

        $store = DB::table('shopify_stores')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$store) {
            abort(404);
        }

        $logs = DB::table('shopify_message_logs')
            ->where('store_id', $store->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('templates.basic.user.shopify.logs', compact(
            'pageTitle',
            'store',
            'logs'
        ));
    }
}