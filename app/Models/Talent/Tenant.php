<?php
namespace App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
class Tenant extends Model {
    protected $table = 'talent_tenants';
    protected $fillable = ['name','slug'];
}
