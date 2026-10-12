<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class Application extends Model {
    protected $table = 'talent_applications';
    protected $fillable = ['tenant_id','job_id','candidate_id','stage','source','utm_campaign','submitted_at'];
    protected $casts = ['submitted_at'=>'datetime'];
    public function job() { return $this->belongsTo(Job::class,'job_id'); }
    public function candidate() { return $this->belongsTo(Candidate::class,'candidate_id'); }
}
