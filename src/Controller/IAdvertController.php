<?php
namespace App\Controller;

//Открывает некоторые защищённые методы контроллера
interface IAdvertController {
	/**
	 * Открывает некоторые защищённые методы контроллера
	 * @param string $sFormTypeClass
	 * @param $oEntity
	 * @param array $aOptions
    */
	public function createFormEx(string $sFormTypeClass, $oEntity, array $aOptions);
	/**
	 * Открывает некоторые защищённые методы контроллера
	 * @param string $sType
	 * @param string $sMessage
	 */
	public function addFlashEx(string $sType, string $sMessage);
}