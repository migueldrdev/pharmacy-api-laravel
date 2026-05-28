<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_date',
        'total',
        'supplier_id',
        'purchase_document_type_id',
        'document_number',
        'user_id',
        'active',
        'user_created',
        'user_updated',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseDocumentType()
    {
        return $this->belongsTo(PurchaseDocumentType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }
}
