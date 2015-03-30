<?php
namespace NB\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use NB\UserBundle\Model\InitializableControllerInterface;

class ControllerInitListener
{
	public function onKernelController(FilterControllerEvent $event){
		$controllerArray = $event->getController();

		if(!is_array($controllerArray))
			return;

		$controller = $controllerArray[0];
		if($controller instanceof ExceptionController)
			return;

		if($controller instanceof InitializableControllerInterface)
			$controller->init();
	}
}
?>