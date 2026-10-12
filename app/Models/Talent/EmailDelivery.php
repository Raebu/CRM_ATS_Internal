<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class EmailDelivery extends Model {
 protected $table='talent_email_deliveries';
 protected $fillable=['tenant_id','notification_id','template_key','status','recipient_email','approved_at','approved_by','sent_at','last_error','template_version','template_checksum','subject_snapshot','body_snapshot'];
 protected $casts=['approved_at'=>'datetime','sent_at'=>'datetime'];
}
