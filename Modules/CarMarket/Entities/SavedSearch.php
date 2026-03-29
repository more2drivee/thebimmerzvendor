<?php

namespace Modules\CarMarket\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Contact;

class SavedSearch extends Model
{
    protected $table = 'cm_saved_searches';

    protected $guarded = ['id'];

    protected $fillable = [
        'contact_id',
        'name',
        'filters',
        'notify_new_matches',
    ];

    protected $casts = [
        'filters' => 'array',
        'notify_new_matches' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
