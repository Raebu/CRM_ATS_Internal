<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class Document extends Model {
 protected $table='talent_documents';
 protected $fillable=['tenant_id','candidate_id','application_id','disk','storage_key','original_name','mime_type','size_bytes','scan_status'];
}
