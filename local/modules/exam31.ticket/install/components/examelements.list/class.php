<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Text\HtmlFilter;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons;

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
	protected const PAGINATION_ID = 'EXAM31_GRID_ELEMENT_PAGINATION';

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

		$arParams['ELEMENT_COUNT'] = (int) $arParams['ELEMENT_COUNT'] ?? 0;
		if ($arParams['ELEMENT_COUNT'] <= 0) {
			$arParams['ELEMENT_COUNT'] = static::DEFAULT_PAGE_SIZE;
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

	public function executeComponent(): void
	{
		if ($this->hasErrors())
		{
			$this->displayErrors();
			return;
		}

        $this->arResult['PAGINATION'] = $this->preparePagination();
        $this->arResult['ITEMS'] = $this->getSomeElementList();
		$this->arResult['grid'] = $this->prepareGrid($this->arResult['ITEMS']);
		$this->arResult['filter'] = $this->prepareFilter();

		$this->prepareToolbar();
		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENTS_LIST_PAGE_TITLE'));
	}

	protected function getSomeElementList(): array
	{
		$filterOptopns = new Options(self::FILTER_ID);
		$titleFilter = $filterOptopns->getFilter()['TITLE'] ?? '';

		$query = SomeElementTable::query()
			->addSelect('*')
			->whereLike('TITLE', '%' . $titleFilter . '%');

		$totalCount = $query->queryCountTotal();

		$items = $query
            ->addOrder('ID')
			->setLimit($this->arResult['PAGINATION']->getLimit())
			->setOffset($this->arResult['PAGINATION']->getOffset())
			->fetchAll();

        $this->arResult['PAGINATION']->setRecordCount($totalCount);
        $infoCount = $this->getInfoCount(array_column($items, 'ID'));

		$preparedItems = [];
		foreach ($items as $item) {
			$active = $item['ACTIVE'] ? 'YES' : 'NO';
			$item['DETAIL_URL'] = $this->getDetailPageUrl($item['ID']);
			$item['INFO_URL'] = $this->getInfoPageUrl($item['ID']);
			$item['INFO_COUNT'] = $infoCount[$item['ID']] ?? 0;
			$item['ACTIVE'] = Loc::getMessage('EXAM31_ELEMENTS_LIST_ELEMENT_ACTIVE_' . $active);
			$item['DATE_MODIFY'] = $item['DATE_MODIFY'] instanceof DateTime
				? $item['DATE_MODIFY']->toString()
				: null;

			$preparedItems[] = $item;
		}
		return $preparedItems;
	}

	protected function getInfoCount(array $elementIds): array
	{
		if (empty($elementIds)) {
			return [];
		}

		$rows = SomeElementInfoTable::query()
			->registerRuntimeField('INFO_COUNT', new ExpressionField('INFO_COUNT', 'COUNT(%s)', 'ID'))
			->addSelect('ELEMENT_ID')
			->addSelect('INFO_COUNT')
			->whereIn('ELEMENT_ID', $elementIds)
			->addGroup('ELEMENT_ID')
			->fetchAll();

		return array_column($rows, 'INFO_COUNT', 'ELEMENT_ID');
	}

	protected function prepareGrid($items): array
	{
		return [
            'GRID_ID' => static::GRID_ID,
			'COLUMNS' => $this->getGridColums(),
			'ROWS' => $this->getGridRows($items),
			'TOTAL_ROWS_COUNT' => $this->arResult['PAGINATION']->getRecordCount(),
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
            'NAV_OBJECT' => $this->arResult['PAGINATION'],
		];
	}

	protected function getGridColums(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();
		return [
			['id' => 'ACTIVE', 'default' => true, 'name' => $fieldsLabel['ACTIVE'] ?? 'ACTIVE'],
			['id' => 'ID', 'default' => true, 'name' => $fieldsLabel['ID'] ?? 'ID'],
			['id' => 'DATE_MODIFY', 'default' => true, 'name' => $fieldsLabel['DATE_MODIFY'] ?? 'DATE_MODIFY'],
			['id' => 'TITLE', 'default' => true, 'name' => $fieldsLabel['TITLE'] ?? 'TITLE'],
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
					'ID' => $item["ID"],
					'DATE_MODIFY' => $item["DATE_MODIFY"],
					'TITLE' => HtmlFilter::encode($item["TITLE"]),
					'TEXT' => HtmlFilter::encode($item["TEXT"]),
					'ACTIVE' => $item["ACTIVE"],
					'DETAIL' => $this->getDetailHTMLLink($item["DETAIL_URL"]),
					'INFO' => $this->getInfoHTMLLink($item),
				],
				'actions' => [
					[
						'text' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME'),
						'onclick' => $this->getSliderJs($item['DETAIL_URL']),
					],
					[
						'text' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_INFO_NAME'),
						'onclick' => $this->getSliderJs($item['INFO_URL']),
					],
				]
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

	protected function getDetailHTMLLink(string $detail_url): string
	{
		return "<a href=\"" . $detail_url . "\">" . Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME') . "</a>";
	}

	protected function getInfoHTMLLink(array $item): string
	{
		return "<a href=\"" . $item['INFO_URL'] . "\">" . Loc::getMessage('EXAM31_ELEMENTS_LIST_INFO_URL_TITLE', ['#COUNT#' => $item['INFO_COUNT']]) . "</a>";
	}

	protected function preparePagination(): PageNavigation
	{

		$pageNavigation = new PageNavigation(static::PAGINATION_ID);
		$pageNavigation->setPageSize($this->arParams['ELEMENT_COUNT'])->initFromUri();

		return $pageNavigation;
	}

	protected function prepareFilter(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();

		return [
			'FILTER_ID' => self::FILTER_ID,
			'GRID_ID' => self::GRID_ID,
			'FILTER' => [
				[
					'id' => 'TITLE',
					'name' => $fieldsLabel['TITLE'] ?? 'TITLE',
				]
			],
		];
	}

	protected function prepareToolbar(): void
	{
		Toolbar::addFilter($this->arResult['filter']);
		Toolbar::addButton(new Buttons\Addbutton([
			'click' => new Buttons\JsCode($this->getSliderJs($this->getDetailPageUrl(0)))
		]));
	}

	protected function getSliderJs($url): string
	{
        return "BX.SidePanel.Instance.open('" . CUtil::JSEscape($url) . "')";
	}
}