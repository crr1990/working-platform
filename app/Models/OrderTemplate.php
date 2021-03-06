<?php
/**
 * Created by PhpStorm.
 * User: chenrongrong
 * Date: 2019/9/21
 * Time: 2:47 PM
 */


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTemplate extends Model
{
    public $table = "order_template";
    protected $guarded = [];
    public $timestamps = false;

    public function params()
    {
        return $this->hasMany('App\Models\OrderTemplateParams', 'temp_id', 'id');
    }
}