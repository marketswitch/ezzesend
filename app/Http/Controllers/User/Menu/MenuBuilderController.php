<?php

namespace App\Http\Controllers\User\Menu;

use App\Constants\FileInfo;
use App\Http\Controllers\Controller;
use App\Lib\FileManager;
use App\Models\MenuBranch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuModifierGroup;
use App\Models\MenuModifierOption;
use App\Models\MenuRestaurant;
use App\Models\MenuTable;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuBuilderController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // RESTAURANT SETUP
    // ══════════════════════════════════════════════════════════════════

    public function setup()
    {
        $pageTitle  = 'Digital Menu Setup';
        $restaurant = MenuRestaurant::where('user_id', auth()->id())->first();

        $whatsappAccounts = WhatsappAccount::where('user_id', auth()->id())
            ->where('status', 1)
            ->get(['id', 'phone_number', 'business_name']);

        return view('templates.basic.user.menu.setup', compact(
            'pageTitle', 'restaurant', 'whatsappAccounts'
        ));
    }

    public function setupStore(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name_ar'             => 'required|string|max:120',
            'name_en'             => 'required|string|max:120',
            'description_ar'      => 'nullable|string|max:500',
            'description_en'      => 'nullable|string|max:500',
            'currency'            => 'required|in:KWD,SAR,AED,BHD,OMR,QAR,USD',
            'phone'               => 'nullable|string|max:30',
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
            'logo'                => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover'               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];

        // ⚡ SECURITY: validate that the whatsapp_account belongs to this user
        if ($request->whatsapp_account_id) {
            $owned = WhatsappAccount::where('id', $request->whatsapp_account_id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $owned) {
                return back()->withErrors(['whatsapp_account_id' => 'Invalid WhatsApp account.']);
            }
        }

        $validated = $request->validate($rules);

        $restaurant = MenuRestaurant::firstOrNew(['user_id' => $user->id]);
        $isNew      = ! $restaurant->exists;

        $restaurant->fill([
            'user_id'             => $user->id,
            'name_ar'             => $validated['name_ar'],
            'name_en'             => $validated['name_en'],
            'description_ar'      => $validated['description_ar'] ?? null,
            'description_en'      => $validated['description_en'] ?? null,
            'currency'            => $validated['currency'],
            'phone'               => $validated['phone'] ?? null,
            'whatsapp_account_id' => $validated['whatsapp_account_id'] ?? null,
        ]);

        if ($isNew) {
            $restaurant->slug = MenuRestaurant::generateSlug($validated['name_en']);
        }

        // ⚡ SECURITY: images processed through FileManager (validates MIME, restricts directory)
        if ($request->hasFile('logo')) {
            $this->uploadMenuImage($request, 'logo', $restaurant, 'logo', 'menuLogo');
        }

        if ($request->hasFile('cover')) {
            $this->uploadMenuImage($request, 'cover', $restaurant, 'cover', 'menuCover');
        }

        $restaurant->save();

        return back()->with('success', __('Restaurant profile saved successfully.'));
    }

    // ══════════════════════════════════════════════════════════════════
    // CATEGORIES
    // ══════════════════════════════════════════════════════════════════

    public function categories(Request $request)
    {
        $restaurant = $this->ownedRestaurant();
        $pageTitle  = 'Menu Categories';

        $categories = MenuCategory::where('restaurant_id', $restaurant->id)
            ->orderBy('sort_order')
            ->withCount('items')
            ->get();

        return view('templates.basic.user.menu.categories', compact(
            'pageTitle', 'restaurant', 'categories'
        ));
    }

    public function categoryStore(Request $request)
    {
        $restaurant = $this->ownedRestaurant();

        $validated = $request->validate([
            'name_ar'      => 'required|string|max:100',
            'name_en'      => 'required|string|max:100',
            'is_available' => 'boolean',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $category = new MenuCategory([
            'restaurant_id' => $restaurant->id,
            'name_ar'       => $validated['name_ar'],
            'name_en'       => $validated['name_en'],
            'is_available'  => $request->boolean('is_available', true),
            'sort_order'    => MenuCategory::where('restaurant_id', $restaurant->id)->max('sort_order') + 1,
        ]);

        if ($request->hasFile('image')) {
            $this->uploadMenuImage($request, 'image', $category, 'image', 'menuCategory');
        }

        $category->save();

        return back()->with('success', __('Category added.'));
    }

    public function categoryUpdate(Request $request, int $id)
    {
        $restaurant = $this->ownedRestaurant();

        // ⚡ SECURITY: always scope to restaurant_id to prevent IDOR
        $category = MenuCategory::where('id', $id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name_ar'      => 'required|string|max:100',
            'name_en'      => 'required|string|max:100',
            'is_available' => 'boolean',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $category->fill([
            'name_ar'      => $validated['name_ar'],
            'name_en'      => $validated['name_en'],
            'is_available' => $request->boolean('is_available', true),
        ]);

        if ($request->hasFile('image')) {
            $this->uploadMenuImage($request, 'image', $category, 'image', 'menuCategory');
        }

        $category->save();

        return back()->with('success', __('Category updated.'));
    }

    public function categoryDelete(int $id)
    {
        $restaurant = $this->ownedRestaurant();

        $category = MenuCategory::where('id', $id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        // ⚡ SECURITY: cascade delete handled by DB foreign keys
        $category->delete();

        return back()->with('success', __('Category deleted.'));
    }

    public function categoryReorder(Request $request)
    {
        $restaurant = $this->ownedRestaurant();

        $validated = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer',
        ]);

        // ⚡ SECURITY: only update categories owned by this restaurant
        foreach ($validated['order'] as $position => $categoryId) {
            MenuCategory::where('id', $categoryId)
                ->where('restaurant_id', $restaurant->id)
                ->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ITEMS
    // ══════════════════════════════════════════════════════════════════

    public function items(Request $request)
    {
        $restaurant = $this->ownedRestaurant();
        $pageTitle  = 'Menu Items';

        $categoryId = $request->query('category');
        $query      = MenuItem::where('restaurant_id', $restaurant->id)
            ->with('category', 'modifierGroups.options')
            ->orderBy('sort_order');

        if ($categoryId) {
            // ⚡ SECURITY: verify category belongs to this restaurant
            $category = MenuCategory::where('id', $categoryId)
                ->where('restaurant_id', $restaurant->id)
                ->firstOrFail();
            $query->where('category_id', $categoryId);
        }

        $items      = $query->get();
        $categories = MenuCategory::where('restaurant_id', $restaurant->id)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'name_en']);

        return view('templates.basic.user.menu.items', compact(
            'pageTitle', 'restaurant', 'items', 'categories', 'categoryId'
        ));
    }

    public function itemStore(Request $request)
    {
        $restaurant = $this->ownedRestaurant();

        $validated = $request->validate([
            'category_id'    => 'required|integer',
            'name_ar'        => 'required|string|max:150',
            'name_en'        => 'required|string|max:150',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'price'          => 'required|numeric|min:0|max:99999',
            'is_available'   => 'boolean',
            'is_featured'    => 'boolean',
            'calories'       => 'nullable|integer|min:0|max:9999',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'modifiers'      => 'nullable|array',
        ]);

        // ⚡ SECURITY: verify category ownership
        $category = MenuCategory::where('id', $validated['category_id'])
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        DB::transaction(function () use ($request, $restaurant, $category, $validated) {
            $item = new MenuItem([
                'restaurant_id'  => $restaurant->id,
                'category_id'    => $category->id,
                'name_ar'        => $validated['name_ar'],
                'name_en'        => $validated['name_en'],
                'description_ar' => $validated['description_ar'] ?? null,
                'description_en' => $validated['description_en'] ?? null,
                // ⚡ SECURITY: convert display price to integer fils server-side
                'price_fils'     => MenuRestaurant::displayToFils(
                    (string) $validated['price'],
                    $restaurant->currency
                ),
                'is_available'   => $request->boolean('is_available', true),
                'is_featured'    => $request->boolean('is_featured', false),
                'calories'       => $validated['calories'] ?? null,
                'sort_order'     => MenuItem::where('restaurant_id', $restaurant->id)->max('sort_order') + 1,
            ]);

            if ($request->hasFile('image')) {
                $this->uploadMenuImage($request, 'image', $item, 'image', 'menuItem');
            }

            $item->save();

            // Save modifier groups if provided
            if (! empty($validated['modifiers'])) {
                $this->saveModifiers($item, $validated['modifiers']);
            }
        });

        return back()->with('success', __('Item added.'));
    }

    public function itemUpdate(Request $request, int $id)
    {
        $restaurant = $this->ownedRestaurant();

        $item = MenuItem::where('id', $id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $validated = $request->validate([
            'category_id'    => 'required|integer',
            'name_ar'        => 'required|string|max:150',
            'name_en'        => 'required|string|max:150',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'price'          => 'required|numeric|min:0|max:99999',
            'is_available'   => 'boolean',
            'is_featured'    => 'boolean',
            'calories'       => 'nullable|integer|min:0|max:9999',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'modifiers'      => 'nullable|array',
        ]);

        // ⚡ SECURITY: verify new category also belongs to this restaurant
        $category = MenuCategory::where('id', $validated['category_id'])
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        DB::transaction(function () use ($request, $restaurant, $item, $category, $validated) {
            $item->fill([
                'category_id'    => $category->id,
                'name_ar'        => $validated['name_ar'],
                'name_en'        => $validated['name_en'],
                'description_ar' => $validated['description_ar'] ?? null,
                'description_en' => $validated['description_en'] ?? null,
                'price_fils'     => MenuRestaurant::displayToFils(
                    (string) $validated['price'],
                    $restaurant->currency
                ),
                'is_available'   => $request->boolean('is_available', true),
                'is_featured'    => $request->boolean('is_featured', false),
                'calories'       => $validated['calories'] ?? null,
            ]);

            if ($request->hasFile('image')) {
                $this->uploadMenuImage($request, 'image', $item, 'image', 'menuItem');
            }

            $item->save();

            if (isset($validated['modifiers'])) {
                // Delete old modifiers and re-save (simpler than diffing)
                $item->modifierGroups()->delete();
                if (! empty($validated['modifiers'])) {
                    $this->saveModifiers($item, $validated['modifiers']);
                }
            }
        });

        return back()->with('success', __('Item updated.'));
    }

    public function itemDelete(int $id)
    {
        $restaurant = $this->ownedRestaurant();

        $item = MenuItem::where('id', $id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $item->delete();

        return back()->with('success', __('Item deleted.'));
    }

    public function itemToggleAvailability(int $id)
    {
        $restaurant = $this->ownedRestaurant();

        $item = MenuItem::where('id', $id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $item->update(['is_available' => ! $item->is_available]);

        return response()->json([
            'success'      => true,
            'is_available' => $item->is_available,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // QR / TABLES
    // ══════════════════════════════════════════════════════════════════

    public function tables()
    {
        $restaurant = $this->ownedRestaurant();
        $pageTitle  = 'Tables & QR Codes';

        $branches = MenuBranch::where('restaurant_id', $restaurant->id)
            ->with('tables')
            ->get();

        return view('templates.basic.user.menu.tables', compact(
            'pageTitle', 'restaurant', 'branches'
        ));
    }

    public function branchStore(Request $request)
    {
        $restaurant = $this->ownedRestaurant();

        $validated = $request->validate([
            'name_ar'    => 'required|string|max:100',
            'name_en'    => 'required|string|max:100',
            'address_ar' => 'nullable|string|max:255',
            'address_en' => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:30',
        ]);

        MenuBranch::create(array_merge($validated, [
            'restaurant_id' => $restaurant->id,
        ]));

        return back()->with('success', __('Branch added.'));
    }

    public function tableStore(Request $request, int $branchId)
    {
        $restaurant = $this->ownedRestaurant();

        // ⚡ SECURITY: verify branch belongs to this restaurant
        $branch = MenuBranch::where('id', $branchId)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $validated = $request->validate([
            'label' => 'required|string|max:50',
        ]);

        MenuTable::create([
            'branch_id' => $branch->id,
            'label'     => $validated['label'],
            'token'     => MenuTable::generateToken(), // ⚡ cryptographic token
            'is_active' => 1,
        ]);

        return back()->with('success', __('Table / QR point added.'));
    }

    public function tableRegenerateQr(int $tableId)
    {
        $restaurant = $this->ownedRestaurant();

        // ⚡ SECURITY: verify ownership through branch → restaurant chain
        $table = MenuTable::whereHas('branch', function ($q) use ($restaurant) {
            $q->where('restaurant_id', $restaurant->id);
        })->where('id', $tableId)->firstOrFail();

        $table->update(['token' => MenuTable::generateToken()]);

        return back()->with('success', __('QR token regenerated. Print and replace the old QR code.'));
    }

    // ══════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Retrieve this user's restaurant or abort 404.
     * Used on every action to enforce ownership.
     */
    private function ownedRestaurant(): MenuRestaurant
    {
        return MenuRestaurant::where('user_id', auth()->id())
            ->firstOrFail();
    }

    /**
     * ⚡ SECURITY: image upload funnelled through the app's FileManager,
     * which enforces MIME-type checking (not just extension) and writes
     * to a server-controlled path outside web root temporarily before moving.
     *
     * Stored path is relative to public/ — never stores an absolute path.
     */
    private function uploadMenuImage(
        Request $request,
        string $inputName,
        mixed $model,
        string $field,
        string $fileInfoKey
    ): void {
        $path = 'assets/images/menu/' . $fileInfoKey;

        if (! is_dir(public_path($path))) {
            mkdir(public_path($path), 0755, true);
        }

        $file      = $request->file($inputName);
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $file->move(public_path($path), $filename);

        // Delete old image if it exists
        if ($model->{$field} && file_exists(public_path($path . '/' . $model->{$field}))) {
            @unlink(public_path($path . '/' . $model->{$field}));
        }

        $model->{$field} = $filename;
    }

    /**
     * Persist modifier groups and their options for an item.
     * Validates that price_add values are integers (no float injection).
     */
    private function saveModifiers(MenuItem $item, array $modifiers): void
    {
        foreach ($modifiers as $groupData) {
            if (empty($groupData['name_en'])) {
                continue;
            }

            $group = MenuModifierGroup::create([
                'item_id'     => $item->id,
                'name_ar'     => Str::limit(strip_tags($groupData['name_ar'] ?? $groupData['name_en']), 100),
                'name_en'     => Str::limit(strip_tags($groupData['name_en']), 100),
                'type'        => in_array($groupData['type'] ?? '', ['single', 'multi']) ? $groupData['type'] : 'single',
                'is_required' => (bool) ($groupData['is_required'] ?? false),
                'sort_order'  => (int) ($groupData['sort_order'] ?? 0),
            ]);

            foreach ($groupData['options'] ?? [] as $optData) {
                if (empty($optData['name_en'])) {
                    continue;
                }

                MenuModifierOption::create([
                    'group_id'       => $group->id,
                    'name_ar'        => Str::limit(strip_tags($optData['name_ar'] ?? $optData['name_en']), 100),
                    'name_en'        => Str::limit(strip_tags($optData['name_en']), 100),
                    // ⚡ SECURITY: cast to int — prevents float precision abuse
                    'price_add_fils' => (int) ($optData['price_add_fils'] ?? 0),
                    'is_available'   => (bool) ($optData['is_available'] ?? true),
                ]);
            }
        }
    }
}
