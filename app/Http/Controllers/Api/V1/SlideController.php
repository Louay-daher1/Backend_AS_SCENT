<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SlideResource;
use App\Models\Slide;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SlideController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $slides = Slide::query()
            ->with(['product', 'category'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return SlideResource::collection($slides);
    }
}
