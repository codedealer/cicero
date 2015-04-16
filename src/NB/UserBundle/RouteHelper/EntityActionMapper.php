<?php
namespace NB\UserBundle\RouteHelper;

class EntityActionMapper
{
	private $actionMap = [];

	public function registerAction($entityName, $actionType, $action){
			$entityName = $this->normilize($entityName);
			if(array_key_exists($entityName, $this->actionMap) 
				&& is_array($this->actionMap[$entityName])){
				$this->actionMap[$entityName][$actionType] = $action;
			}
			else{
				$this->actionMap[$entityName] = [$actionType => $action];
			}
			return $this;
	}

	public function map($entityName, $actionType){
		$entityName = $this->normilize($entityName);
		if(array_key_exists($entityName, $this->actionMap) 
			&& is_array($this->actionMap[$entityName])
			&& array_key_exists($actionType, $this->actionMap[$entityName])){
			return $this->actionMap[$entityName][$actionType];
		}
		return false;
	}

	private function normilize($string){
		return str_replace(['\\','_'], '', strtolower($string));
	}
}
?>