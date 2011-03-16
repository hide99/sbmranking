<?php

class SbmRankingBehavior extends ModelBehavior {
	
	var $SbmRanking;
	var $config = array();
	
	function setup(&$Model,$config = array())
	{
		$this->SbmRanking = Classregistry::init('Sbmranking.SbmRanking');
		$this->SbmPastRanking = Classregistry::init('Sbmranking.SbmPastRanking');
		$this->config = Configure::read('sbmranking');
	}
	
	/*
		過去のランキングを生成
	*/
	function _addPastRanking()
	{
		$res = $this->SbmRanking->find('all');
		
		$ar = array();
		foreach($res as $v){
			$ar[]['SbmPastRanking'] = $v['SbmRanking'];
		}
		
		$this->SbmPastRanking->set($ar);
		
		$res = false;
		if($this->SbmPastRanking->saveAll($ar)){
			$res = true;
		}
		
		$this->SbmRanking->query('UPDATE `'.$this->SbmRanking->tablePrefix.'sbm_rankings` SET `rate` = 0');
		return $res;
	}
	
	/*
		先月のレコードがあるかチェック。
		@return bool 先月のデータがあれば、true
	*/
	function _isLastMonth()
	{
		App::import('Helper', 'Time');
		$time = new TimeHelper();  
		$last_month[0] = date('Y-m-01',strtotime('-1 month'));  
		$last_month[1] = date(DATE_W3C, mktime(0, 0, 0, date('m'), 0, date('y')));  
		$q = $time->daysAsSql($last_month[0],$last_month[1],'modified');
		
		$res = false;
		if($this->SbmRanking->hasAny($q)){
			$res = true;
		}
		
		return $res;
		
	}
	
	/*
		指定したIDのポイントを取得
		@params int $foreign_key ユーザーIDなど
		@return ユーザー情報と合計ポイントを返す
	*/
	function getPoint(&$Model,$foreign_key)
	{
		$res = $this->SbmRanking->find('first',array('conditions' => array('foreign_key' => $foreign_key) , 'fields' => $this->config['fields']));
		
		if(isset($res[0]['total_point'])){
			$res = $res[0]['total_point'];
		} else {
			$res = $this->config['empty'];
		}
		
		return $res;
	}
	
	/*
		全文  	id 	foreign_key 	hatena 	facebook_like 	facebook_share 	facebook_comment 	facebook_total 	twitter 	rate 	modified
		指定した limit 件数分、ポイントトップのユーザーを読み込んで行く
		@params array $conditions find と同じクエリを突っ込む。
		@return ポイントの多いユーザー順に読み出す
	*/
	function getTops(&$Model,$q = array('conditions' => array()))
	{
		//トップ10を取得
		$Model->bindModel(array('hasOne' => array('SbmRanking' =>
			array(
				'foreignKey' => 'foreign_key',
				'order' => 'total_point desc',
				'fields' => array(
					$this->config['fields']
				),
			),
		)));
		
		if(!isset($q['limit'])){
			$q['limit'] = 10;
		}
	
		$point = $Model->find('all',array(
			'group' => 'User.id',
			'conditions' => $q['conditions'],
			'limit' => $q['limit']
			)
		);
		
		return $point;
	}
	
	/*
		指定したIDのレートを指定した分、プラスする
		@params int $foreign_key ユーザーIDなど
		@params int $point ポイント
	*/
	function addRate(&$Model,$foreign_key,$rate)
	{
		$res = $this->SbmRanking->find('first',array('conditions' => array('foreign_key' => $foreign_key)));

		if(isset($res['SbmRanking']['rate'])){
			$data[$this->SbmRanking->alias]['id'] = $res['SbmRanking']['id'];
			$rate += $res['SbmRanking']['rate'];
		}
		
		$data[$this->SbmRanking->alias]['foreign_key'] = $foreign_key;
		$data[$this->SbmRanking->alias]['rate'] = $rate;
		
		$res = false;
		$this->SbmRanking->set($data);
		if($this->SbmRanking->save()){
			$res = true;
		}
		
		return $res;
	}
	
	/*
		先月のデータがあれば先月のデータを更新。
		最新のブックマーク数を取得する。
	*/
	function addSbm(&$Model)
	{
		if($this->_isLastMonth() && $this->config['past'] == true){
			$this->_addPastRanking();
		}
		
		$data[$this->SbmRanking->alias] = $_POST;
		
		$res = $this->SbmRanking->find('first',array('conditions' => array('foreign_key' => $_POST['foreign_key']) , 'fields' => array('id')));
		if(isset($res['SbmRanking']['id'])){
			$data[$this->SbmRanking->alias]['id'] = $res['SbmRanking']['id'];
		}
		
		$this->SbmRanking->set($data);
		
		$res = false;
		if($this->SbmRanking->save()){
			$res = true;
		}
		
		$point = $this->getPoint($Model,$_POST['foreign_key']);
		echo number_format($point);
	}
	
}

?>