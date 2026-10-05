<?php
namespace Exam31\Ticket;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

class SomeElementInfoTable extends Entity\DataManager
{
	static function getTableName(): string
	{
		return 'exam31_ticket_someelement_info';
	}
	static function getMap(): array
	{
		return array(
			(new Entity\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Entity\StringField('TITLE'))
				->configureRequired()
                ->configureSize(250),
			new Entity\IntegerField('ELEMENT_ID'),
            new Entity\ReferenceField(
                'ELEMENT',
                SomeElementTable::class,
                ['=this.ELEMENT_ID' => 'ref.ID']
            ),
		);
	}
}