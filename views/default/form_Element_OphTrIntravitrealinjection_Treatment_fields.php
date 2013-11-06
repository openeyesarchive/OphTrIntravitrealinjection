<?php /* DEPRECATED */ ?>
<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */
?>

<?php
	$antiseptic_drugs = OphTrIntravitrealinjection_AntiSepticDrug::model()->with('allergies')->findAll();
	$antiseptic_drugs_opts = array('empty' => '- Please select -', 'nowrapper' => true, 'options' => array());
	$antiseptic_allergic = false;
	foreach ($antiseptic_drugs as $drug) {
		$opts = array();
		foreach ($drug->allergies as $allergy) {
			if ($this->patient->hasAllergy($allergy)) {
				$opts['data-allergic'] = 1;
				if ($drug->id == $element->{$side . '_pre_antisept_drug_id'}) {
					$antiseptic_allergic = true;
				}
			}
		}
		$antiseptic_drugs_opts['options'][(string)$drug->id] = $opts;
	}
	$skin_drugs = OphTrIntravitrealinjection_SkinDrug::model()->with('allergies')->findAll();
	$skin_drugs_opts = array('empty' => '- Please select -', 'nowrapper' => true, 'options' => array());
	$skin_allergic = false;
	foreach ($skin_drugs as $drug) {
		$opts = array();
		foreach ($drug->allergies as $allergy) {
			if ($this->patient->hasAllergy($allergy)) {
				$opts['data-allergic'] = 1;
				if ($drug->id == $element->{$side . '_pre_skin_drug_id'}) {
					$skin_allergic = true;
				}
			}
		}
		$skin_drugs_opts['options'][(string)$drug->id] = $opts;
	}
?>

<div id="div_<?php echo get_class($element)?>_<?php echo $side ?>_pre_antisept_drug_id"
	 class="eventDetail">
	<div class="label">
		<?php echo $element->getAttributeLabel($side . '_pre_antisept_drug_id') ?>:
	</div>
	<div class="data">
		<div class="wrapper<?php if ($antiseptic_allergic) { echo ' allergyWarning'; }?>">
			<?php
				echo $form->dropDownList($element, $side . '_pre_antisept_drug_id', CHtml::listData($antiseptic_drugs, 'id', 'name'), $antiseptic_drugs_opts);
			?>
		</div>
	</div>
</div>

<div id="div_<?php echo get_class($element)?>_<?php echo $side ?>_pre_skin_drug_id"
	 class="eventDetail">
	<div class="label">
		<?php echo $element->getAttributeLabel($side . '_pre_skin_drug_id') ?>:
	</div>
	<div class="data">
		<div class="wrapper<?php if ($skin_allergic) { echo ' allergyWarning'; }?>">
			<?php
			echo $form->dropDownList($element, $side . '_pre_skin_drug_id', CHtml::listData($skin_drugs, 'id', 'name'), $skin_drugs_opts);
			?>
		</div>
	</div>
</div>

<div id="div_<?php echo get_class($element)?>_<?php echo $side ?>_pre_ioplowering_required"
	class="eventDetail">
	<div class="label">
		<?php echo $element->getAttributeLabel($side . '_pre_ioplowering_required') ?>:
	</div>
	<div class="data">
		<?php
			echo $form->checkbox($element, $side . '_pre_ioplowering_required', array('nowrapper' => true));
		?>
	</div>
</div>

<?php
$show = $element->{ $side . '_pre_ioplowering_required'};
if (isset($_POST[get_class($element)])) {
	$show = $_POST[get_class($element)][$side . '_pre_ioplowering_required'];
}
?>


<?php
	$div_class = "eventDetail";
	if (!$show) {
		$div_class .= " hidden";
	}

	$html_options = array(
		'options' => array(),
		'empty' => '- Please select -',
		'div_id' =>  "div_" . get_class($element) ."_" . $side . "_pre_ioploweringdrugs",
		'label' => $element->getAttributeLabel($side . '_pre_ioploweringdrugs'),
		'div_class' => $div_class);
	$ioplowering_drugs = OphTrIntravitrealinjection_IOPLoweringDrug::model()->findAll(array('order'=>'display_order asc'));
	foreach ($ioplowering_drugs as $drug) {
		$html_options['options'][(string) $drug->id] = array('data-order' => $drug->display_order);
	}
	echo $form->multiSelectList($element, get_class($element) . '[' . $side . '_pre_ioploweringdrugs]', $side . '_pre_ioploweringdrugs', 'id', CHtml::listData($ioplowering_drugs,'id','name'), array(), $html_options);

	$drugs = $element->getTreatmentDrugs($side);

	$html_options = array(
		'empty' => '- Please select -',
		'options' => array(),
	);
	// get the previous injection counts for each of the drug options for this eye
	$drug_history = array();

	foreach ($drugs as $drug) {
		$previous = $injection_api->previousInjections($this->patient, $episode, $side, $drug);
		$count = 0;
		if (sizeof($previous)) {
			$count = $previous[0][$side . '_number'];
		}
		$drug_history[$drug->id] = array_reverse($previous);

	 	$html_options['options'][$drug->id] = array(
			'data-previous' => $count,
		);

		// if this is an edit, we want to know what the original count was so that we don't replace it
		if ($element->{$side . '_drug_id'} && $element->{$side . '_drug_id'} == $drug->id) {
			$html_options['options'][$drug->id]['data-original-count'] = $element->{$side . '_number'};
		}
	}

	echo $form->dropDownList($element, $side . '_drug_id', CHtml::listData($drugs,'id','name'),$html_options);

	$selected_drug = null;
	if (@$_POST['Element_OphTrIntravitrealinjection_Treatment']) {
		$selected_drug = $_POST['Element_OphTrIntravitrealinjection_Treatment'][$side . '_drug_id'];
	} else {
		$selected_drug = $element->{$side . '_drug_id'};
	}

