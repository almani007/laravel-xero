<?php
namespace Almani\Xero\Models;
use Illuminate\Database\Eloquent\Model;
class XeroToken extends Model
{
    protected $fillable = ['user_id','access_token','refresh_token','expires_at'];
    protected $dates = ['expires_at'];
}