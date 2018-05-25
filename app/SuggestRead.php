<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * 用户知识等级记录表数据库操作
 */
class SuggestRead extends Model
{
	protected $table = 'suggest_read_feedback';

    public $timestamps = false; 
}
?>