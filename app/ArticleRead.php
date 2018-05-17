<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * 文章阅读记录数据库操作
 */
class ArticleRead extends Model
{
	protected $table = 'article_read';

    public $timestamps = false; 
}
?>