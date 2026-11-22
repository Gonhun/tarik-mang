<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiChatFiles extends Model
{
    use HasFactory;
    protected $fillable = ['chat_id','file_name','file_path','mime_type','meta'];
    protected $casts = ['meta'=>'array'];

    public function chat() { return $this->belongsTo(Chat::class); }
}
