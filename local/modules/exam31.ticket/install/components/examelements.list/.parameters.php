<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
	'PARAMETERS' => [
		'DETAIL_PAGE_URL' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('EXAM31_ELEMENTS_LIST_DETAIL_URL'),
			'TYPE' => 'STRING',
		],
		'INFO_PAGE_URL' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('EXAM31_ELEMENTS_LIST_INFO_URL'),
			'TYPE' => 'STRING',
		],
	],
];
