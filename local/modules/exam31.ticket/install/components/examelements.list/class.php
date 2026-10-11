<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;

use Bitrix\UI\Buttons\AddButton;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Exam31\Ticket\SomeElementTable;
use Exam31\Ticket\SomeElementInfoTable;

class ExamElementsListComponent extends CBitrixComponent implements Errorable
{
	use ErrorableImplementation;
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
		$this->arResult['GRID_ID'] = static::GRID_ID;
		$this->arResult['ADD_PAGE_URL'] = $this->getDetailPageUrl(0);

		$this->prepareToolbar();
		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENTS_LIST_PAGE_TITLE'));
	}

	protected function getSomeElementList(): array
	{
        $filterOptions = new FilterOptions(self::FILTER_ID);
        $filter = $filterOptions->getFilter();
        //Строка поиска тоже ищет по TITLE
        $titleFilter = ($filter['TITLE'] ?? '') ?: ($filter['FIND'] ?? '');

        $query = SomeElementTable::query()
            ->addSelect('*')
            ->whereLike('TITLE', '%' . $titleFilter . '%');

		$totalCount = $query->queryCountTotal();

		$items = $query
			->setOrder(['ID' => 'ASC'])
			->setLimit($this->arResult['PAGINATION']->getLimit())
			->setOffset($this->arResult['PAGINATION']->getOffset())
			->fetchAll();

        $this->arResult['PAGINATION']->setRecordCount($totalCount);
        $infoCount = $this->getInfoCount();

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

	protected function getInfoCount(): array
	{
        $infoElements = SomeElementInfoTable::query()
            ->addSelect('*')
            ->fetchAll();

        $infoElements = array_column($infoElements, 'ELEMENT_ID');
        return array_count_values($infoElements);
	}

    protected function preparePagination(): PageNavigation
    {
        $pagination = new PageNavigation(static::PAGINATION_ID);
        $pagination->initFromUri();

        return $pagination;
    }

	protected function prepareFilter(): array
	{
        $fieldsLabel = SomeElementTable::getFieldsDisplayLabel();

		return [
			'FILTER_ID' => static::FILTER_ID,
			'GRID_ID' => static::GRID_ID,
			'FILTER' => [
                [
                    'id' => 'TITLE',
                    'name' => $fieldsLabel['TITLE'] ?? 'TITLE',
                ]
            ]
		];
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

    protected function prepareToolbar(): void
    {
        Toolbar::addFilter($this->arResult['filter']);
        Toolbar::addButton(new AddButton([
            'click' => new JsCode($this->getOpenSliderJs($this->arResult['ADD_PAGE_URL'])),
        ]));
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
						'onclick' => $this->getOpenSliderJs($item['DETAIL_URL']),
					],
					[
						'text' => Loc::getMessage('EXAM31_ELEMENTS_LIST_ACTION_INFO'),
                        'onclick' => $this->getOpenSliderJs($item['INFO_URL']),
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
		return "BX.SidePanel.Instance.open('" . CUtil::JSEscape($url) . "')";
	}
}
