<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * 收藏数据库操作
 */
class Collection extends Model{
	public $table = 'article_collection';
 	
 	public $timestamps = false; 

 	public function articleList()
 	{
 		return $this->belongsTo('App\Article');    
 	}
}