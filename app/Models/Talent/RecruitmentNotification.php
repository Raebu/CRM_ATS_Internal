<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class RecruitmentNotification extends Model {
 protected $table='talent_notifications';
 protected $fillable=['tenant_id','source_event_id','kind','status','data','acknowledged_at'];
 protected $casts=['data'=>'array','acknowledged_at'=>'datetime'];
}
