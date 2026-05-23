<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartPreviewRequest;
use App\Http\Resources\CartPreviewResource;
use App\Services\PricingService;

class CartPreviewController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
    ) {}

    public function store(CartPreviewRequest $request): CartPreviewResource
    {
        $pricing = $this->pricingService->calculate(
            $request->validated('items'),
            $request->validated('discount_code'),
        );

        return new CartPreviewResource($pricing);
    }
}
