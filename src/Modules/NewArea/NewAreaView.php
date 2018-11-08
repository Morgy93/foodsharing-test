<?php

namespace Foodsharing\Modules\NewArea;

use Foodsharing\Modules\Core\View;

class NewAreaView extends View
{
	public function listWantNews($foodsaver)
	{
		$rows = array();
		foreach ($foodsaver as $d) {
			$beziks = '';
			if (is_array($d['bezirke'])) {
				foreach ($d['bezirke'] as $bz) {
					$beziks .= ', ' . $bz['name'];
				}
				$beziks = substr($beziks, 2);
			}

			$rows[] = array(
				array('cnt' => '<input class="wantnewcheck" type="checkbox" name="ordersaver[]" value="' . $d['id'] . '" />'),
				array('cnt' => '<span class="photo"><a href="/profile/' . (int)$d['id'] . '"><img src="' . $this->func->img($d['photo']) . '" /></a></span>'),
				array('cnt' => '<a class="linkrow ui-corner-all" href="/profile/' . (int)$d['id'] . '">' . $d['name'] . '</a>'),
				array('cnt' => $d['anschrift'] . ', ' . $d['plz'] . ' ' . $d['stadt']),
				array('cnt' => $beziks),
				array('cnt' => $d['new_bezirk'])
			);
		}
		$out = $this->v_utils->v_tablesorter(array(
			array('name' => '', 'sort' => false),
			array('name' => '', 'sort' => false),
			array('name' => 'Name'),
			array('name' => 'Adresse'),
			array('name' => 'Bezirke'),
			array('name' => 'Gewünschter Bezirk')
		), $rows);

		return $this->v_utils->v_field($out, 'Foodsaver mit neuem Bezirkswünschen');
	}

	public function options()
	{
		return $this->v_utils->v_menu(array(
			array('name' => 'Markierte Anfragen löschen', 'click' => 'deleteMarked();return false;')
		), 'Optionen');
	}

	public function orderToBezirk()
	{
		$out = '';

		global $g_data;
		$g_data['order_msg'] = "{ANREDE} {NAME},\n\n";
		$g_data['subject'] = 'Dein gewünschter Bezirk wurde angelegt!';
		$out .= $this->v_utils->v_bezirkChooser('order_bezirk');
		$out .= $this->v_utils->v_form_textarea('order_msg');
		$out .= $this->v_utils->v_form_text('subject');
		$out .= '<a class="button" id="orderFs">Speichern & Senden</a>';

		return $this->v_utils->v_field($out, 'Markierte Foodsaver zu Bezirk', array('class' => 'ui-padding'));
	}
}
