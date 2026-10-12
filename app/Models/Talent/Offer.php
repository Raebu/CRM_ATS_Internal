<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class Offer extends Model {
 protected $table='talent_offers';
 protected $fillable=['tenant_id','application_id','version','status','salary_amount','currency','conditions','accepted_at'];
 protected $casts=['accepted_at'=>'datetime','salary_amount'=>'decimal:2'];
 public function application(){return $this->belongsTo(Application::class);}
}
