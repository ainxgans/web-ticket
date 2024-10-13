<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Seller;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SellerRepositoryInterface;

class SellerRepository implements SellerRepositoryInterface
{
    public function getAllSellers()
    {
        return Seller::latest()->get();
    }
}