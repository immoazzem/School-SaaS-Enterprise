<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'student_invoice_id', 'amount', 'paid_on', 'payment_method', 'transaction_ref', 'payment_channel_metadata', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_on' => 'date:Y-m-d', 'payment_channel_metadata' => 'array'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentInvoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class);
    }
}
