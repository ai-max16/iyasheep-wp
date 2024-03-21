<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'address',
        'access',
        'business_hours',
        'regular_holiday',
        'popular_menu1',
        'popular_menu2',
        'popular_menu3',
        'image_path',
        'shop_url',
        // 他の fillable プロパティを追加する場合はここに追加します
    ];

    use HasFactory;
}
