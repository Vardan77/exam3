<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * @var array $arResult
 */

Loc::loadMessages(__FILE__);
Extension::load('sidepanel');

//Правила открытия страниц в слайдере
$rules = [
	[
		'condition' => [str_replace('#ELEMENT_ID#', '(\d+)', $arResult['DETAIL_PAGE_URL'])],
		'options' => [
			'width' => 900,
			'cacheable' => false,
		],
	],
	[
		'condition' => [str_replace('#ELEMENT_ID#', '(\d+)', $arResult['INFO_PAGE_URL'])],
		'options' => [
			'width' => 700,
			'cacheable' => false,
			'label' => [
				'text' => Loc::getMessage('EXAM31_ELEMENTS_SIDEPANEL_INFO_LABEL'),
				'color' => '#FFFFFF',
				'bgColor' => '#7BD500',
				'opacity' => 90,
			],
		],
	],
];
?>
<script>
	BX.ready(function () {
		BX.SidePanel.Instance.bindAnchors({rules: <?= Json::encode($rules) ?>});
	});
</script>
