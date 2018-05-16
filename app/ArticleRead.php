<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * 站内消息数据库操作
 */
class ArticleRead extends Model
{

 	protected $table = 'article_read';
	protected $fillable = [
        'article_id',
        'user_id',
    ];
 	
 	public $timestamps = false; 
}
?>