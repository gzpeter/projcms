<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * 文章数据库操作
 */
class Article extends Model
{

	protected $table = 'article';

	protected $fillable = [
        'title',
        'summary',
        'author',
        'type',
        'status',
        'read_num',
        'collect_num',
        'state_update_time',
        'last_update_time',
        'created_at',
    ];

    public $timestamps = false; 

	public function segmentList()     
	{          
		return $this->hasMany('App\Segment');     
	} 
}
?>