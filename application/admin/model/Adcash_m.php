<?php
namespace app\admin\model;
use think\Model;

class Adcash_m extends Model
{
    	
    protected $table = 'hellowd_adcash_data';
	
	
    protected $resultSetType = 'collection';	
	
	
	//平台汇总数据
	public static function getplatdata($where)
	{
		return $list = self::all(function($query)use($where){
			
		    $query->field("platform,sum(revenue) as revenue,if( platform=2,sum(started),sum(impression) ) as impression,if(platform=2,sum(views),sum(click)) as click ")->where($where)->group('platform');
			
			
		})->toArray();
		//echo db('hellowd_adcash_data')->getlastsql();
	}
     //每天汇总
	 public static function getplatdaydata($where)
	{
		return $list = self::all(function($query)use($where){
			
		    $query->field("platform,date,sum(revenue) as revenue,if( platform=2,sum(started),sum(impression) ) as impression,if(platform=2,sum(views),sum(click)) as click")->where($where)->group('platform,date');
			
			
		})->toArray();
		
	}
	
	//总数据
	public static function gettotaldata($where)
	{
		return $list = self::all(function($query)use($where){
			
		    $query->field("if(sum(revenue),sum(revenue),'0.00') as revenue")->where($where);
						
		})->toArray();
	}
}
