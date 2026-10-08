<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;

use Exam31\Ticket\SomeElementTable;
use Exam31\Ticket\SomeElementInfoTable;

class ExamElementsListComponent extends CBitrixComponent implements Errorable
{
	use ErrorableImplementation;
	protected const DEFAULT_PAGE_SIZE = 20;
	protected const GRID_ID = 'EXAM31_GRID_ELEMENT';
	protected const FILTER_ID = 'EXAM31_GRID_ELEMENT_FILTER';
	protected const NAV_ID = 'exam31_nav';
	protected const SORTABLE_FIELDS = ['ID', 'DATE_MODIFY', 'ACTIVE', 'TITLE'];

	protected ?PageNavigation $navigation = null;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function onPrepareComponentParams($arParams): array
	{
		if (!Loader::includeModule('exam31.ticket'))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('EXAM31_TICKET_MODULE_NOT_INSTALLED'))
			);
			return $arParams;
		}

		$arParams['ELEMENT_COUNT'] = (int) ($arParams['ELEMENT_COUNT'] ?? 0);
		if ($arParams['ELEMENT_COUNT'] <= 0)
		{
			$arParams['ELEMENT_COUNT'] = static::DEFAULT_PAGE_SIZE;
		}
		$arParams['DETAIL_PAGE_URL'] = (string) ($arParams['DETAIL_PAGE_URL'] ?? '');
		$arParams['INFO_PAGE_URL'] = (string) ($arParams['INFO_PAGE_URL'] ?? '');

		return $arParams;
	}

	private function displayErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error->getMessage());
		}
	}

	public function executeComponent(): void
	{
		if ($this->hasErrors())
		{
			$this->displayErrors();
			return;
		}

		$this->arResult['ITEMS'] = $this->getSomeElementList();
		$this->arResult['grid'] = $this->prepareGrid($this->arResult['ITEMS']);
		$this->arResult['filter'] = $this->prepareFilter();
		$this->arResult['GRID_ID'] = static::GRID_ID;
		$this->arResult['ADD_PAGE_URL'] = $this->getDetailPageUrl(0);

		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENTS_LIST_PAGE_TITLE'));
	}

	protected function getSomeElementList(): array
	{
		$gridOptions = new GridOptions(static::GRID_ID);
		$filter = $this->getListFilter();

		$navParams = $gridOptions->GetNavParams(['nPageSize' => $this->arParams['ELEMENT_COUNT']]);
		$this->navigation = new PageNavigation(static::NAV_ID);
		$this->navigation
			->allowAllRecords(false)
			->setPageSize((int) $navParams['nPageSize'])
			->initFromUri();
		$this->navigation->setRecordCount(SomeElementTable::getCount($filter));

		$items = SomeElementTable::getList([
			'select' => ['ID', 'DATE_MODIFY', 'ACTIVE', 'TITLE', 'TEXT'],
			'filter' => $filter,
			'order' => $this->getListOrder($gridOptions),
			'limit' => $this->navigation->getLimit(),
			'offset' => $this->navigation->getOffset(),
		])->fetchAll();

		$infoCount = $this->getInfoCount(array_column($items, 'ID'));

		$preparedItems = [];
		foreach ($items as $item)
		{
			$item['DETAIL_URL'] = $this->getDetailPageUrl($item['ID']);
			$item['INFO_URL'] = $this->getInfoPageUrl($item['ID']);
			$item['INFO_COUNT'] = $infoCount[$item['ID']] ?? 0;
			$item['DATE_MODIFY'] = $item['DATE_MODIFY'] instanceof DateTime
				? $item['DATE_MODIFY']->toString()
				: null;

			$preparedItems[] = $item;
		}
		return $preparedItems;
	}

	protected function getListFilter(): array
	{
		$filterOptions = new FilterOptions(static::FILTER_ID);
		$filterData = $filterOptions->getFilter($this->getFilterFields());

		//Фильтруем только по TITLE, строка быстрого поиска тоже ищет по TITLE
		$title = trim((string) ($filterData['TITLE'] ?? ''));
		if ($title === '')
		{
			$title = trim((string) ($filterData['FIND'] ?? ''));
		}

		return $title !== '' ? ['%TITLE' => $title] : [];
	}

	protected function getListOrder(GridOptions $gridOptions): array
	{
		$sorting = $gridOptions->getSorting(['sort' => ['ID' => 'ASC']]);

		$order = [];
		foreach ($sorting['sort'] as $field => $direction)
		{
			if (in_array($field, static::SORTABLE_FIELDS, true))
			{
				$order[$field] = mb_strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
			}
		}

		return $order ?: ['ID' => 'ASC'];
	}

	protected function getInfoCount(array $elementIds): array
	{
		if (empty($elementIds))
		{
			return [];
		}

		$result = [];
		$rows = SomeElementInfoTable::getList([
			'select' => ['ELEMENT_ID', 'CNT'],
			'filter' => ['@ELEMENT_ID' => $elementIds],
			'group' => ['ELEMENT_ID'],
			'runtime' => [
				new ExpressionField('CNT', 'COUNT(*)'),
			],
		]);
		while ($row = $rows->fetch())
		{
			$result[$row['ELEMENT_ID']] = (int) $row['CNT'];
		}

		return $result;
	}

	protected function getFilterFields(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();
		return [
			['id' => 'TITLE', 'name' => $fieldsLabel['TITLE'] ?? 'TITLE', 'type' => 'string', 'default' => true],
		];
	}

	protected function prepareFilter(): array
	{
		return [
			'FILTER_ID' => static::FILTER_ID,
			'GRID_ID' => static::GRID_ID,
			'FILTER' => $this->getFilterFields(),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
		];
	}

	protected function prepareGrid($items): array
	{
		return [
			'GRID_ID' => static::GRID_ID,
			'COLUMNS' => $this->getGridColums(),
			'ROWS' => $this->getGridRows($items),
			'NAV_OBJECT' => $this->navigation,
			'TOTAL_ROWS_COUNT' => $this->navigation->getRecordCount(),
			'PAGE_SIZES' => [
				['NAME' => '5', 'VALUE' => '5'],
				['NAME' => '10', 'VALUE' => '10'],
				['NAME' => '20', 'VALUE' => '20'],
				['NAME' => '50', 'VALUE' => '50'],
			],
			'SHOW_PAGINATION' => true,
			'SHOW_NAVIGATION_PANEL' => true,
			'SHOW_PAGESIZE' => true,
			'SHOW_TOTAL_COUNTER' => true,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'SHOW_ROW_ACTIONS_MENU' => true,
			'ALLOW_SORT' => true,
			'AJAX_MODE' => 'Y',
			'AJAX_ID' => CAjax::GetComponentID('bitrix:main.ui.grid', '', ''),
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
		];
	}

	protected function getGridColums(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();
		return [
			['id' => 'ACTIVE', 'default' => true, 'sort' => 'ACTIVE', 'name' => $fieldsLabel['ACTIVE'] ?? 'ACTIVE'],
			['id' => 'ID', 'default' => true, 'sort' => 'ID', 'name' => $fieldsLabel['ID'] ?? 'ID'],
			['id' => 'DATE_MODIFY', 'default' => true, 'sort' => 'DATE_MODIFY', 'name' => $fieldsLabel['DATE_MODIFY'] ?? 'DATE_MODIFY'],
			['id' => 'TITLE', 'default' => true, 'sort' => 'TITLE', 'name' => $fieldsLabel['TITLE'] ?? 'TITLE'],
			['id' => 'TEXT', 'default' => true, 'name' => $fieldsLabel['TEXT'] ?? 'TEXT'],
			['id' => 'DETAIL', 'default' => true, 'name' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME')],
			['id' => 'INFO', 'default' => true, 'name' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_INFO_NAME')],
		];
	}
	protected function getGridRows(array $items): array
	{
		if (empty($items))
		{
			return [];
		}

		$rows = [];
		foreach ($items as $key => $item)
		{
			$rows[$key] = [
				'id' => $item["ID"],
				'columns' => [
					'ID' => (int) $item["ID"],
					'DATE_MODIFY' => HtmlFilter::encode((string) $item["DATE_MODIFY"]),
					'TITLE' => HtmlFilter::encode((string) $item["TITLE"]),
					'TEXT' => HtmlFilter::encode((string) $item["TEXT"]),
					'ACTIVE' => $item["ACTIVE"]
						? Loc::getMessage('EXAM31_ELEMENTS_LIST_ACTIVE_Y')
						: Loc::getMessage('EXAM31_ELEMENTS_LIST_ACTIVE_N'),
					'DETAIL' => $this->getHTMLLink(
						$item["DETAIL_URL"],
						Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME')
					),
					'INFO' => $this->getHTMLLink(
						$item["INFO_URL"],
						Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_INFO_VALUE', ['#COUNT#' => $item["INFO_COUNT"]])
					),
				],
				'actions' => [
					[
						'text' => Loc::getMessage('EXAM31_ELEMENTS_LIST_ACTION_DETAIL'),
						'default' => true,
						'onclick' => $this->getOpenSliderJs($item["DETAIL_URL"]),
					],
					[
						'text' => Loc::getMessage('EXAM31_ELEMENTS_LIST_ACTION_INFO'),
						'onclick' => $this->getOpenSliderJs($item["INFO_URL"]),
					],
				],
			];
		}
		return $rows;
	}

	protected function getDetailPageUrl(int $id): string
	{
		return str_replace('#ELEMENT_ID#', $id, $this->arParams['DETAIL_PAGE_URL']);
	}
	protected function getInfoPageUrl(int $id): string
	{
		return str_replace('#ELEMENT_ID#', $id, $this->arParams['INFO_PAGE_URL']);
	}
	protected function getHTMLLink(string $url, string $text): string
	{
		return "<a href=\"" . HtmlFilter::encode($url) . "\">" . HtmlFilter::encode($text) . "</a>";
	}
	protected function getOpenSliderJs(string $url): string
	{
		//Открываем с теми же параметрами, что заданы в правилах bindAnchors
		$url = CUtil::JSEscape($url);
		return "(function(){var r=BX.SidePanel.Instance.getUrlRule('{$url}');BX.SidePanel.Instance.open('{$url}',(r&&r.options)||{});})()";
	}
}
