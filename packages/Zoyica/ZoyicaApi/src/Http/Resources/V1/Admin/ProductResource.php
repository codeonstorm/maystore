<?php

namespace Zoyica\ZoyicaApi\Http\Resources\V1\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $flat = $this->product_flats->first();

        return [
            'id'            => $this->id,
            'sku'           => $this->sku,
            'type'          => $this->type,
            'price'         => $flat?->price,
            'special_price' => $flat?->special_price,
            'inventories'   => $this->inventories->map(fn ($inv) => [
                'inventory_source_id' => $inv->inventory_source_id,
                'qty'                 => $inv->qty,
            ]),
        ];
    }
}
