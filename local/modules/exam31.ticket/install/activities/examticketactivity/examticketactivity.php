<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Exam31\Ticket\SomeElementTable;

class CBPExamTicketActivity extends BaseActivity
{
	protected static $requiredModules = ['exam31.ticket'];

	public function __construct($name)
	{
		parent::__construct($name);

		$this->arProperties = [
			'ID' => 0,

			//return
			'ACTIVE' => null,
			'DATE_MODIFY' => null,
			'TITLE' => null,
			'TEXT' => null,
		];

		$this->SetPropertiesTypes([
			'ACTIVE' => ['Type' => FieldType::STRING],
			'DATE_MODIFY' => ['Type' => FieldType::STRING],
			'TITLE' => ['Type' => FieldType::STRING],
			'TEXT' => ['Type' => FieldType::STRING],
		]);

	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	protected function internalExecute(): ErrorCollection
	{
		$errors = parent::internalExecute();

		$elementId = (int) $this->preparedProperties["ID"];
		$element = $elementId > 0
			? SomeElementTable::getRow([
				'select' => ['ID', 'ACTIVE', 'DATE_MODIFY', 'TITLE', 'TEXT'],
				'filter' => ['=ID' => $elementId],
			])
			: null;

		if($element)
		{
			//Значения найдены, отдаем их в виде строк
			$this->setProperty('ID', (string) $element['ID']);
			$this->setProperty(
				'ACTIVE',
				Loc::getMessage($element['ACTIVE'] ? 'EXAM31_TICKET_ACTIVITY_ACTIVE_Y' : 'EXAM31_TICKET_ACTIVITY_ACTIVE_N')
			);
			$this->setProperty('DATE_MODIFY', $element['DATE_MODIFY'] ? $element['DATE_MODIFY']->toString() : '');
			$this->setProperty('TITLE', (string) $element['TITLE']);
			$this->setProperty('TEXT', (string) $element['TEXT']);

			//Пишем в журнал выполнения БП что данные получены
			$this->log(
				Loc::getMessage(
					'EXAM31_TICKET_ACTIVITY_LOG_TEXT_Y',
					[
						'#ID#' => $elementId,
					]
				)
			);
		}
		else
		{
			//Если нет данных, отдаем пустые значения
			$this->setProperty('ID', '');
			$this->setProperty('ACTIVE', '');
			$this->setProperty('DATE_MODIFY', '');
			$this->setProperty('TITLE', '');
			$this->setProperty('TEXT', '');

			//Пишем в журнал выполнения БП что данные не нашли
			$this->log(
				Loc::getMessage(
					'EXAM31_TICKET_ACTIVITY_LOG_TEXT_N',
					[
						'#ID#' => $elementId,
					]
				)
			);
		}

		return $errors;
	}

	public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
	{
		$map = [
			'ID' => [
				'Name' => Loc::getMessage('EXAM31_TICKET_ACTIVITY_FIELD_ID'),
				'FieldName' => 'ID',
				'Type' => FieldType::INT,
				'Required' => true,
				'Default' => '',
				'Options' => [],
			],
		];

		return $map;
	}
}