<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
	'PARAMETERS' => [
		'VARIABLE_ALIASES' => [
			'ELEMENT_ID' => ['NAME' => GetMessage('EXAM31_ELEMENTS_VARIABLE_ALIASES_ID_NAME')],
		],
		'SEF_MODE' => [
			'list' => [
				'NAME' => Loc::getMessage('EXAM31_ELEMENTS_SEF_MODE_LIST_PARAMETER_NAME'),
				'DEFAULT' => '',
				'VARIABLES' => [],
			],
			'detail' => [
				'NAME' => Loc::getMessage('EXAM31_ELEMENTS_SEF_MODE_DETAIL_PARAMETER_NAME'),
				'DEFAULT' => 'detail/#ELEMENT_ID#/',
				'VARIABLES' => ['ELEMENT_ID'],
			],
			'info' => [
				'NAME' => Loc::getMessage('EXAM31_ELEMENTS_SEF_MODE_INFO_PARAMETER_NAME'),
				'DEFAULT' => 'info/#ELEMENT_ID#/',
				'VARIABLES' => ['ELEMENT_ID'],
			],
		],
	],
];
