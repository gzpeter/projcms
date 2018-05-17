<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * 文章更新历史记录表数据库操作
 */
class UpdateHistory extends Model
{

	protected $table = 'update_history';

    public $timestamps = false; 

}
?>