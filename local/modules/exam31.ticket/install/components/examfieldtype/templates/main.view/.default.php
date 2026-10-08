<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Web\Json;

$sliderOptions = HtmlFilter::encode(Json::encode($arResult['SLIDER_OPTIONS']));
?>

<span class="fields string field-wrap">
	<?php
	foreach ($arResult['PREPARED_VALUES'] as $item)
	{ ?>
		<span class="fields string field-item">
			<?php
			if ($item['LINK'] !== '')
			{ ?>
				<a href="<?= HtmlFilter::encode($item['LINK']) ?>"
					onclick="BX.SidePanel.Instance.open(this.href, <?= $sliderOptions ?>); return false;"
				><?= $item['FORMATTED_VALUE'] ?></a>
				<?php
			}
			else
			{
				print $item['FORMATTED_VALUE'];
			}
			?>
		</span>
		<?php
	} ?>
</span>
