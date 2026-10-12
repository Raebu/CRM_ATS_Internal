<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class Interview extends Model {
 protected $table='talent_interviews';
 protected $fillable=['tenant_id','application_id','title','starts_at','ends_at','location','status','notes'];
 protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime'];
 public function application(){return $this->belongsTo(Application::class);}
}
