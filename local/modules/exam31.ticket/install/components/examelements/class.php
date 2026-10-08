<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class SomeElementsComponent extends CBitrixComponent
{
	const DEFAULT_SEF_FOLDER = '/exam31/';
	const DEFAULT_PAGE = 'list';
	const DEFAULT_URL_TEMPLATES = [
		'list' => '',
		'detail' => 'detail/#ELEMENT_ID#/',
		'info' => 'info/#ELEMENT_ID#/',
	];

	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public function onPrepareComponentParams($params)
	{
		$params['SEF_FOLDER'] = $params['SEF_FOLDER'] ?? static::DEFAULT_SEF_FOLDER;
		$params['DEFAULT_PAGE'] = $params['DEFAULT_PAGE'] ?? static::DEFAULT_PAGE;
		$params['SEF_URL_TEMPLATES'] = is_array($params['SEF_URL_TEMPLATES'] ?? null) ? $params['SEF_URL_TEMPLATES'] : [];

		return $params;
	}

	function executeComponent(): void
	{
		$variables = [];
		$engine = new CComponentEngine($this);

		$urlTemplates = $engine->MakeComponentUrlTemplates(
			static::DEFAULT_URL_TEMPLATES,
			$this->arParams['SEF_URL_TEMPLATES']
		);

		$componentPage = $engine->guessComponentPath(
			$this->arParams['SEF_FOLDER'],
			$urlTemplates,
			$variables,
		);

		$this->arResult = [
			'FOLDER' => $this->arParams['SEF_FOLDER'],
			'LIST_PAGE_URL' => $this->arParams['SEF_FOLDER'] . $urlTemplates['list'],
			'DETAIL_PAGE_URL' => $this->arParams['SEF_FOLDER'] . $urlTemplates['detail'],
			'INFO_PAGE_URL' => $this->arParams['SEF_FOLDER'] . $urlTemplates['info'],
			'VARIABLES' => $variables,
		];

		$this->includeComponentTemplate($componentPage ?: $this->arParams['DEFAULT_PAGE']);
	}
}
