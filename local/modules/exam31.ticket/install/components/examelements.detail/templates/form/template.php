<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 */
?>

<?
$APPLICATION->IncludeComponent(
	'bitrix:ui.form',
	'.default',
	$arResult['form']
);
?>
