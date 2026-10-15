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

		if ($this->elementId && empty($entityData)) {
			$this->errorCollection->setError(
				new Error(Loc::getMessage('EXAM31_ELEMENT_DETAIL_NOT_FOUND', ['#ID#' => $this->elementId]))
			);
			$this->displayErrors();
			$APPLICATION->SetTitle($this->getTitle());
			return;
		}

		$this->arResult['form'] = $this->PrepareForm($entityData);

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
		$fields = SomeElementTable::getMap();
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();
		$res = [];

		foreach($fields as $field) {
			$fieldName = $field->getTitle();

			if(!$this->elementId && ($fieldName == 'ID' || $fieldName == 'DATE_MODIFY')) {
				continue;
			}

			$fieldConfig = [
				'name' => $fieldName,
				'title' => $fieldsLabel[$fieldName] ?? $fieldName,
				'editable' => true,
				'type' => $this->getEntityType($field),
			];

			if($this->elementId && ($fieldName == 'ID' || $fieldName == 'DATE_MODIFY')) {
				$fieldConfig['editable'] = false;
			}

			$res[] = $fieldConfig; 

		}

		return $res;
	}

	protected function getEntityType($field): string
	{
		$type = $field->getDataType();

		return match ($type) {
			'integer' => 'number',
			'text' => 'textarea',
			'boolean' => 'boolean',
			'datetime' => 'datetime',
			default => 'text',
		};
	}

	protected function getEntityData(): array
	{
		if (!$this->elementId)
		{
			return [];
		}

		$element = SomeElementTable::query()
			->addSelect('*')
			->where('ID', $this->elementId)
			->fetch();

		if(!$element) {
			return [];
		}

		$element['ACTIVE'] = $element['ACTIVE'] ? 'Y' : 'N';

		return $element;
	}

	//Ajax
	public function saveAction(array $data): AjaxJson
	{
		try
		{
            $fields['DATE_MODIFY'] = new DateTime();
            if (isset($data['ACTIVE'])) {
                $fields['ACTIVE'] = $data['ACTIVE'] === 'Y';
            }

            foreach (['TITLE', 'TEXT'] as $name) {
                if (isset($data[$name])) {
                    $fields[$name] = (string) $data[$name];
                }
            }

			if ($this->elementId && empty($this->getEntityData()))
			{
				$this->errorCollection->setError(
					new Error(Loc::getMessage('EXAM31_ELEMENT_DETAIL_NOT_FOUND', ['#ID#' => $this->elementId]))
				);
				return AjaxJson::createError($this->errorCollection);
			}

			if(!$this->elementId) {
				$result = SomeElementTable::add($fields);
			} else {
				$result = SomeElementTable::update($this->elementId, $fields);
			}

			if (!$result->isSuccess()) {
                $this->errorCollection->add($result->getErrors());
                return AjaxJson::createError($this->errorCollection);
			}

			return AjaxJson::createSuccess([
				'ENTITY_ID' => $result->getId(),
				'ENTITY_DATA' => $this->getEntityData(),
				'REDIRECT_URL' => $this->getDetailPageUrl($result->getId()),
			]);
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