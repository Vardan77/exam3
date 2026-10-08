<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Exam31\Ticket\SomeElementInfoTable;

class ExamElementsInfoComponent extends CBitrixComponent
{
    function onPrepareComponentParams($arParams)
    {
        $arParams['ELEMENT_ID'] = (int) ($arParams['ELEMENT_ID'] ?? 0);

        return $arParams;
    }

    function executeComponent(): void
    {
        if (!Loader::includeModule('exam31.ticket'))
        {
            ShowError(Loc::getMessage('EXAM31_TICKET_MODULE_NOT_INSTALLED'));
            return;
        }

        Extension::load('ui.sidepanel-content');

        $this->arResult['ELEMENTS'] = $this->getInfoElements();
        $this->includeComponentTemplate();

        global $APPLICATION;
        $APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENT_INFO_TITLE', ['#ID#' => $this->arParams['ELEMENT_ID']]));
    }

    private function getInfoElements(): array
    {
        return SomeElementInfoTable::query()
            ->setSelect(['ID', 'TITLE', 'ELEMENT_ID'])
            ->where('ELEMENT_ID', $this->arParams['ELEMENT_ID'])
            ->setOrder(['ID' => 'ASC'])
            ->fetchAll();
    }
}
