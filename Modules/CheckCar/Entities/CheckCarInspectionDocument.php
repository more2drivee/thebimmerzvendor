<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;

class CheckCarInspectionDocument extends Model
{
    protected $table = 'checkcar_inspection_documents';

    protected $fillable = [
        'inspection_id',
        'party',
        'document_type',
        'file_path',
        'mime_type',
    ];

    public function inspection()
    {
        return $this->belongsTo(CarInspection::class, 'inspection_id');
    }
}
