<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FmbapProposal extends Model
{
    protected $fillable = [
        'user_id',
        'state',
        'scheme_name',
        'project_type',
        'river_basin',
        'district',
        'latitude',
        'longitude',
        'estimated_cost_cr',
        'description',
        'photos',
        'videos',
        'pdfs',
        'status',
        'bb_remarks',
        'mojs_remarks',
    ];

    protected $casts = [
        'photos' => 'array',
        'videos' => 'array',
        'pdfs' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
