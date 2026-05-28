<?php

namespace Zoyica\ZoyicaApi\Http\Controllers\V1\Admin\Catalog;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Zoyica\ZoyicaApi\Http\Requests\V1\Admin\ProductUpdateRequest;
use Zoyica\ZoyicaApi\Http\Resources\V1\Admin\ProductResource;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductInventoryRepository $inventoryRepository,
    ) {}

    /**
     * Update product price and/or stock.
     *
     * PATCH /api/v1/zoyica/admin/products/{id}/price-stock
     *
     * Request body (all fields optional but at least one required):
     *   price          – regular price (numeric, min 0)
     *   special_price  – sale price (must be < price)
     *   inventories    – object keyed by inventory_source_id, value = qty
     *                    e.g. { "1": 50 }
     */
    public function update(ProductUpdateRequest $request, int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        // Update price attributes via the product type instance
        if ($request->hasAny(['price', 'special_price'])) {
            Event::dispatch('catalog.product.update.before', $id);

            $this->productRepository->update(
                array_merge(
                    $request->only(['price', 'special_price']),
                    [
                        'channel' => core()->getCurrentChannelCode(),
                        'locale'  => app()->getLocale(),
                    ]
                ),
                $id
            );

            $product->refresh();

            Event::dispatch('catalog.product.update.after', $product);
        }

        // Update inventory per source
        if ($request->has('inventories')) {
            $this->inventoryRepository->saveInventories(
                $request->only('inventories'),
                $product
            );

            $product->refresh();
        }

        return response()->json([
            'data'    => new ProductResource($product),
            'message' => 'Product updated successfully.',
        ]);
    }
}
