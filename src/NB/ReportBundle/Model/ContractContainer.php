<?php
namespace NB\ReportBundle\Model;

class ContractContainer
{
	const MONTHLY = 1;
	const HOURLY = 2;
	const PROJECT = 3;

	protected static $info = [
		self::MONTHLY => ['label' => 'Абонентский договор'],
		self::HOURLY => ['label' => 'Почасовая оплата'],
		self::PROJECT => ['label' => 'Проект'],
	];

	public static function has( $value){
		return array_key_exists($value, self::$info);
	}

	public static function datagridLabel(){
		return function($record) { 
			$ar = self::$info;
			return $ar[(int)$record->getValue('contract')]['label'];
		};
	}

	public static function label($value){
		$ar = self::$info;
		return $ar[$value]['label'];
	}
}