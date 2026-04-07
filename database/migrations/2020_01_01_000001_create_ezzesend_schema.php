<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // General Settings
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 200)->nullable();
            $table->string('cur_text', 50)->nullable();
            $table->string('cur_sym', 10)->nullable();
            $table->tinyInteger('email_verification')->default(0);
            $table->tinyInteger('sms_verification')->default(0);
            $table->tinyInteger('registration')->default(1);
            $table->tinyInteger('kyc_verification')->default(0);
            $table->string('active_template')->default('basic');
            $table->text('mail_config')->nullable();
            $table->text('sms_config')->nullable();
            $table->text('pusher_config')->nullable();
            $table->text('firebase_config')->nullable();
            $table->text('global_shortcodes')->nullable();
            $table->text('socialite_credentials')->nullable();
            $table->string('base_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('logo')->nullable();
            $table->string('dark_logo')->nullable();
            $table->string('favicon')->nullable();
            $table->text('seo_content')->nullable();
            $table->text('system_info')->nullable();
            $table->string('time_zone')->default('UTC');
            $table->tinyInteger('force_ssl')->default(0);
            $table->tinyInteger('secure_password')->default(0);
            $table->tinyInteger('agree')->default(0);
            $table->string('PUSHER_APP_ID')->nullable();
            $table->string('PUSHER_APP_KEY')->nullable();
            $table->string('PUSHER_APP_SECRET')->nullable();
            $table->string('PUSHER_APP_CLUSTER')->nullable();
            $table->string('PUSHER_APP_HOST')->nullable();
            $table->string('PUSHER_APP_PORT')->nullable();
            $table->timestamps();
        });

        // Admins
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });

        // Admin notifications
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->tinyInteger('is_read')->default(0);
            $table->timestamps();
        });

        // Admin password resets
        Schema::create('admin_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->string('status')->default('0');
            $table->timestamps();
        });

        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('mobile')->nullable();
            $table->string('mobile_code', 20)->nullable();
            $table->string('country_code', 10)->nullable();
            $table->string('country_name', 100)->nullable();
            $table->string('image')->nullable();
            $table->decimal('balance', 28, 8)->default(0);
            $table->string('password');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->text('kyc_data')->nullable();
            $table->tinyInteger('kyc_rejection_reason')->nullable();
            $table->tinyInteger('kv')->default(0);
            $table->tinyInteger('ev')->default(0);
            $table->tinyInteger('sv')->default(0);
            $table->tinyInteger('tv')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('ver_code')->nullable();
            $table->timestamp('ver_code_send_at')->nullable();
            $table->tinyInteger('ts')->default(0);
            $table->string('tsc')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->timestamp('plan_expire_date')->nullable();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->tinyInteger('is_agent')->default(0);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('ref_by')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        // Languages
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20);
            $table->tinyInteger('is_default')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // Frontend
        Schema::create('frontends', function (Blueprint $table) {
            $table->id();
            $table->string('tempname');
            $table->string('slug');
            $table->text('data_values')->nullable();
            $table->timestamps();
        });

        // Pages
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('tempname');
            $table->string('slug');
            $table->string('name');
            $table->text('content')->nullable();
            $table->tinyInteger('is_default')->default(0);
            $table->timestamps();
        });

        // Pricing Plans
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 28, 8)->default(0);
            $table->integer('validity')->default(30);
            $table->string('validity_type')->default('days');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_popular')->default(0);
            $table->tinyInteger('campaign_available')->default(1);
            $table->tinyInteger('ecommerce_available')->default(0);
            $table->text('features')->nullable();
            $table->timestamps();
        });

        // Gateways
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('form_id')->default(0);
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('min_amount', 28, 8)->default(0);
            $table->decimal('max_amount', 28, 8)->default(0);
            $table->decimal('percent_charge', 5, 2)->default(0);
            $table->decimal('fixed_charge', 28, 8)->default(0);
            $table->tinyInteger('type')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->text('gateway_parameters')->nullable();
            $table->text('supported_currencies')->nullable();
            $table->text('extra')->nullable();
            $table->timestamps();
        });

        Schema::create('gateway_currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gateway_id');
            $table->string('name');
            $table->string('currency', 20);
            $table->string('symbol', 40)->nullable();
            $table->text('gateway_parameter')->nullable();
            $table->decimal('min_amount', 28, 8)->default(0);
            $table->decimal('max_amount', 28, 8)->default(0);
            $table->decimal('percent_charge', 5, 2)->default(0);
            $table->decimal('fixed_charge', 28, 8)->default(0);
            $table->decimal('rate', 28, 8)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // Deposits/Transactions
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('method_id');
            $table->string('method_currency', 40)->nullable();
            $table->decimal('amount', 28, 8)->default(0);
            $table->decimal('method_amount', 28, 8)->default(0);
            $table->decimal('charge', 28, 8)->default(0);
            $table->decimal('rate', 28, 8)->default(0);
            $table->decimal('final_amo', 28, 8)->default(0);
            $table->text('detail')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('btc_amo')->nullable();
            $table->string('btc_wallet')->nullable();
            $table->string('trx');
            $table->text('payment_try')->nullable();
            $table->string('from_api')->default(0);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 28, 8)->default(0);
            $table->decimal('charge', 28, 8)->default(0);
            $table->string('trx_type', 40);
            $table->string('trx', 40);
            $table->decimal('wallet_type', 28, 8)->nullable();
            $table->text('details')->nullable();
            $table->tinyInteger('remark')->nullable();
            $table->timestamps();
        });

        // Withdraw
        Schema::create('withdraw_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('user_data')->nullable();
            $table->decimal('min_limit', 28, 8)->default(0);
            $table->decimal('max_limit', 28, 8)->default(0);
            $table->decimal('fixed_charge', 28, 8)->default(0);
            $table->decimal('percent_charge', 5, 2)->default(0);
            $table->decimal('rate', 28, 8)->default(0);
            $table->string('currency', 40)->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('method_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 28, 8)->default(0);
            $table->decimal('currency_amount', 28, 8)->default(0);
            $table->decimal('charge', 28, 8)->default(0);
            $table->decimal('final_amount', 28, 8)->default(0);
            $table->decimal('after_charge', 28, 8)->default(0);
            $table->decimal('rate', 28, 8)->default(0);
            $table->string('currency', 40)->nullable();
            $table->text('user_data')->nullable();
            $table->string('trx')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->text('admin_feedback')->nullable();
            $table->timestamps();
        });

        // Notification Templates
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('act');
            $table->string('name');
            $table->text('subject')->nullable();
            $table->text('body')->nullable();
            $table->text('email_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->text('push_title')->nullable();
            $table->text('push_body')->nullable();
            $table->text('shortcodes')->nullable();
            $table->tinyInteger('email_status')->default(1);
            $table->tinyInteger('sms_status')->default(1);
            $table->tinyInteger('push_status')->default(1);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('sender', 40)->nullable();
            $table->string('sent_from')->nullable();
            $table->string('sent_to')->nullable();
            $table->text('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('notification_type', 40)->nullable();
            $table->timestamps();
        });

        // Support Tickets
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('ticket')->unique();
            $table->string('subject');
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('priority')->default(1);
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_ticket_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('support_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_message_id');
            $table->string('attachment');
            $table->timestamps();
        });

        // Extensions
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();
            $table->string('act')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->text('script')->nullable();
            $table->text('shortcode')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // WhatsApp Accounts
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('phone_number', 30)->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('business_account_id')->nullable();
            $table->string('waba_id')->nullable();
            $table->string('access_token', 500)->nullable();
            $table->string('business_name')->nullable();
            $table->string('phone_number_status')->nullable();
            $table->string('code_verification_status')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_default')->default(0);
            $table->timestamps();
        });

        // Contacts
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('mobile_code', 20)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('details')->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('contact_list_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_list_id');
            $table->unsignedBigInteger('contact_id');
            $table->timestamps();
        });

        Schema::create('contact_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('contact_tag_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_tag_id');
            $table->unsignedBigInteger('contact_id');
            $table->timestamps();
        });

        Schema::create('contact_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->text('note');
            $table->timestamps();
        });

        // Conversations & Messages
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('whatsapp_account_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('flow_node_id')->nullable();
            $table->unsignedBigInteger('cta_url_id')->nullable();
            $table->unsignedBigInteger('interactive_list_id')->nullable();
            $table->string('message_id')->nullable();
            $table->tinyInteger('type')->default(1);
            $table->tinyInteger('status')->default(0);
            $table->string('message_type')->nullable();
            $table->text('message')->nullable();
            $table->string('file')->nullable();
            $table->text('location')->nullable();
            $table->text('list_reply')->nullable();
            $table->text('product_data')->nullable();
            $table->tinyInteger('failed_reason')->nullable();
            $table->timestamps();
        });

        // Templates
        Schema::create('template_languages', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->string('code', 20);
            $table->timestamps();
        });

        Schema::create('template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('whatsapp_account_id')->nullable();
            $table->string('template_id')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('language')->nullable();
            $table->string('status')->default('PENDING');
            $table->string('header_type')->nullable();
            $table->text('header_value')->nullable();
            $table->text('body')->nullable();
            $table->text('footer')->nullable();
            $table->text('buttons')->nullable();
            $table->tinyInteger('is_carousel')->default(0);
            $table->timestamps();
        });

        Schema::create('template_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('header_type')->nullable();
            $table->text('header_value')->nullable();
            $table->text('body')->nullable();
            $table->text('buttons')->nullable();
            $table->string('media_path')->nullable();
            $table->timestamps();
        });

        // Campaigns
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('whatsapp_account_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('title');
            $table->text('template_header_params')->nullable();
            $table->text('template_body_params')->nullable();
            $table->timestamp('send_at')->nullable();
            $table->integer('total_message')->default(0);
            $table->integer('total_send')->default(0);
            $table->integer('total_success')->default(0);
            $table->integer('total_failed')->default(0);
            $table->tinyInteger('status')->default(3);
            $table->timestamps();
        });

        Schema::create('campaign_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('contact_id');
            $table->timestamps();
        });

        // Flow Builder
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('whatsapp_account_id')->nullable();
            $table->string('name');
            $table->tinyInteger('trigger_type')->default(1);
            $table->string('keyword')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('node_id')->unique();
            $table->unsignedBigInteger('flow_id');
            $table->string('type')->nullable();
            $table->text('message')->nullable();
            $table->text('buttons_json')->nullable();
            $table->text('location')->nullable();
            $table->string('target_node_id')->nullable();
            $table->timestamps();
        });

        Schema::create('flow_edges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id');
            $table->string('source')->nullable();
            $table->string('target')->nullable();
            $table->string('source_handle')->nullable();
            $table->timestamps();
        });

        Schema::create('flow_node_media', function (Blueprint $table) {
            $table->id();
            $table->string('flow_node_id');
            $table->string('file')->nullable();
            $table->string('type')->nullable();
            $table->string('media_id')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_flow_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('flow_id');
            $table->string('current_node_id')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // Chatbot
        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('whatsapp_business_account_id')->nullable();
            $table->string('name');
            $table->string('keyword')->nullable();
            $table->tinyInteger('match_type')->default(0);
            $table->text('reply')->nullable();
            $table->string('reply_type')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Welcome Messages
        Schema::create('welcome_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('whatsapp_account_id')->nullable();
            $table->text('message')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // AI Assistant
        Schema::create('ai_assistants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->text('config')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_user_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('whatsapp_account_id')->nullable();
            $table->text('system_prompt')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // Cron Jobs
        Schema::create('cron_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('interval');
            $table->timestamps();
        });

        Schema::create('cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cron_schedule_id');
            $table->string('name');
            $table->text('action')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cron_job_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cron_job_id');
            $table->text('message')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Plan Purchases
        Schema::create('plan_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->decimal('amount', 28, 8)->default(0);
            $table->string('trx')->nullable();
            $table->timestamp('expire_date')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Coupons
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('discount', 5, 2)->default(0);
            $table->string('discount_type')->default('percent');
            $table->timestamp('expire_date')->nullable();
            $table->integer('used_times')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Short Links
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title')->nullable();
            $table->string('short_code')->unique();
            $table->string('long_url', 1000);
            $table->integer('click')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Floaters
        Schema::create('floaters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('welcome_text')->nullable();
            $table->string('position')->default('right');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // CTA URLs
        Schema::create('cta_urls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('display_text')->nullable();
            $table->string('url', 1000)->nullable();
            $table->text('body')->nullable();
            $table->text('footer')->nullable();
            $table->timestamps();
        });

        // Interactive Lists
        Schema::create('interactive_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->text('footer')->nullable();
            $table->string('button_title')->nullable();
            $table->text('sections')->nullable();
            $table->timestamps();
        });

        // User logins
        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_ip', 50)->nullable();
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('location')->nullable();
            $table->string('country_code', 10)->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamps();
        });

        // Password resets
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->string('status')->default('0');
            $table->timestamps();
        });

        // Subscribers
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        // Agent permissions
        Schema::create('agent_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('permissions')->nullable();
            $table->timestamps();
        });

        // External API IP whitelist
        Schema::create('external_api_ip_white_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ip', 50);
            $table->timestamps();
        });

        // User API credentials
        Schema::create('user_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('api_key', 100)->unique();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Ecommerce
        Schema::create('ecommerce_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('provider')->default(1);
            $table->text('config')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 500);
            $table->timestamps();
        });

        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('act')->default(0);
            $table->text('form_data')->nullable();
            $table->timestamps();
        });

        // Shopify stores
        Schema::create('shopify_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('store_url')->nullable();
            $table->unsignedBigInteger('whatsapp_account_id')->nullable();
            $table->string('access_token', 500)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // Commerce customers/orders (for ecommerce sync)
        Schema::create('commerce_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('provider')->default(1);
            $table->string('store_ref')->nullable();
            $table->string('external_customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('last_order_number')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commerce_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('provider')->default(1);
            $table->string('store_ref')->nullable();
            $table->string('commerce_customer_id')->nullable();
            $table->string('external_order_id')->nullable();
            $table->string('order_number')->nullable();
            $table->decimal('order_total', 28, 8)->default(0);
            $table->string('currency', 20)->nullable();
            $table->string('order_status')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });

        // Sanctum personal access tokens
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Now seed essential data
        $this->seedEssentialData();
    }

    private function seedEssentialData(): void
    {
        // General Settings
        DB::table('general_settings')->insert([
            'site_name'           => 'EzzeSend',
            'cur_text'            => 'USD',
            'cur_sym'             => '$',
            'active_template'     => 'basic',
            'email_verification'  => 0,
            'sms_verification'    => 0,
            'registration'        => 1,
            'base_color'          => '#7367f0',
            'secondary_color'     => '#ce9ffc',
            'time_zone'           => 'Asia/Kuwait',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Admin account (password: admin123)
        DB::table('admins')->insert([
            'name'       => 'Super Admin',
            'email'      => 'admin@ezzesend.com',
            'username'   => 'admin',
            'password'   => bcrypt('admin123'),
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Default language
        DB::table('languages')->insert([
            ['name' => 'English', 'code' => 'en', 'is_default' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Arabic', 'code' => 'ar', 'is_default' => 0, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Cron schedules
        DB::table('cron_schedules')->insert([
            ['name' => 'Every Minute',  'interval' => '* * * * *',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Every 5 Minutes', 'interval' => '*/5 * * * *', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hourly',        'interval' => '0 * * * *',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Daily',         'interval' => '0 0 * * *',    'created_at' => now(), 'updated_at' => now()],
        ]);

        // Notification templates
        $templates = [
            ['act' => 'ADMIN_SUPPORT_REPLY',  'name' => 'Support Reply from Admin'],
            ['act' => 'TICKET_CREATE',         'name' => 'Support Ticket Create'],
            ['act' => 'TICKET_REPLY',          'name' => 'Support Ticket Reply'],
            ['act' => 'PASS_RESET_CODE',       'name' => 'Password Reset'],
            ['act' => 'EMAIL_VERIFICATION',    'name' => 'Email Verification'],
            ['act' => 'SMS_VERIFICATION',      'name' => 'SMS Verification'],
            ['act' => 'SUBSCRIPTION_EXPIRE',   'name' => 'Subscription Expire'],
        ];
        foreach ($templates as $t) {
            DB::table('notification_templates')->insert(array_merge($t, [
                'email_status' => 1, 'sms_status' => 1, 'push_status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        // Pricing plans
        DB::table('pricing_plans')->insert([
            [
                'name'               => 'Starter',
                'price'              => 0,
                'validity'           => 365,
                'validity_type'      => 'days',
                'status'             => 1,
                'is_popular'         => 0,
                'campaign_available' => 1,
                'ecommerce_available'=> 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Professional',
                'price'              => 29,
                'validity'           => 30,
                'validity_type'      => 'days',
                'status'             => 1,
                'is_popular'         => 1,
                'campaign_available' => 1,
                'ecommerce_available'=> 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);

        // Template categories
        DB::table('template_categories')->insert([
            ['name' => 'MARKETING',     'slug' => 'marketing',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'UTILITY',       'slug' => 'utility',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AUTHENTICATION','slug' => 'authentication', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        $tables = [
            'commerce_orders','commerce_customers','shopify_stores',
            'personal_access_tokens','device_tokens','ecommerce_configurations',
            'user_api_credentials','external_api_ip_white_lists','agent_permissions',
            'subscribers','password_resets','user_logins','interactive_lists',
            'cta_urls','floaters','short_links','coupons','plan_purchases',
            'cron_job_logs','cron_jobs','cron_schedules','ai_user_settings',
            'ai_assistants','welcome_messages','chatbots','contact_flow_states',
            'flow_node_media','flow_edges','flow_nodes','flows','campaign_contacts',
            'campaigns','template_cards','templates','template_categories',
            'template_languages','messages','conversations','contact_notes',
            'contact_tag_contacts','contact_tags','contact_list_contacts',
            'contact_lists','contacts','whatsapp_accounts','extensions',
            'notification_logs','notification_templates','support_attachments',
            'support_messages','support_tickets','pages','frontends','languages',
            'withdrawals','withdraw_methods','transactions','deposits',
            'gateway_currencies','gateways','pricing_plans','forms',
            'admin_password_resets','admin_notifications','admins',
            'general_settings','users',
        ];

        foreach (array_reverse($tables) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
