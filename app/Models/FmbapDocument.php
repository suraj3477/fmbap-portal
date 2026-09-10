<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FmbapDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'fmbap_project_id',
        'file_name',
        'file_path',
        'document_type',
        'uploaded_by',
        'latitude',
        'longitude',
    ];

    public function project()
    {
        return $this->belongsTo(FmbapProject::class, 'fmbap_project_id');
    }
}