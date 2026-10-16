<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class StageHistory extends Model {
 protected $table='talent_stage_history';
 protected $fillable=['tenant_id','application_id','from_stage','to_stage','actor_id'];
}
