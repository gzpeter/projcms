<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;


/**
 * 站内消息数据库操作
 */
class MessageSend extends Model
{
	public $table = 'message_send';
	
	public $timestamps = false;
}
?>