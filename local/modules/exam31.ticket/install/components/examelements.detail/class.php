<?php
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\Text\HtmlFilter;

use Exam31\Ticket\SomeElementTable;

class ExamElementsDetailComponent extends CBitrixComponent implements Controllerable, Errorable
{
	use ErrorableImplementation;
	private ?int $elementId = null;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	function onPrepareComponentParams($arParams)
	{
		if (!Loader::includeModule('exam31.ticket'))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('EXAM31_TICKET_MODULE_NOT_INSTALLED'))
			);
			return $arParams;
		}

		if (isset($arParams['ELEMENT_ID']))
		{
			$this->elementId = (int) $arParams['ELEMENT_ID'] ?: null;
		}

		return $arParams;
	}

	private function displayErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error->getMessage());
		}
	}

	function executeComponent(): void
	{
		global $APPLICATION;

		if ($this->hasErrors())
		{
			$this->displayErrors();
			return;
		}

		$entityData = $this->getEntityData();

		//flat - данные для прямого вывода в шаблоне, приводим к безопасному виду
		$this->arResult['ELEMENT'] = array_map(
			static fn($value) => HtmlFilter::encode((string) $value),
			$entityData
		);

		if ($this->elementId && empty($entityData))
		{
			$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE', ['#ID#' => $this->elementId]));
			ShowError(Loc::getMessage('EXAM31_ELEMENT_DETAIL_NOT_FOUND'));
			return;
		}

		//form - сюда отдаем исходные значения: ui.form сам экранирует их при выводе,
		//а в поля редактирования должны попасть данные без html-сущностей
		$this->arResult['form'] = $this->PrepareForm($entityData);
		$this->arResult['LIST_PAGE_URL'] = $this->arParams['LIST_PAGE_URL'];
		$this->arResult['DETAIL_PAGE_URL'] = $this->arParams['DETAIL_PAGE_URL'];

		$this->includeComponentTemplate();

		$APPLICATION->SetTitle($this->getTitle());
	}

	protected function getTitle(): string
	{
		return $this->elementId
			? Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE', ['#ID#' => $this->elementId])
			: Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE_NEW');
	}

	protected function PrepareForm($element): array
	{
		return [
			'MODULE_ID' => null,
			'CONFIG_ID' => null,
			'GUID' => 'GUIDSomeElement',
			'ENTITY_TYPE_NAME' => 'SomeElement',

			'ENTITY_CONFIG_EDITABLE' => true,
			'READ_ONLY' => false,
			'ENABLE_CONFIG_CONTROL' => false,

			'ENTITY_ID' => $this->elementId,

			'ENTITY_FIELDS' => $this->getEntityFields(),
			'ENTITY_CONFIG' => $this->getEntityConfig(),
			'ENTITY_DATA' => $element,
			'ENTITY_CONTROLLERS' => [],

			'COMPONENT_AJAX_DATA' => [
				'COMPONENT_NAME' => $this->getName(),
				'SIGNED_PARAMETERS' => $this->getSignedParameters()
			],
		];
	}
	protected function getEntityConfig(): array
	{
		return [
			[
				'type' => 'column',
				'name' => 'default_column',
				'elements' => [
					[
						'name' => 'main',
						'title' => $this->getTitle(),

						'type' => 'section',
						'elements' => [
							['name' => 'ID'],
							['name' => 'DATE_MODIFY'],
							['name' => 'ACTIVE'],
							['name' => 'TITLE'],
							['name' => 'TEXT'],
						]
					],
				]
			]
		];
	}

	protected function getEntityFields(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();

		return [
			[
				'name' => 'ID',
				'title' => $fieldsLabel['ID'] ?? 'ID',
				'editable' => false,
				'type' => 'text',
			],
			[
				'name' => 'DATE_MODIFY',
				'title' => $fieldsLabel['DATE_MODIFY'] ?? 'DATE_MODIFY',
				'editable' => false,
				'type' => 'datetime',
			],
			[
				'name' => 'ACTIVE',
				'title' => $fieldsLabel['ACTIVE'] ?? 'ACTIVE',
				'editable' => true,
				'type' => 'boolean',
			],
			[
				'name' => 'TITLE',
				'title' => $fieldsLabel['TITLE'] ?? 'TITLE',
				'editable' => true,
				'required' => true,
				'type' => 'text',
			],
			[
				'name' => 'TEXT',
				'title' => $fieldsLabel['TEXT'] ?? 'TEXT',
				'editable' => true,
				'type' => 'textarea',
			],
		];
	}

	protected function getEntityData(): array
	{
		if (!$this->elementId)
		{
			//Новый элемент по умолчанию активен
			return ['ACTIVE' => 'Y'];
		}

		$element = SomeElementTable::getRow([
			'select' => ['ID', 'DATE_MODIFY', 'ACTIVE', 'TITLE', 'TEXT'],
			'filter' => ['=ID' => $this->elementId],
		]);
		if (!$element)
		{
			return [];
		}

		return [
			'ID' => (int) $element['ID'],
			'DATE_MODIFY' => $element['DATE_MODIFY'] instanceof DateTime
				? $element['DATE_MODIFY']->toString()
				: '',
			'TITLE' => (string) $element['TITLE'],
			'TEXT' => (string) $element['TEXT'],
			'ACTIVE' => $element['ACTIVE'] ? 'Y' : 'N',
		];
	}

	//Ajax
	public function saveAction(array $data): AjaxJson
	{
		try
		{
			if ($this->hasErrors())
			{
				return AjaxJson::createError($this->errorCollection);
			}

			$fields = ['DATE_MODIFY' => new DateTime()];
			if (array_key_exists('TITLE', $data))
			{
				$fields['TITLE'] = trim((string) $data['TITLE']);
			}
			if (array_key_exists('TEXT', $data))
			{
				$fields['TEXT'] = (string) $data['TEXT'];
			}
			if (array_key_exists('ACTIVE', $data))
			{
				$fields['ACTIVE'] = $data['ACTIVE'] === 'Y';
			}

			$isNew = !$this->elementId;
			if ($isNew)
			{
				$fields['ACTIVE'] = $fields['ACTIVE'] ?? true;
				$result = SomeElementTable::add($fields);
			}
			else
			{
				if (!SomeElementTable::getByPrimary($this->elementId, ['select' => ['ID']])->fetch())
				{
					throw new SystemException(Loc::getMessage('EXAM31_ELEMENT_DETAIL_NOT_FOUND'));
				}
				$result = SomeElementTable::update($this->elementId, $fields);
			}

			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors());
				return AjaxJson::createError($this->errorCollection);
			}

			$this->elementId = (int) $result->getId();

			$response = [
				'ENTITY_ID' => $this->elementId,
				'ENTITY_DATA' => $this->getEntityData(),
			];
			if ($isNew)
			{
				//REDIRECT_URL необходим для корректной работы формы в слайдере
				$response['REDIRECT_URL'] = $this->getDetailPageUrl($this->elementId);
			}

			return AjaxJson::createSuccess($response);
		}
		catch (SystemException $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
			return AjaxJson::createError($this->errorCollection);
		}
	}

	public function configureActions(): array
	{
		return [];
	}
	protected function listKeysSignedParameters(): array
	{
		return ['ELEMENT_ID', 'DETAIL_PAGE_URL'];
	}

	protected function getDetailPageUrl(int $id): string
	{
		return str_replace('#ELEMENT_ID#', $id, $this->arParams['DETAIL_PAGE_URL']);
	}
}
