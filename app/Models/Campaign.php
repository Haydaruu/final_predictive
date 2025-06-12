<?php

namespace App\Models;

use App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['name', 'product_type', 'keterangan'];

    public function calls()
    {
        return $this->hasMany(Call::class);
    }
    public function nasabahs() 
    {
        return $this->hasMany(Nasabah::class);
    }   
}
