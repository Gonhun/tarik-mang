<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiChatMessage extends Model
{
    use HasFactory;
    protected $fillable = ['chat_id','role','content','meta'];
    protected $casts = ['meta'=>'array'];

    public function chat() { return $this->belongsTo(Chat::class); }
}
