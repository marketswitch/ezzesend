<div class="sidebar-menu flex-between">
    <div class="sidebar-menu__inner">

        {{-- =========================================================
             MOBILE CLOSE BUTTON
        ========================================================== --}}
        <span class="sidebar-menu__close d-lg-none d-block">
            <i class="fas fa-times"></i>
        </span>

        {{-- =========================================================
             SIDEBAR LOGO
             - Main logo for expanded sidebar
             - Favicon reserved for collapsed mode if needed later
        ========================================================== --}}
        <div class="sidebar-logo">
            <a href="{{ route('home') }}" class="sidebar-logo__link">
                <img src="{{ siteLogo('dark') }}" alt="logo">
            </a>
            <a href="javascript:void(0)" class="sidebar-logo__favicon d-none">
                <img src="{{ siteFavicon('dark') }}" alt="img">
            </a>
        </div>

        {{-- =========================================================
             PREPARE SIDEBAR STATE
             - Resolve Shopify quick links
             - Resolve active/open states once
             - Keep all route-state logic centralized here
        ========================================================== --}}
        @php
            use Illuminate\Support\Facades\DB;

            /*
            |--------------------------------------------------------------------------
            | Resolve latest Shopify store for quick-access links
            |--------------------------------------------------------------------------
            */
            $shopifySidebarStore = DB::table('shopify_stores')
                ->where('user_id', auth()->id())
                ->orderByDesc('id')
                ->first();

            $shopifySettingsUrl = $shopifySidebarStore
                ? route('user.ecommerce.shopify.settings', $shopifySidebarStore->id)
                : route('user.ecommerce.shopify.connect');

            $shopifyWebhooksUrl = $shopifySidebarStore
                ? route('user.ecommerce.shopify.webhooks', $shopifySidebarStore->id)
                : route('user.ecommerce.shopify.connect');

            $shopifyLogsUrl = $shopifySidebarStore
                ? route('user.ecommerce.shopify.logs', $shopifySidebarStore->id)
                : route('user.ecommerce.shopify.connect');

            /*
            |--------------------------------------------------------------------------
            | Parent menu active states
            |--------------------------------------------------------------------------
            */
            $contactsActive = request()->routeIs('user.contact.*', 'user.contacttag.*', 'user.contactlist.*');
            $templatesActive = request()->routeIs('user.template.*');
            $campaignsActive = request()->routeIs('user.campaign.*');
            $automationActive = request()->routeIs('user.automation.*', 'user.flow.builder.*');
            $shortlinkActive = request()->routeIs('user.shortlink.*');
            $floaterActive = request()->routeIs('user.floater.*');
            $ctaUrlActive = request()->routeIs('user.cta-url.*');
            $interactiveListActive = request()->routeIs('user.interactive-list.*');

            /*
            |--------------------------------------------------------------------------
            | Ecommerce menu states
            |--------------------------------------------------------------------------
            */
            $ecommerceActive = request()->routeIs('user.ecommerce.*');

            $wooCommerceActive = request()->routeIs(
                'user.ecommerce.woocommerce.*',
                'user.ecommerce.logs',
                'user.ecommerce.customers',
                'user.ecommerce.catalog',
                'user.ecommerce.orders',
                'user.ecommerce.sync.center'
            );

            $shopifyActive = request()->routeIs(
                'user.ecommerce.shopify.*',
                'user.ecommerce.customers',
                'user.ecommerce.orders',
                'user.ecommerce.catalog',
                'user.ecommerce.sync.center'
            );

            /*
            |--------------------------------------------------------------------------
            | Marketing intelligence states
            |--------------------------------------------------------------------------
            */
            $rfmActive = request()->routeIs(
                'user.ecommerce.rfm.dashboard',
                'user.ecommerce.rfm.vip.customers',
                'user.ecommerce.rfm.loyal.customers',
                'user.ecommerce.rfm.new.customers',
                'user.ecommerce.rfm.at.risk',
                'user.ecommerce.rfm.lost.customers'
            );

            $segmentsActive = request()->routeIs(
                'user.ecommerce.segments',
                'user.ecommerce.segments.customer.filters',
                'user.ecommerce.segments.buyers',
                'user.ecommerce.segments.repeat.customers',
                'user.ecommerce.segments.high.value.customers',
                'user.ecommerce.segments.abandoned.cart',
                'user.ecommerce.segments.message.history'
            );

            $analyticsActive = request()->routeIs('user.ecommerce.analytics');
            $healthActive = request()->routeIs('user.ecommerce.health');

            /*
            |--------------------------------------------------------------------------
            | Dropdown helper classes
            |--------------------------------------------------------------------------
            */
            $openClass = 'show';
            $activeParentClass = 'active';
        @endphp

        <ul class="sidebar-menu-list">

            {{-- =========================================================
                 DASHBOARD
            ========================================================== --}}
            <x-permission_check permission="view dashboard">
                <li class="sidebar-menu-list__item {{ menuActive('user.home') }}">
                    <a href="{{ route('user.home') }}"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Dashboard')">
                        <span class="icon">
                            <i class="fa-solid fa-border-all"></i>
                        </span>
                        <span class="text">@lang('My Dashboard')</span>
                    </a>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 MARKETING TOOLS SECTION
            ========================================================== --}}
            <x-permission_check :permission="[
                'view dashboard',
                'view contact',
                'view contact list',
                'view contact tag',
                'view template',
                'view campaign',
                'view welcome message',
                'view shortlink',
                'view floater',
                'add cta url',
                'view cta url',
                'add interactive list',
                'view interactive list',
                'update ecommerce configuration',
                'show ecommerce products',
            ]">
                <li class="sidebar-menu-list__title">
                    <span class="text">@lang('MARKETING TOOLS')</span>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 CONTACTS
                 - Parent menu for contacts, tags, and contact lists
            ========================================================== --}}
            <x-permission_check :permission="['view contact', 'view contact list', 'view contact tag']">
                <li class="sidebar-menu-list__item has-dropdown {{ $contactsActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.contact.list') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Contacts')">
                        <span class="icon">
                            <i class="fa-regular fa-id-card"></i>
                        </span>
                        <span class="text">@lang('Manage Contacts')</span>
                    </a>

                    <div class="sidebar-submenu {{ $contactsActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <x-permission_check permission="view contact">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.contact.*') }}">
                                    <a href="{{ route('user.contact.list') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Manage Contacts')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view contact tag">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.contacttag.*') }}">
                                    <a href="{{ route('user.contacttag.list') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Manage Contact Tag')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view contact list">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.contactlist.*') }}">
                                    <a href="{{ route('user.contactlist.list') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Manage Contact List')</span>
                                    </a>
                                </li>
                            </x-permission_check>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 TEMPLATES
            ========================================================== --}}
            <x-permission_check :permission="['view template', 'add template', 'delete template']">
                <li class="sidebar-menu-list__item has-dropdown {{ $templatesActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.template.index') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Templates')">
                        <span class="icon">
                            <i class="fa-solid fa-envelope-square"></i>
                        </span>
                        <span class="text">@lang('Manage Templates')</span>
                    </a>

                    <div class="sidebar-submenu {{ $templatesActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <li class="sidebar-submenu-list__item {{ menuActive('user.template.create') }}">
                                <a href="{{ route('user.template.create') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('New Template')</span>
                                </a>
                            </li>

                            <li class="sidebar-submenu-list__item {{ menuActive('user.template.create.carousel') }}">
                                <a href="{{ route('user.template.create.carousel') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('Carousel Template')</span>
                                </a>
                            </li>

                            <li class="sidebar-submenu-list__item {{ menuActive('user.template.index') }}">
                                <a href="{{ route('user.template.index') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('All Template')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 CAMPAIGNS
            ========================================================== --}}
            <x-permission_check :permission="['view campaign', 'add campaign', 'delete campaign']">
                <li class="sidebar-menu-list__item has-dropdown {{ $campaignsActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.campaign.index') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Campaigns')">
                        <span class="icon">
                            <i class="fa-solid fa-volume-high"></i>
                        </span>
                        <span class="text">@lang('Manage Campaigns')</span>
                    </a>

                    <div class="sidebar-submenu {{ $campaignsActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <x-permission_check permission="add campaign">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.campaign.create') }}">
                                    <a href="{{ route('user.campaign.create') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('New Campaign')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view campaign">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.campaign.index') }}">
                                    <a href="{{ route('user.campaign.index') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('All Campaign')</span>
                                    </a>
                                </li>
                            </x-permission_check>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 AUTOMATION
            ========================================================== --}}
            <x-permission_check :permission="['view welcome message', 'view flow builder']">
                <li class="sidebar-menu-list__item has-dropdown {{ $automationActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.flow.builder.index') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Automation')">
                        <span class="icon">
                            <i class="fa-solid fa-envelope-square"></i>
                        </span>
                        <span class="text">@lang('Manage Automation')</span>
                    </a>

                    <div class="sidebar-submenu {{ $automationActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <x-permission_check permission="view welcome message">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.automation.welcome.message') }}">
                                    <a href="{{ route('user.automation.welcome.message') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Welcome Message')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view flow builder">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.flow.builder.index') }}">
                                    <a href="{{ route('user.flow.builder.index') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Flow Builder')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <li class="sidebar-submenu-list__item {{ menuActive('user.automation.ai.assistant') }}">
                                <a href="{{ route('user.automation.ai.assistant') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('AI Assistant')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 SHORTLINK
            ========================================================== --}}
            <x-permission_check :permission="['add shortlink', 'view shortlink']">
                <li class="sidebar-menu-list__item has-dropdown {{ $shortlinkActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.shortlink.index') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('ShortLink')">
                        <span class="icon">
                            <i class="fa-solid fa-link"></i>
                        </span>
                        <span class="text">@lang('Manage ShortLink')</span>
                    </a>

                    <div class="sidebar-submenu {{ $shortlinkActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <x-permission_check permission="add shortlink">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.shortlink.create') }}">
                                    <a href="{{ route('user.shortlink.create') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Create ShortLink')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view shortlink">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.shortlink.index') }}">
                                    <a href="{{ route('user.shortlink.index') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Manage ShortLink')</span>
                                    </a>
                                </li>
                            </x-permission_check>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 FLOATERS
            ========================================================== --}}
            <x-permission_check :permission="['add floater', 'view floater']">
                <li class="sidebar-menu-list__item has-dropdown {{ $floaterActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.floater.index') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Floaters')">
                        <span class="icon">
                            <i class="fa-brands fa-whatsapp"></i>
                        </span>
                        <span class="text">@lang('Manage Floaters')</span>
                    </a>

                    <div class="sidebar-submenu {{ $floaterActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <x-permission_check permission="add floater">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.floater.create') }}">
                                    <a href="{{ route('user.floater.create') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Create Floater')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view floater">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.floater.index') }}">
                                    <a href="{{ route('user.floater.index') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Manage Floater')</span>
                                    </a>
                                </li>
                            </x-permission_check>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 CTA URL
            ========================================================== --}}
            <x-permission_check :permission="['add cta url', 'view cta url']">
                <li class="sidebar-menu-list__item has-dropdown {{ $ctaUrlActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.cta-url.index') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('CTA URL')">
                        <span class="icon">
                            <i class="fa-solid fa-paperclip"></i>
                        </span>
                        <span class="text">@lang('Manage CTA URL')</span>
                    </a>

                    <div class="sidebar-submenu {{ $ctaUrlActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <x-permission_check permission="add cta url">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.cta-url.create') }}">
                                    <a href="{{ route('user.cta-url.create') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Create URL')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view cta url">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.cta-url.index') }}">
                                    <a href="{{ route('user.cta-url.index') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('CTA URL List')</span>
                                    </a>
                                </li>
                            </x-permission_check>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 INTERACTIVE LIST
            ========================================================== --}}
            <x-permission_check :permission="['add interactive list', 'view interactive list']">
                <li class="sidebar-menu-list__item has-dropdown {{ $interactiveListActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.interactive-list.index') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Interactive List')">
                        <span class="icon">
                            <i class="fa-solid fa-list"></i>
                        </span>
                        <span class="text">@lang('Manage Interactive List')</span>
                    </a>

                    <div class="sidebar-submenu {{ $interactiveListActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <x-permission_check permission="add interactive list">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.interactive-list.create') }}">
                                    <a href="{{ route('user.interactive-list.create') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Create List')</span>
                                    </a>
                                </li>
                            </x-permission_check>

                            <x-permission_check permission="view interactive list">
                                <li class="sidebar-submenu-list__item {{ menuActive('user.interactive-list.index') }}">
                                    <a href="{{ route('user.interactive-list.index') }}" class="sidebar-submenu-list__link">
                                        <span class="text">@lang('Interactive List')</span>
                                    </a>
                                </li>
                            </x-permission_check>
                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 E-COMMERCE
                 - Unified top-level commerce area
                 - Shared pages can be linked under both providers
            ========================================================== --}}
            <x-permission_check :permission="['update ecommerce configuration', 'show ecommerce products']">
                <li class="sidebar-menu-list__item has-dropdown {{ $ecommerceActive ? $activeParentClass : '' }}"
                    data-link="{{ route('user.ecommerce.woocommerce.products') }}">
                    <a href="javascript:void(0)"
                       class="sidebar-menu-list__link"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('E-Commerce')">
                        <span class="icon">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </span>
                        <span class="text">@lang('E-Commerce')</span>
                    </a>

                    <div class="sidebar-submenu {{ $ecommerceActive ? $openClass : '' }}">
                        <ul class="sidebar-submenu-list">
                            <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.sync.center') }}">
                                <a href="{{ route('user.ecommerce.sync.center') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('Sync Center')</span>
                                </a>
                            </li>

                            <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.customers') }}">
                                <a href="{{ route('user.ecommerce.customers') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('Customers')</span>
                                </a>
                            </li>

                            <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.orders') }}">
                                <a href="{{ route('user.ecommerce.orders') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('Orders')</span>
                                </a>
                            </li>

                            <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.catalog') }}">
                                <a href="{{ route('user.ecommerce.catalog') }}" class="sidebar-submenu-list__link">
                                    <span class="text">@lang('Catalog')</span>
                                </a>
                            </li>


                            {{-- WooCommerce provider menu --}}
                            <li class="sidebar-menu-list__item has-dropdown {{ $wooCommerceActive ? $activeParentClass : '' }}">
                                <a href="javascript:void(0)"
                                   class="sidebar-menu-list__link"
                                   data-bs-toggle-custom="tooltip"
                                   data-bs-placement="right"
                                   data-bs-title="@lang('Woo-Commerce')">
                                    <span class="text">@lang('Woo-Commerce')</span>
                                </a>

                                <div class="sidebar-submenu {{ $wooCommerceActive ? $openClass : '' }}">
                                    <ul class="sidebar-submenu-list">
                                        <x-permission_check permission="show ecommerce products">
                                            <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.woocommerce.products') }}">
                                                <a href="{{ route('user.ecommerce.woocommerce.products') }}" class="sidebar-submenu-list__link">
                                                    <span class="text">@lang('Products')</span>
                                                </a>
                                            </li>
                                        </x-permission_check>

                                        <x-permission_check permission="update ecommerce configuration">
                                            <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.woocommerce.config') }}">
                                                <a href="{{ route('user.ecommerce.woocommerce.config') }}" class="sidebar-submenu-list__link">
                                                    <span class="text">@lang('Config')</span>
                                                </a>
                                            </li>
                                        </x-permission_check>

                                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.logs') }}">
                                            <a href="{{ route('user.ecommerce.logs') }}" class="sidebar-submenu-list__link">
                                                <span class="text">@lang('Logs')</span>
                                            </a>
                                        </li>




                                    </ul>
                                </div>
                            </li>

                            {{-- Shopify provider menu --}}
                            <li class="sidebar-menu-list__item has-dropdown {{ $shopifyActive ? $activeParentClass : '' }}">
                                <a href="javascript:void(0)"
                                   class="sidebar-menu-list__link"
                                   data-bs-toggle-custom="tooltip"
                                   data-bs-placement="right"
                                   data-bs-title="@lang('Shopify')">
                                    <span class="text">@lang('Shopify')</span>
                                </a>

                                <div class="sidebar-submenu {{ $shopifyActive ? $openClass : '' }}">
                                    <ul class="sidebar-submenu-list">
                                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.shopify.index') }}">
                                            <a href="{{ route('user.ecommerce.shopify.index') }}" class="sidebar-submenu-list__link">
                                                <span class="text">@lang('Stores')</span>
                                            </a>
                                        </li>

                                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.shopify.connect') }}">
                                            <a href="{{ route('user.ecommerce.shopify.connect') }}" class="sidebar-submenu-list__link">
                                                <span class="text">@lang('Connect')</span>
                                            </a>
                                        </li>

                                        <li class="sidebar-submenu-list__item {{ request()->routeIs('user.ecommerce.shopify.settings') ? 'active' : '' }}">
                                            <a href="{{ $shopifySettingsUrl }}" class="sidebar-submenu-list__link">
                                                <span class="text">@lang('Settings')</span>
                                            </a>
                                        </li>

                                        <li class="sidebar-submenu-list__item {{ request()->routeIs('user.ecommerce.shopify.webhooks') ? 'active' : '' }}">
                                            <a href="{{ $shopifyWebhooksUrl }}" class="sidebar-submenu-list__link">
                                                <span class="text">@lang('Webhooks')</span>
                                            </a>
                                        </li>

                                        <li class="sidebar-submenu-list__item {{ request()->routeIs('user.ecommerce.shopify.logs') ? 'active' : '' }}">
                                            <a href="{{ $shopifyLogsUrl }}" class="sidebar-submenu-list__link">
                                                <span class="text">@lang('Logs')</span>
                                            </a>
                                        </li>




                                    </ul>
                                </div>
                            </li>

                        </ul>
                    </div>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 MARKETING INTELLIGENCE
                 - Future analytics and segmentation modules
            ========================================================== --}}
            <li class="sidebar-menu-list__title">
                <span class="text">@lang('MARKETING INTELLIGENCE')</span>
            </li>

            {{-- =========================================================
                 RFM SEGMENTATION
                 - Uses URL-based links for future pages to avoid sidebar
                   failure before named routes are created.
            ========================================================== --}}
            <li class="sidebar-menu-list__item has-dropdown {{ $rfmActive ? $activeParentClass : '' }}">
                <a href="javascript:void(0)"
                   class="sidebar-menu-list__link"
                   data-bs-toggle-custom="tooltip"
                   data-bs-placement="right"
                   data-bs-title="@lang('RFM Segmentation')">
                    <span class="icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </span>
                    <span class="text">@lang('RFM Segmentation')</span>
                </a>

                <div class="sidebar-submenu {{ $rfmActive ? $openClass : '' }}">
                    <ul class="sidebar-submenu-list">
                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.rfm.dashboard') }}">
                            <a href="{{ route('user.ecommerce.rfm.dashboard') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('RFM Dashboard')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.rfm.vip.customers') }}">
                            <a href="{{ route('user.ecommerce.rfm.vip.customers') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('VIP Customers')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.rfm.loyal.customers') }}">
                            <a href="{{ route('user.ecommerce.rfm.loyal.customers') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Loyal Customers')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.rfm.new.customers') }}">
                            <a href="{{ route('user.ecommerce.rfm.new.customers') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('New Customers')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.rfm.at.risk') }}">
                            <a href="{{ route('user.ecommerce.rfm.at.risk') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('At Risk')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.rfm.lost.customers') }}">
                            <a href="{{ route('user.ecommerce.rfm.lost.customers') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Lost Customers')</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- =========================================================
                 SEGMENTS & TARGETING
            ========================================================== --}}
            <li class="sidebar-menu-list__item has-dropdown {{ $segmentsActive ? $activeParentClass : '' }}">
                <a href="javascript:void(0)"
                   class="sidebar-menu-list__link"
                   data-bs-toggle-custom="tooltip"
                   data-bs-placement="right"
                   data-bs-title="@lang('Segments')">
                    <span class="icon">
                        <i class="fa-solid fa-filter"></i>
                    </span>
                    <span class="text">@lang('Segments & Targeting')</span>
                </a>

                <div class="sidebar-submenu {{ $segmentsActive ? $openClass : '' }}">
                    <ul class="sidebar-submenu-list">
                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.segments') }}">
                            <a href="{{ route('user.ecommerce.segments') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Segments')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.segments.customer.filters') }}">
                            <a href="{{ route('user.ecommerce.segments.customer.filters') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Customer Filters')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.segments.buyers') }}">
                            <a href="{{ route('user.ecommerce.segments.buyers') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Buyers')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.segments.repeat.customers') }}">
                            <a href="{{ route('user.ecommerce.segments.repeat.customers') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Repeat Customers')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.segments.high.value.customers') }}">
                            <a href="{{ route('user.ecommerce.segments.high.value.customers') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('High Value Customers')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.segments.abandoned.cart') }}">
                            <a href="{{ route('user.ecommerce.segments.abandoned.cart') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Abandoned Cart')</span>
                            </a>
                        </li>

                        <li class="sidebar-submenu-list__item {{ menuActive('user.ecommerce.segments.message.history') }}">
                            <a href="{{ route('user.ecommerce.segments.message.history') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Message History')</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- =========================================================
                 ANALYTICS
            ========================================================== --}}
            <li class="sidebar-menu-list__item {{ $analyticsActive ? 'active' : '' }}">
                <a href="{{ route('user.ecommerce.analytics') }}"
                   class="sidebar-menu-list__link"
                   data-bs-toggle-custom="tooltip"
                   data-bs-placement="right"
                   data-bs-title="@lang('Analytics')">
                    <span class="icon">
                        <i class="fa-solid fa-chart-pie"></i>
                    </span>
                    <span class="text">@lang('Analytics')</span>
                </a>
            </li>

            {{-- =========================================================
                 HEALTH CHECK
            ========================================================== --}}
            <li class="sidebar-menu-list__item {{ $healthActive ? 'active' : '' }}">
                <a href="{{ route('user.ecommerce.health') }}"
                   class="sidebar-menu-list__link"
                   data-bs-toggle-custom="tooltip"
                   data-bs-placement="right"
                   data-bs-title="@lang('Health Check')">
                    <span class="icon">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </span>
                    <span class="text">@lang('Health Check')</span>
                </a>
            </li>

            {{-- =========================================================
                 CRM TOOLS
            ========================================================== --}}
            <x-permission_check :permission="['view inbox', 'view customer', 'view agent']">
                <li class="sidebar-menu-list__title">
                    <span class="text">@lang('CRM TOOLS')</span>
                </li>
            </x-permission_check>

            <x-permission_check permission="view inbox" data-link="{{ route('user.inbox.list') }}">
                <li class="sidebar-menu-list__item">
                    <a href="{{ route('user.inbox.list') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.inbox.*') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Inbox')">
                        <span class="icon"><i class="fas fa-sms"></i></span>
                        <span class="text">@lang('Manage Inbox')</span>
                    </a>
                </li>
            </x-permission_check>

            <x-permission_check permission="view customer">
                <li class="sidebar-menu-list__item" data-link="{{ route('user.customer.list') }}">
                    <a href="{{ route('user.customer.list') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.customer.*') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Customer')">
                        <span class="icon"><i class="fas fa-users"></i></span>
                        <span class="text">@lang('Manage Customer')</span>
                    </a>
                </li>
            </x-permission_check>

            <x-permission_check permission="view agent">
                <li class="sidebar-menu-list__item" data-link="{{ route('user.agent.list') }}">
                    <a href="{{ route('user.agent.list') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.agent.*') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Agent')">
                        <span class="icon"><i class="fa-solid fa-users-gear"></i></span>
                        <span class="text">@lang('Manage Agent')</span>
                    </a>
                </li>
            </x-permission_check>

            <x-permission_check permission="view ticket" data-link="{{ route('ticket.index') }}">
                <li class="sidebar-menu-list__item">
                    <a href="{{ route('ticket.index') }}"
                       class="sidebar-menu-list__link {{ menuActive('ticket.index') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Support Ticket')">
                        <span class="icon"><i class="fa-solid fa-tags"></i></span>
                        <span class="text">@lang('Support Ticket')</span>
                    </a>
                </li>
            </x-permission_check>

            {{-- =========================================================
                 FINANCE
            ========================================================== --}}
            @if (isParentUser())
                <li class="sidebar-menu-list__title">
                    <span class="text">@lang('FINANCE')</span>
                </li>

                <li class="sidebar-menu-list__item" data-link="{{ route('user.deposit.history') }}">
                    <a href="{{ route('user.deposit.history') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.deposit.*') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Deposit')">
                        <span class="icon"><i class="fa-solid fa-money-bill-transfer"></i></span>
                        <span class="text">@lang('Manage Deposit')</span>
                    </a>
                </li>

                <li class="sidebar-menu-list__item" data-link="{{ route('user.withdraw.history') }}">
                    <a href="{{ route('user.withdraw.history') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.withdraw*') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Withdraw')">
                        <span class="icon"><i class="fa-solid fa-wallet"></i></span>
                        <span class="text">@lang('Manage Withdraw')</span>
                    </a>
                </li>

                <li class="sidebar-menu-list__item" data-link="{{ route('user.transactions') }}">
                    <a href="{{ route('user.transactions') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.transactions') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Transactions')">
                        <span class="icon"><i class="fa-solid fa-right-left"></i></span>
                        <span class="text">@lang('Transactions Logs')</span>
                    </a>
                </li>

                <li class="sidebar-menu-list__item" data-link="{{ route('user.referral.index') }}">
                    <a href="{{ route('user.referral.index') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.referral.index') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Referrals')">
                        <span class="icon"><i class="fa-solid fa-share-nodes"></i></span>
                        <span class="text">@lang('Manage Referrals')</span>
                    </a>
                </li>
            @endif

            {{-- =========================================================
                 BILLING & PROFILE
            ========================================================== --}}
            <li class="sidebar-menu-list__title">
                <span class="text">@lang('BILLING & PROFILE')</span>
            </li>

            @if (isParentUser())
                <li class="sidebar-menu-list__item" data-link="{{ route('user.whatsapp.account.index') }}">
                    <a href="{{ route('user.whatsapp.account.index') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.whatsapp.account.*') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Whatsapp Accounts')">
                        <span class="icon"><i class="fa-solid fa-phone"></i></span>
                        <span class="text">@lang('Whatsapp Accounts')</span>
                    </a>
                </li>

                <li class="sidebar-menu-list__item" data-link="{{ route('user.subscription.index') }}">
                    <a href="{{ route('user.subscription.index') }}"
                       class="sidebar-menu-list__link {{ menuActive('user.subscription.index') }}"
                       data-bs-toggle-custom="tooltip"
                       data-bs-placement="right"
                       data-bs-title="@lang('Subscription Info')">
                        <span class="icon"><i class="fa-solid fa-dollar-sign"></i></span>
                        <span class="text">@lang('Subscription Info')</span>
                    </a>
                </li>
            @endif

            <li class="sidebar-menu-list__item" data-link="{{ route('user.profile.setting') }}">
                <a href="{{ route('user.profile.setting') }}"
                   class="sidebar-menu-list__link {{ menuActive('user.profile.setting') }}"
                   data-bs-toggle-custom="tooltip"
                   data-bs-placement="right"
                   data-bs-title="@lang('Profile')">
                    <span class="icon"><i class="fas fa-user"></i></span>
                    <span class="text">@lang('Manage Profile')</span>
                </a>
            </li>

        </ul>
    </div>
</div>
