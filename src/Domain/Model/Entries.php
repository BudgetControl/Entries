<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Entries extends Model
{
    use SoftDeletes;
    protected $table = 'entries';

    protected $hidden = ['id'];

    protected $fillable = [
        'date_time',
        'updated_at',
        'created_at',
        'uuid',
        'amount',
        'note',
        'type',
        'waranty',
        'transfer',
        'confirmed',
        'planned',
        'installment',
        'category_id',
        'model_id',
        'account_id',
        'transfer_id',
        'transfer_relation',
        'currency_id',
        'payment_type',
        'payee_id',
        'deleted_at',
        'geolocation',
        'workspace_id',
        'exclude_from_stats'
    ];

    public function setDateTimeAttribute($value)
    {
        $this->attributes['date_time'] = Carbon::createFromFormat('Y-d-m H:i:s',$value)->toAtomString();
    }

    public function setGeolocationAttribute($value)
    {
        $this->attributes['geolocation'] = json_encode($value);
    }

    public function getGeolocationAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * The users that belong to the role.
     */
    public function label()
    {
        return $this->belongsToMany(Labels::class, 'entry_labels','entry_id');
    }

    /**
     * Get the category
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategories::class, "category_id");
    }

    /**
     * Get the currency
     */
    public function currency()
    {
        return $this->belongsTo(Currencies::class);
    }

    /**
     * Get the payments_type
     */
    public function wallet()
    {
        return $this->belongsTo(Wallets::class);
    }

    /**
     * Get the payments_type
     */
    public function paymentType()
    {
        return $this->belongsTo(PaymentsTypes::class);
    }

    /**
     * Get the payee
     */
    public function payee()
    {
        return $this->belongsTo(Payees::class);
    }
}