<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons\CreateButton;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

Loader::includeModule('ui');

Toolbar::addFilter($arResult['filter']);
Toolbar::addButton(
	new CreateButton([
		'text' => Loc::getMessage('EXAM_ELEMENTS_LIST_ADD_BUTTON'),
		'link' => $arResult['ADD_PAGE_URL'],
	])
);
?>


<?
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult["grid"],
	$component
);
?>

<script>
	BX.ready(function () {
		//После сохранения элемента в слайдере обновляем грид
		BX.addCustomEvent('SidePanel.Slider:onMessage', function (event) {
			if (event.getEventId() !== 'exam31.ticket:onElementSave')
			{
				return;
			}
			var grid = BX.Main.gridManager.getInstanceById('<?= CUtil::JSEscape($arResult['GRID_ID']) ?>');
			if (grid)
			{
				grid.reload();
			}
		});
	});
</script>
