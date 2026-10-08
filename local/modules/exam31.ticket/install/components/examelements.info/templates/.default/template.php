<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;

/**
 * @var array $arParams
 * @var array $arResult
 */

Extension::load('ui.sidepanel-content');
?>

<div class="ui-slider-section">
    <div class="ui-slider-content-box">
        <div class="ui-slider-heading-2"><?= Loc::getMessage('EXAM31_ELEMENT_INFO_PAGE_TITLE') ?></div>
    </div>
    <?php if ($arResult['ELEMENTS']): ?>
        <?php foreach ($arResult['ELEMENTS'] as $element): ?>
            <div class="ui-slider-frame --no-hover">
                <div class="ui-slider-heading-3"><?= (int) $element['ID'] ?></div>
                <p class="ui-slider-paragraph-2"><?= HtmlFilter::encode($element['TITLE']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="ui-slider-paragraph"><?= Loc::getMessage('EXAM31_ELEMENT_INFO_PAGE_EMPTY') ?></p>
    <?php endif; ?>
</div>
