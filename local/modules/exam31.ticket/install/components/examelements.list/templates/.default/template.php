<?php B_PROLOG_INCLUDED === true || die();

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

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
