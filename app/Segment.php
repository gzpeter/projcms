<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * 文章内容数据库操作
 */
class Segment extends Model
{

	protected $table = 'segment';

	protected $fillable = [
        'article_id',
        'content',
        'type',
        'last_update_time',
        'created_at',
    ];
	
    public $timestamps = false; 
}
?>