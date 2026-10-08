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

<script>
	BX.ready(function () {
		//Сообщаем странице списка о сохранении, чтобы она обновила грид
		var notify = function () {
			var topBX = window.top.BX;
			if (topBX && topBX.SidePanel)
			{
				topBX.SidePanel.Instance.postMessage(window, 'exam31.ticket:onElementSave', {});
			}
		};
		BX.addCustomEvent(window, 'onEntityCreate', notify);
		BX.addCustomEvent(window, 'onEntityUpdate', notify);
	});
</script>
