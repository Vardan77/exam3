<?php B_PROLOG_INCLUDED === true || die();

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 */

$APPLICATION->IncludeComponent(
	'bitrix:ui.form',
	'.default',
	$arResult['form']
);
