<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'supplier_id', 'status', 'reference', 'notes',
        'total_cost', 'ordered_by', 'received_by', 'received_at',
    ];

    protected function casts(): array
    {
        return ['total_cost' => 'decimal:2', 'received_at' => 'datetime'];
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function orderedBy() { return $this->belongsTo(User::class, 'ordered_by'); }
    public function receivedBy() { return $this->belongsTo(User::class, 'received_by'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
}
