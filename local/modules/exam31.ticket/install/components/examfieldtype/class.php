<?php
defined('B_PROLOG_INCLUDED') || die;

use Exam31\Ticket\ExamFieldType;
use Exam31\Ticket\SomeElementTable;
use Bitrix\Main\Component\BaseUfComponent;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

Loc::loadMessages(__FILE__);

class SomeElementFieldComponent extends BaseUfComponent
{

	public function __construct($component = null)
	{
		Loader::requireModule('exam31.ticket');
		parent::__construct($component);
	}

	protected static function getUserTypeId(): string
	{
		return ExamFieldType::USER_TYPE_ID;
	}

	public function getSliderOptions(): array
	{
		//Те же параметры, что и у слайдера информации в разделе /exam31/
		return [
			'width' => 700,
			'cacheable' => false,
			'label' => [
				'text' => Loc::getMessage('EXAM31_TICKET_FIELDTYPE_SLIDER_LABEL'),
				'color' => '#FFFFFF',
				'bgColor' => '#7BD500',
				'opacity' => 90,
			],
		];
	}

	public function prepareValue($value)
	{
		$preparedValue = [
			'VALUE' => (int) $value,
			'LINK' => '',
		];

		$element = null;
		if ($preparedValue['VALUE'] > 0)
		{
			$element = SomeElementTable::getRow([
				'select' => ['ID', 'TITLE'],
				'filter' => ['=ID' => $preparedValue['VALUE']],
			]);
		}

		$settings = $this->arResult['userField']['SETTINGS'] ?? [];
		$formatValueTemplate = HtmlFilter::encode((string) ($settings['FORMAT'] ?? '#ID#'));

		$preparedValue['FORMATTED_VALUE'] = str_replace(
			['#ID#', '#TITLE#'],
			[$preparedValue['VALUE'], $element ? HtmlFilter::encode($element['TITLE']) : ''],
			$formatValueTemplate
		);

		if ($element)
		{
			$linkTemplate = trim((string) ($settings['LINK_TEMPLATE'] ?? ''));
			if ($linkTemplate !== '')
			{
				$preparedValue['LINK'] = str_replace('#ID#', $preparedValue['VALUE'], $linkTemplate);
			}
		}
		else
		{
			$preparedValue['FORMATTED_VALUE'] .= ' ' . HtmlFilter::encode(Loc::getMessage('EXAM31_TICKET_FIELDTYPE_ELEMENT_NOT_FOUND'));
		}

		return $preparedValue;

	}
}
