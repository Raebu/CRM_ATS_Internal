<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class Candidate extends Model {
    protected $table = 'talent_candidates';
    protected $fillable = ['tenant_id','name','email','phone','notes'];
}
