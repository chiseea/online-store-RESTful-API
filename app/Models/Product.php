<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock'
    ];

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
