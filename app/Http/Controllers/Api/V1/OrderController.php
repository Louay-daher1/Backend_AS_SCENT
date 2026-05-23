<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        protected CreateOrderAction $createOrderAction,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->createOrderAction->execute($request->validated());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