?>

<div id="div_<?php echo get_class($element);?>_<?php echo $side?>_number" class="eventDetail">
	<div class="label">
		<?php echo $element->getAttributeLabel($side . '_number'); ?>
	</div>
	<div class="data">
		<?php echo $form->textField($element, $side . '_number', array('size' => '10', 'nowrapper' => true))?>
		<span id="<?php echo $side; ?>_number_history_icon" class="number-history-icon<?php if (!$selected_drug) { echo ' hidden'; } ?>">
			<img src="<?php echo $this->assetPath ?>/img/icon_info.png" height="20" />
		</span>
		<div class="quicklook number-history" style="display: none;">
			<?php
			foreach ($drugs as $drug) {
				echo '<div class="number-history-item';
				if ($drug->id != $selected_drug) { echo ' hidden';}
				echo '" id="div_' . get_class($element) . '_' . $side . '_history_' . $drug->id . '">';
				if (count($drug_history[$drug->id])) {
					echo '<b>Previous ' . $drug->name . ' treatments</b><br />';
					echo '<dl style="margin-top: 0px; margin-bottom: 2px;">';
					foreach ($drug_history[$drug->id] as $previous) {
						echo '<dt>' . Helper::convertDate2NHS($previous['date']) . ' (' . $previous[$side . '_number'] . ')</dt>';
					}
					echo '</dl>';
				}
				else {
					echo 'No previous ' . $drug->name . ' treatments';
				}
				echo '</div>';
			}?>
		</div>
	</div>
</div>

<?php echo $form->textField($element, $side . '_batch_number', array('size' => '32'))?>
<?php
if (!$element->getIsNewRecord()) {
	$expiry_date_params = array('minDate' => Helper::convertDate2NHS($element->created_date) );
} else {
	$expiry_date_params = array('minDate' => 'yesterday');
}
?>

<?php echo $form->datePicker($element, $side . '_batch_expiry_date', $expiry_date_params, array('style'=>'width: 110px;'))?>

<?php echo $form->dropDownList($element, $side . '_injection_given_by_id', CHtml::listData(OphTrIntravitrealinjection_InjectionUser::model()->getUsers(),'id','ReversedFullName'),array('empty'=>'- Please select -'))?>

<div id="div_<?php echo get_class($element)?>_<?php echo $side ?>_injection_time"
	class="eventDetail">
	<div class="label">
		<?php echo $element->getAttributeLabel($side . '_injection_time') ?>:
	</div>
	<div class="data">
		<?php
			if ($element->{$side . '_injection_time'} != null) {
				$val = date('H:i',strtotime($element->{$side . '_injection_time'}));
			} else {
				$val = date('H:i');
			}

			if (isset($_POST[get_class($element)])) {
				$val = $_POST[get_class($element)][$side . '_injection_time'];
			}
			echo CHtml::textField(get_class($element) . "[".$side."_injection_time]", $val, array('size' => 6));
		?>
	</div>
</div>

<div id="div_<?php echo get_class($element)?>_<?php echo $side ?>_post_ioplowering_required"
	class="eventDetail">
	<div class="label">
		<?php echo $element->getAttributeLabel($side . '_post_ioplowering_required') ?>:
	</div>
	<div class="data">
		<?php
			echo $form->checkbox($element, $side . '_post_ioplowering_required', array('nowrapper' => true));
		?>
	</div>
</div>

<?php
	$div_class = "eventDetail";
	$show = $element->{ $side . '_post_ioplowering_required'};

	if (isset($_POST[get_class($element)])) {
		$show = $_POST[get_class($element)][$side . '_post_ioplowering_required'];
	}

	if (!$show) {
		$div_class .= " hidden";
	}

	$html_options = array(
		'options' => array(),
		'empty' => '- Please select -',
		'div_id' =>  "div_" . get_class($element) ."_" . $side . "_post_ioploweringdrugs",
		'label' => $element->getAttributeLabel($side . '_post_ioploweringdrugs'),
		'div_class' => $div_class);
	$ioplowering_drugs = OphTrIntravitrealinjection_IOPLoweringDrug::model()->findAll(array('order'=>'display_order asc'));
	foreach ($ioplowering_drugs as $drug) {
		$html_options['options'][(string) $drug->id] = array('data-order' => $drug->display_order);
	}
	echo $form->multiSelectList($element, get_class($element) . '[' . $side . '_post_ioploweringdrugs]', $side . '_post_ioploweringdrugs', 'id', CHtml::listData($ioplowering_drugs,'id','name'), array(), $html_options);
?>
