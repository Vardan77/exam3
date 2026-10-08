<?php

defined('B_PROLOG_INCLUDED') || die;

$values = [
	'FORMAT' => '',
	'LINK_TEMPLATE' => '',
];
if (
	isset($arResult['additionalParameters']['bVarsFromForm'])
	&& $arResult['additionalParameters']['bVarsFromForm'])
{
	$values['FORMAT'] = $GLOBALS[$arResult['additionalParameters']['NAME']]['FORMAT'] ?? '';
	$values['LINK_TEMPLATE'] = $GLOBALS[$arResult['additionalParameters']['NAME']]['LINK_TEMPLATE'] ?? '';
}
elseif (isset($arResult['userField']) && $arResult['userField'])
{
	$values['FORMAT'] = $arResult['userField']['SETTINGS']['FORMAT'] ?? '';
	$values['LINK_TEMPLATE'] = $arResult['userField']['SETTINGS']['LINK_TEMPLATE'] ?? '';
}

$arResult['VALUES'] = $values;
