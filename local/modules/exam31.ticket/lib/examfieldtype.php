<?php
namespace Exam31\Ticket;

use Bitrix\Main\UserField\Types\BaseType;
use Bitrix\Main\Localization\Loc;

class ExamFieldType extends BaseType
{
	public const USER_TYPE_ID = 'examfieldtype';
	public const RENDER_COMPONENT = 'exam31.ticket:examfieldtype';

	protected static function getDescription(): array
	{
		return [
			'DESCRIPTION' => Loc::getMessage('EXAM31_TICKET_FIELDTYPE_UF_DESCRIPTION'),
			'BASE_TYPE' => \CUserTypeManager::BASE_TYPE_INT,
		];
	}

	public static function getDbColumnType(): string
	{
		return 'int';
	}

	public static function prepareSettings(array $userField): array
	{
		$format = trim((string) ($userField['SETTINGS']['FORMAT'] ?? ''));
		$linkTemplate = trim((string) ($userField['SETTINGS']['LINK_TEMPLATE'] ?? ''));

		return [
			'FORMAT' => $format !== '' ? $format : Loc::getMessage('EXAM31_TICKET_FIELDTYPE_UF_DEFAULT_TEMPLATE_VALUE'),
			'LINK_TEMPLATE' => $linkTemplate,
		];
	}
}
