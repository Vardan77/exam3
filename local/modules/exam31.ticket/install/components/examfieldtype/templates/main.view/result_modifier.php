<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\UI\Extension;

Extension::load('sidepanel');

$component = $this->getComponent();
$values = (array) ($arResult['value'] ?? []);

$arResult['SLIDER_OPTIONS'] = $component->getSliderOptions();
$arResult['PREPARED_VALUES'] = [];
foreach($values as $key => $val)
{
	if ((string) $val === '')
	{
		continue;
	}
	$arResult['PREPARED_VALUES'][$key] = $component->prepareValue($val);
}
