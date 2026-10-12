<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class PrivacyRequest extends Model {
 protected $table='talent_privacy_requests';
 protected $fillable=['tenant_id','candidate_id','request_type','status','notes','created_by','reviewed_by','verified_at','resolved_at'];
 protected $casts=['verified_at'=>'datetime','resolved_at'=>'datetime'];
 public function candidate(){return $this->belongsTo(Candidate::class);}
}
