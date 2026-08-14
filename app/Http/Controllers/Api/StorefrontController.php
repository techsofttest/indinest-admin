<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Brand;
use App\Models\Advertisement;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomePageSection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    private function assetUrl(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }

    private function variantPayload($variant): array
    {
        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'name' => $variant->name ?? $variant->size,
            'price' => (float) $variant->selling_price,
            'buying_price' => $variant->buying_price !== null ? (float) $variant->buying_price : null,
            'stock' => (int) $variant->stock,
        ];
    }

    private function productPayload(Product $product): array
    {
        $inStockVariants = $product->variants->filter(fn ($variant) => (int) $variant->stock > 0);
        $variant = $inStockVariants->first();
        $price = $variant ? (float) $variant->selling_price : 0.0;
        $reviews = $product->reviews;
        $rating = round((float) ($reviews->avg('review_rating') ?: 0), 1);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'sku' => $product->sku,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'featured_image' => $this->assetUrl($product->featured_image),
            'gallery' => $product->images->map(fn ($image) => $this->assetUrl($image->image_path))->values(),
            'price' => $price,
            'min_price' => (float) ($product->min_price ?? $price),
            'max_price' => (float) ($product->max_price ?? $price),
            'is_featured' => (bool) $product->is_featured,
            'is_active' => (bool) $product->is_active,
            'rating' => $rating,
            'review_count' => $reviews->count(),
            'variants' => $inStockVariants->map(fn ($variant) => $this->variantPayload($variant))->values(),
            'description' => $product->description,
            'key_features' => $product->key_features,
            'style_fit_tips' => $product->style_fit_tips,
            'shipping_returns' => $product->shipping_returns,
            'faq' => $product->faq,
            'fabric' => $product->fabric?->name,
            'colour' => $product->color?->name,
            'occasion' => $product->occasion?->name,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
        ];
    }

    private function getCategoryImageUrl(Category $category): ?string
    {
        if ($category->image) {
            return $this->assetUrl($category->image);
        }

        // Try to get a product directly in this category
        $product = Product::query()
            ->where('category_id', $category->id)
            ->whereNotNull('featured_image')
            ->where('is_active', true)
            ->first();

        if ($product) {
            return $this->assetUrl($product->featured_image);
        }

        // Try to get a product from any child categories (subcategories)
        $childIds = Category::query()
            ->where('parent_id', $category->id)
            ->pluck('id');

        if ($childIds->isNotEmpty()) {
            $product = Product::query()
                ->whereIn('category_id', $childIds)
                ->whereNotNull('featured_image')
                ->where('is_active', true)
                ->first();

            if ($product) {
                return $this->assetUrl($product->featured_image);
            }
        }

        return null;
    }

    private function categoryPayload(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'image_url' => $this->getCategoryImageUrl($category),
            'icon_url' => $this->assetUrl($category->icon),
            'href' => '/category/' . $category->slug,
        ];
    }

    private function getCategoryIds(Category $category): array
    {
        $ids = [$category->id];

        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getCategoryIds($child));
        }

        return array_values(array_unique($ids));
    }

    private function brandPayload(Brand $brand): array
    {
        $firstProduct = $brand->products->first();

        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'logo_url' => $this->assetUrl($brand->logo),
            'product_image_url' => $firstProduct ? $this->assetUrl($firstProduct->featured_image) : null,
            'link' => '/brand/' . $brand->slug,
        ];
    }

    private function topOfferPayloads(int $limit = 20)
    {
        return Product::query()
            ->with(['brand', 'category', 'variants', 'images', 'reviews'])
            ->where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
            ->get()
            ->filter(function (Product $product) {
                $minPrice = (float) ($product->min_price ?? 0);
                $maxPrice = (float) ($product->max_price ?? 0);

                return $minPrice > 0 && $maxPrice > $minPrice;
            })
            ->map(function (Product $product) {
                $minPrice = (float) ($product->min_price ?? 0);
                $maxPrice = (float) ($product->max_price ?? 0);
                $discountPercent = (int) round((1 - ($minPrice / $maxPrice)) * 100);

                return [
                    'id' => $product->id,
                    'title' => $product->name,
                    'label' => $discountPercent . '% OFF',
                    'discount_percent' => $discountPercent,
                    'image_url' => $this->assetUrl($product->featured_image),
                    'href' => '/product/' . $product->slug,
                ];
            })
            ->sortByDesc('discount_percent')
            ->take($limit)
            ->values();
    }

    private function homeSectionPayload(HomePageSection $section): array
    {
        $limit = max(1, min((int) $section->item_limit, 48));
        $items = collect();

        if ($section->source === HomePageSection::SOURCE_CUSTOM_PRODUCTS && filled($section->product_ids)) {
            $products = Product::query()
                ->with(['brand', 'category', 'variants', 'reviews', 'images'])
                ->whereIn('id', $section->product_ids)
                ->where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
                ->get()
                ->sortBy(fn (Product $product) => array_search($product->id, $section->product_ids, true));

            $items = $products->map(fn (Product $product) => $this->productPayload($product))->values();
        } elseif ($section->source === HomePageSection::SOURCE_FEATURED_PRODUCTS) {
            $items = Product::query()
                ->with(['brand', 'category', 'variants', 'reviews', 'images'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
                ->latest()
                ->take($limit)
                ->get()
                ->map(fn (Product $product) => $this->productPayload($product))
                ->values();
        } elseif ($section->source === HomePageSection::SOURCE_LATEST_PRODUCTS) {
            $items = Product::query()
                ->with(['brand', 'category', 'variants', 'reviews', 'images'])
                ->where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
                ->latest()
                ->take($limit)
                ->get()
                ->map(fn (Product $product) => $this->productPayload($product))
                ->values();
        } elseif ($section->source === HomePageSection::SOURCE_CUSTOM_CATEGORIES && filled($section->category_ids)) {
            $categories = Category::query()
                ->whereIn('id', $section->category_ids)
                ->where('is_active', true)
                ->get()
                ->sortBy(fn (Category $category) => array_search($category->id, $section->category_ids, true));

            $items = $categories->map(fn (Category $category) => $this->categoryPayload($category))->values();
        } elseif ($section->source === HomePageSection::SOURCE_CATEGORIES) {
            $items = Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->take($limit)
                ->get()
                ->map(fn (Category $category) => $this->categoryPayload($category))
                ->values();
        } elseif ($section->source === HomePageSection::SOURCE_CUSTOM_BRANDS && filled($section->brand_ids)) {
            $brands = Brand::query()
                ->with(['products' => fn ($q) => $q->where('is_active', true)->take(1)])
                ->whereIn('id', $section->brand_ids)
                ->get()
                ->sortBy(fn (Brand $brand) => array_search($brand->id, $section->brand_ids, true));

            $items = $brands->map(fn (Brand $brand) => $this->brandPayload($brand))->values();
        } elseif ($section->source === HomePageSection::SOURCE_BRANDS) {
            $items = Brand::query()
                ->with(['products' => fn ($q) => $q->where('is_active', true)->take(1)])
                ->take($limit)
                ->get()
                ->map(fn (Brand $brand) => $this->brandPayload($brand))
                ->values();
        } elseif ($section->source === HomePageSection::SOURCE_BANNERS) {
            $items = Banner::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->take($limit)
                ->get()
                ->map(fn (Banner $banner) => [
                    'id' => $banner->id,
                    'name' => $banner->name,
                    'image_url' => $this->assetUrl($banner->image),
                    'url' => $banner->url,
                ])->values();
        } elseif ($section->source === HomePageSection::SOURCE_ADVERTISEMENT) {
            $advertisement = Advertisement::query()->first();

            $items = $advertisement ? collect([[
                'id' => $advertisement->id,
                'name' => $advertisement->name,
                'title' => $advertisement->title,
                'banner_url' => $this->assetUrl($advertisement->banner),
                'url' => $advertisement->url,
            ]]) : collect();
        } elseif ($section->source === HomePageSection::SOURCE_TOP_OFFERS) {
            $items = $this->topOfferPayloads($limit);
        }

        return [
            'id' => $section->id,
            'name' => $section->name,
            'type' => $section->type,
            'source' => $section->source,
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'link_label' => $section->link_label,
            'link_url' => $section->link_url,
            'background_color' => $section->background_color,
            'settings' => $section->settings,
            'items' => $items,
        ];
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->withCount('products')
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories->map(function (Category $category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image_url' => $this->getCategoryImageUrl($category),
                'icon_url' => $this->assetUrl($category->icon),
                'product_count' => $category->products_count,
                'children' => $category->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'image_url' => $this->getCategoryImageUrl($child),
                    'icon_url' => $this->assetUrl($child->icon),
                ])->values(),
            ];
        }));
    }

    public function header(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $homeFeatured = Category::query()
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])
            ->where('home_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'brand' => [
                'name' => config('app.name'),
                'logo' => asset('images/logo/brand-logo-nobg.png?v1'),
            ],
            'links' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'Products', 'href' => '/products'],
                ['label' => 'Categories', 'href' => '/categories'],
            ],
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'href' => '/category/' . $category->slug,
                'image_url' => $this->getCategoryImageUrl($category),
                'icon_url' => $this->assetUrl($category->icon),
            ])->values(),
            'home_featured_categories' => $homeFeatured->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'href' => '/category/' . $category->slug,
                'subcategories' => $category->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'href' => '/category/' . $child->slug,
                ])->values(),
            ])->values(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->toString());
        $perPage = max(1, min((int) $request->integer('per_page', 24), 100));

        $products = collect();
        if ($search !== '') {
            $products = Product::query()
                ->with(['brand', 'category', 'variants', 'reviews', 'images'])
                ->where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
                ->where(function ($query) use ($search) {
                    $keywords = array_filter(explode(' ', $search));
                    foreach ($keywords as $keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('sku', 'like', '%' . $keyword . '%')
                                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', '%' . $keyword . '%'))
                                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', '%' . $keyword . '%'))
                                ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%' . $keyword . '%'));
                        });
                    }
                })
                ->take($perPage)
                ->get()
                ->map(fn (Product $product) => $this->productPayload($product))
                ->values();
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->where(function ($query) use ($search) {
                $keywords = array_filter(explode(' ', $search));
                foreach ($keywords as $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('slug', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->orderBy('sort_order')
            ->take(12)
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'href' => '/category/' . $category->slug,
            ])
            ->values();

        $brands = Brand::query()
            ->where(function ($query) use ($search) {
                $keywords = array_filter(explode(' ', $search));
                foreach ($keywords as $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('slug', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->orderBy('name')
            ->take(12)
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'href' => '/brand/' . $brand->slug,
            ])
            ->values();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 12);
        $perPage = max(1, min($perPage, 48));
        $minProducts = max(18, $perPage);

        $baseQuery = Product::query()
            ->with(['brand', 'category', 'variants', 'reviews', 'images'])
            ->where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));

        $categoryIds = null;

        if ($categorySlug = $request->string('category')->toString()) {
            $category = Category::query()->where('slug', $categorySlug)->first();

            if ($category) {
                $categoryIds = $this->getCategoryIds($category);
            }
        }

        $query = (clone $baseQuery)
            ->when($categoryIds, fn ($query) => $query->whereIn('category_id', $categoryIds));

        if ($brandSlug = $request->string('brand')->toString()) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $brandSlug));
        }

        if ($collectionSlug = $request->string('collection')->toString()) {
            $query->whereHas('collections', fn ($q) => $q->where('slug', $collectionSlug));
        }

        if ($departmentSlug = $request->string('department')->toString()) {
            $department = \App\Models\Department::where('slug', $departmentSlug)->first();
            if ($department) {
                $departmentIds = array_merge([$department->id], $department->children()->pluck('id')->all());
                $categoryIds = \App\Models\Category::whereIn('department_id', $departmentIds)->pluck('id')->all();
                if (!empty($categoryIds)) {
                    $subCategoryIds = \App\Models\Category::whereIn('parent_id', $categoryIds)->pluck('id')->all();
                    $allCategoryIds = array_merge($categoryIds, $subCategoryIds);
                    $query->whereIn('category_id', $allCategoryIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        // Checkbox filters: accept comma-separated or array values
        if ($colours = array_filter(explode(',', $request->string('colour')->toString()))) {
            $query->whereHas('color', fn ($q) => $q->whereIn('name', $colours));
        }

        if ($fabrics = array_filter(explode(',', $request->string('fabric')->toString()))) {
            $query->whereHas('fabric', fn ($q) => $q->whereIn('name', $fabrics));
        }

        if ($occasions = array_filter(explode(',', $request->string('occasion')->toString()))) {
            $query->whereHas('occasion', fn ($q) => $q->whereIn('name', $occasions));
        }

        if ($brands = array_filter(explode(',', $request->string('brand_name')->toString()))) {
            $query->whereHas('brand', fn ($q) => $q->whereIn('name', $brands));
        }

        $featuredFilter = $request->string('featured')->toString();
        if ($featuredFilter !== '') {
            if (in_array(strtolower($featuredFilter), ['1', 'true', 'yes'], true)) {
                $query->where('is_featured', true);
            } elseif (in_array(strtolower($featuredFilter), ['0', 'false', 'no'], true)) {
                $query->where('is_featured', false);
            }
        }

        $sort = $request->string('sort', 'latest')->toString();

        $query->when($sort === 'price_low', function ($q) {
                return $q->orderBy(
                    \App\Models\ProductVariant::select('selling_price')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->orderBy('selling_price', 'asc')
                        ->limit(1),
                    'asc'
                );
            })
            ->when($sort === 'price_high', function ($q) {
                return $q->orderBy(
                    \App\Models\ProductVariant::select('selling_price')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->orderBy('selling_price', 'desc')
                        ->limit(1),
                    'desc'
                );
            })
            ->when($sort === 'name_asc', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'name_desc', fn ($q) => $q->orderByDesc('name'))
            ->when($sort === 'featured', fn ($q) => $q->orderByDesc('is_featured'))
            ->when($sort === 'latest' || ! in_array($sort, ['price_low', 'price_high', 'name_asc', 'name_desc', 'featured'], true), fn ($q) => $q->latest());

        $products = $query->get();



        $page = (int) $request->integer('page', 1);
        $paginator = new LengthAwarePaginator(
            $products->forPage($page, $perPage),
            $products->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Product $product) => $this->productPayload($product))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function productById(int $id): JsonResponse
    {
        $product = Product::query()
            ->with(['brand', 'category', 'variants', 'reviews', 'images'])
            ->where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json($this->productPayload($product));
    }

    public function product(Product $product): JsonResponse
    {
        $product->load(['brand', 'category', 'variants', 'reviews', 'images']);

        return response()->json($this->productPayload($product));
    }

    public function departments(): JsonResponse
    {
        $departments = \App\Models\Department::query()
            ->with(['children' => function ($q) {
                $q->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($departments->map(fn ($department) => [
            'id' => $department->id,
            'name' => $department->name,
            'slug' => $department->slug,
            'image_url' => $this->assetUrl($department->image),
            'description' => $department->description,
            'sort_order' => (int) $department->sort_order,
            'href' => '/departments/' . $department->slug,
            'subdepartments' => $department->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'image_url' => $this->assetUrl($child->image),
                'description' => $child->description,
                'sort_order' => (int) $child->sort_order,
                'href' => '/departments/' . $child->slug,
            ])->values(),
        ]));
    }

    public function collections(): JsonResponse
    {
        $collections = \App\Models\Collection::query()->get();

        return response()->json($collections->map(fn ($collection) => [
            'id' => $collection->id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'image_url' => $this->assetUrl($collection->image),
            'href' => '/collections/' . $collection->slug,
        ]));
    }

    public function featuredCategories(): JsonResponse
    {
        $categories = \App\Models\Category::query()
            ->where('is_active', true)
            ->where('home_featured', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($categories->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'image_url' => $this->getCategoryImageUrl($category),
        ]));
    }

    public function masterFilters(): JsonResponse
    {
        return response()->json([
            'occasions' => \App\Models\Occasion::pluck('name')->filter()->values(),
            'fabrics' => \App\Models\Fabric::pluck('name')->filter()->values(),
            'colors' => \App\Models\Color::pluck('name')->filter()->values(),
            'brands' => \App\Models\Brand::pluck('name')->filter()->values(),
        ]);
    }

    public function home(): JsonResponse
    {
        $products = Product::query()
            ->with(['brand', 'category', 'variants', 'reviews', 'images'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
            ->latest()
            ->take(15)
            ->get();

        $advertisements = Advertisement::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Advertisement $ad) => [
                'id' => $ad->id,
                'name' => $ad->name,
                'title' => $ad->title,
                'banner_url' => $this->assetUrl($ad->banner),
                'url' => $ad->url,
            ])->values();

        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'name' => $banner->name,
                'image_url' => $this->assetUrl($banner->image),
                'url' => $banner->url,
            ])->values();

        $brands = Brand::query()
            ->with(['products' => fn ($q) => $q->where('is_active', true)->take(1)])
            ->get()
            ->map(function (Brand $brand, int $index) {
                $payload = $this->brandPayload($brand);

                return $payload + ['order' => $index + 1];
            })->values();

        $sections = HomePageSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HomePageSection $section) => $this->homeSectionPayload($section))
            ->values();

        $featuredCategories = Category::query()
            ->where('home_featured', true)
            ->where('is_active', true)
            ->get()
            ->map(function (Category $category) {
                $categoryIds = Category::query()
                    ->where('id', $category->id)
                    ->orWhere('parent_id', $category->id)
                    ->pluck('id');

                $products = Product::query()
                    ->with(['brand', 'category', 'variants', 'reviews', 'images'])
                    ->whereIn('category_id', $categoryIds)
                    ->where('is_active', true)
                    ->latest()
                    ->take(4)
                    ->get();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'products' => $products->map(fn (Product $product) => $this->productPayload($product))->values(),
                ];
            })
            ->values();

        $announcements = collect();

        return response()->json([
            'home_advertisement' => $advertisements->first(),
            'advertisements' => $advertisements,
            'banners' => $banners,
            'products' => $products->map(fn (Product $product) => $this->productPayload($product))->values(),
            'brands' => $brands,
            'sections' => $sections,
            'featured_categories' => $featuredCategories,
            'announcements' => $announcements,
        ]);
    }

    public function topOffers(): JsonResponse
    {
        return response()->json($this->topOfferPayloads());
    }

    public function faqs(): JsonResponse
    {
        $faqs = \App\Models\Faq::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($faqs);
    }

    public function lookbooks(): JsonResponse
    {
        $lookbooks = \App\Models\Lookbook::with(['lookbookProducts.product.category'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($lookbooks->map(fn ($l) => [
            'id' => $l->id,
            'title' => $l->title,
            'subtitle' => $l->subtitle,
            'slug' => $l->slug,
            'model_image' => $this->assetUrl($l->model_image),
            'model_alt' => $l->model_alt ?? $l->title,
            'items' => $l->lookbookProducts
                ->sortBy('sort_order')
                ->map(function ($lp) {
                    $p = $lp->product;
                    if (!$p || !$p->is_active) {
                        return null;
                    }
                    $price = $p->variants->first() ? (float) $p->variants->first()->selling_price : 0.00;
                    return [
                        'id' => $p->id,
                        'product_id' => $p->id,
                        'name' => $p->name,
                        'brand' => $p->brand ? $p->brand->name : 'IndiNest',
                        'price' => $price,
                        'category' => $p->category ? $p->category->name : 'General',
                        'image' => $this->assetUrl($p->featured_image),
                        'slug' => $p->slug,
                        'url' => '/products/' . $p->slug,
                    ];
                })
                ->filter()
                ->values()
        ])->values());
    }
}
