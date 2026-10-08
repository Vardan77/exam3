<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponent $component
 */

include __DIR__ . '/sidepanel_rules.php';

//По прямой ссылке (без IFRAME) обертка сама открывает слайдер, а при его закрытии уходит на список
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'exam31.ticket:examelements.info',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '.default',
		'POPUP_COMPONENT_PARAMS' => [
			'ELEMENT_ID' => $arResult['VARIABLES']['ELEMENT_ID'] ?? null,
		],
		'POPUP_COMPONENT_PARENT' => $component,
		'USE_PADDING' => true,
		'BUTTONS' => ['close'],
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => $arResult['LIST_PAGE_URL'],
	]
);
