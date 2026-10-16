<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class Job extends Model {
    protected $table = 'talent_jobs';
    protected $fillable = ['tenant_id','title','slug','description','location','employment_type','status','published_at','closes_at'];
    protected $casts = ['published_at'=>'datetime','closes_at'=>'datetime'];
}
