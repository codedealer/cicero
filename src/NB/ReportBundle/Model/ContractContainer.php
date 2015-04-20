<?php
namespace NB\ReportBundle\Model;

class ContractContainer
{
	const MONTHLY = 1;
	const HOURLY = 2;
	const PROJECT = 3;
	const COURT = 4;

	protected static $info = [
		self::MONTHLY => [
		'label' => 'Абонентский договор', 
		'report' => 'Отчет по абонентскому договору', 
		'target_class' => 'Extend\Entity\client',
		'target_label' => 'Клиент',
		],
		self::HOURLY => [
		'label' => 'Почасовая оплата', 
		'report' => 'Отчет по почасовому договору', 
		'target_class' => 'Extend\Entity\client',
		'target_label' => 'Клиент',],
		self::PROJECT => [
		'label' => 'Проект', 
		'report' => 'Отчет по проекту', 
		'target_class' => 'Extend\Entity\Project',
		'target_label' => 'Проект',
		],
		self::COURT => [
		'label' => 'Судебное дело', 
		'report' => 'Отчет по судебному делу', 
		'target_class' => 'Extend\Entity\Court',
		'target_label' => 'Судебное дело',
		],
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

	public static function info($value){
		$ar = self::$info;
		return $ar[$value];
	}
}