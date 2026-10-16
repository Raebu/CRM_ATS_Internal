<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class DomainEvent extends Model {
    protected $table = 'talent_events';
    protected $fillable = ['tenant_id','event_id','event_type','event_version','aggregate_type','aggregate_id','payload','published_at'];
    protected $casts = ['payload'=>'array','published_at'=>'datetime'];
}
