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
		'POPUP_COMPONENT_NAME' => 'exam31.ticket:examelements.detail',
		'POPUP_COMPONENT_TEMPLATE_NAME' => 'form',
		'POPUP_COMPONENT_PARAMS' => [
			'ELEMENT_ID' => $arResult['VARIABLES']['ELEMENT_ID'] ?? null,
			'DETAIL_PAGE_URL' => $arResult['DETAIL_PAGE_URL'],
			'LIST_PAGE_URL' => $arResult['LIST_PAGE_URL'],
		],
		'POPUP_COMPONENT_PARENT' => $component,
		'USE_PADDING' => true,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => $arResult['LIST_PAGE_URL'],
		//ID грида на странице списка
		'RELOAD_GRID_AFTER_SAVE' => 'EXAM31_GRID_ELEMENT',
	]
);
