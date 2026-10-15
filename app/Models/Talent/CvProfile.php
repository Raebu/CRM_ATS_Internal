<?php
namespace App\Models\Talent;

use Illuminate\Database\Eloquent\Model;

class CvProfile extends Model
{
    protected $table = 'talent_cv_profiles';
    protected $fillable = ['tenant_id','candidate_id','application_id','source_document_id','status','fields','review_flags','approved_at','approved_by'];
    protected $casts = ['fields'=>'array','review_flags'=>'array','approved_at'=>'datetime'];
}
