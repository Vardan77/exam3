<?php
namespace Exam31\Ticket;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

class ProtectedField
{
	public const ENTITY_ID = 'CRM_DEAL';
	public const FIELD_NAME = 'UF_CRM_PROTECTED_FIELD';

    public static function onBeforeCrmDealUpdate(array &$fields): bool
    {
        if ($fields['UF_CRM_PROTECTED_FIELD']) {
            if (!CurrentUser::get()->isAdmin()) {
                $fields['RESULT_MESSAGE'] = Loc::getMessage('EXAM31_TICKET_PROTECTED_FIELD_ERROR');
                return false;
            }
        }

        return true;
    }
}
