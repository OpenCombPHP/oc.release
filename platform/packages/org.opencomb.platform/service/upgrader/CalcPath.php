<?php
namespace org\opencomb\platform\system\upgrader ;

class CalcPath{
	/**
	 * @param arrMap array 二维数组
	 * 第一维的每一个元素 值 表示一条边，键表示边的名称
	 * 第二维 'from' 表示起点，'to' 表示终点 
	 * 
	 * @param from 起点
	 * @param to 终点
	 * 返回值 ： 成功 ：数组，包含边的名称的集合。
	 * 失败 ： false 
	 */
	public function calc(array $arrMap , $from , $to){
		$this->buildLinkTable($arrMap);
		
		return $this->dijkstra($from , $to);
	}
	
	private function buildLinkTable(array $arrMap){
		foreach($arrMap as $name=> $arrEdge){
			$sFrom = $arrEdge['from'];
			$sTo = $arrEdge['to'];
			
			if(!isset($this->arrLinkTable[$sFrom])){
				$this->arrLinkTable[$sFrom] = array();
			}
			
			$this->arrLinkTable[$sFrom] [$name] = $arrEdge ;
		}
	}
	
	private function dijkstra($sSourcePt , $sTargetPt){
		$this->arrPt [$sSourcePt] = 
			array(
				'dist' => 0,
				'fromPt' => null,
				'fromEdge' => null,
			);
		
		$arrQueue = array();
		$nIn = 0 ;
		$nOut = 0 ;
		$arrQueue[$nIn++] = $sSourcePt ;
		while($nIn > $nOut){
			$sCurrentPt = $arrQueue[$nOut++] ;
			$nCurrentDist = $this->arrPt[$sCurrentPt]['dist'];
			if(isset($this->arrLinkTable[$sCurrentPt])){
				foreach($this->arrLinkTable[$sCurrentPt] as $name => $arrEdge ){
					$sTravelToPt = $arrEdge['to'];
					if(!isset($this->arrPt[$sTravelToPt])){
						$this->arrPt[$sTravelToPt] = array(
							'dist' => $nCurrentDist +1,
							'fromPt'=>$sCurrentPt,
							'fromEdge' => $name,
						);
						$arrQueue[$nIn++] = $sTravelToPt ;
					}
				}
			}
		}
		
		if(isset($this->arrPt[$sTargetPt])){
			$arrRtn = array();
			
			$sCurrentPt = $sTargetPt ;
			while( null !== $this->arrPt[$sCurrentPt]['fromPt'] ){
				array_unshift($arrRtn,$this->arrPt[$sCurrentPt]['fromEdge'] );
				$sCurrentPt = $this->arrPt[$sCurrentPt]['fromPt'] ;
			}
			return $arrRtn ;
		}else{
			return false;
		}
	}
	
	private $arrLinkTable = array () ;
	private $arrPt = array();
	/*
		array(
			'ptname' =>
				array(
					'dist' => num,
					'fromPt' => 'ptname' ,
					'fromEdge' => 'edgename' ,
				),
		);
	*/
}

