<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'contact_name', 'phone', 'email', 'address'];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function inventoryItems() { return $this->hasMany(InventoryItem::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
}
