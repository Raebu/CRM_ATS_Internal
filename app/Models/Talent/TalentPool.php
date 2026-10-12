<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class TalentPool extends Model {
 protected $table='talent_pools';protected $fillable=['tenant_id','name','description'];
 public function candidates(){return $this->belongsToMany(Candidate::class,'talent_pool_members','pool_id','candidate_id')->withTimestamps();}
}
