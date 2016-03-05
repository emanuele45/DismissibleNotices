<?php

/**
 * @name      Dismissible Notices
 * @copyright Dismissible Notices contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 *
 */

function template_notices_above()
{
	global $context, $txt, $scripturl;

	echo '
	<noscript>
		<div id="notices">';

	foreach ($context['notices'] as $notice)
	{
		addInlineJavascript('
		setTimeout(function() {
			$.notify({
				title: ' . JavaScriptEscape($notice['body']) . ',
				button: ' . JavaScriptEscape($txt['dismiss_notice_dismis']) . ',
				cancel: ' . JavaScriptEscape($txt['dismiss_notice_cancel']) . ',
				id: ' . JavaScriptEscape($notice['id_notice']) . '
			}, {
				style: \'foo\',
				className:  ' . javaScriptEscape($notice['class']) . ',
				autoHide: ' . ($context['user']['is_guest'] ? 'true' : 'false') . ',
				clickToHide: false
			});
		}, 500);', true);
		echo '
			<div class="' . $notice['class'] . '"">
				' . $notice['body'] . '<br />
				<a href="' . $scripturl . '?action=dismissnotice;url=' . urlencode($_SERVER['REQUEST_URL']) . $txt['dismiss_notice_dismis'] . '">X</a>,
			</div>';
	}
	echo '
		</div>
	</noscript>';
}

function template_dismissnotice_ajax_edit()
{
	global $context, $txt;

	echo '
	<div id="dismissnotice_box">
		<dl class="settings">
			<dt>
				<strong>' . $txt['dismissnotices_time_added'] . '</strong>
			</dt>
			<dd>
				' . $context['dismissnotice_data']['added'] . '
			</dd>
			<dt>
				<strong><label for="expire">' . $txt['dismissnotices_expire'] . '</label></strong>
			</dt>
			<dd>
				<input type="text" value="' . $context['dismissnotice_data']['expire'] . '" id="expire" name="expire" />
				<input type="hidden" value="' . $context['dismissnotice_data']['expire'] . '" id="expire_alt" name="expire_alt" />
			</dd>
			<dt>
				<strong><label for="body">' . $txt['dismissnotices_body'] . '</label></strong>
				<div class="description">' . $txt['dismissnotices_body_description'] . '</div>
			</dt>
			<dd>
				<textarea rows="7" id="body" name="body">' . $context['dismissnotice_data']['body'] . '</textarea>
			</dd>
			<dt>
				<strong><label for="class">' . $txt['dismissnotices_class'] . '</label></strong>
			</dt>
			<dd>
				<input type="text" value="' . $context['dismissnotice_data']['class'] . '" id="class" name="class" />
			</dd>
			<dt>
				<strong><label for="class">' . $txt['dismissnotices_positioning'] . '</label></strong>
			</dt>
			<dd>
				<div>
					<label for="element">' . $txt['dismissnotices_pos_element'] . '</label>
					<input type="radio" id="element" value="element" name="positioning" ' . $context['dismissnotice_data']['element'] . ' />
					<label for="global">' . $txt['dismissnotices_pos_global'] . '</label>
					<input type="radio" id="global" value="global" name="positioning" ' . $context['dismissnotice_data']['global'] . ' /><br />
					<label for="element_name">' . $txt['dismissnotices_pos_class'] . '</label>
					<input type="text" value="' . $context['dismissnotice_data']['element_name'] . '" id="element_name" name="element_name" />
				</div>

				<div style="margin-left: 60px">
					<input type="text" value="' . $context['dismissnotice_data']['position'] . '" id="position" name="position" data-thickness=".3" data-width="100" data-height="100" data-max="8" data-cursor="true" data-bgColor="#fff" data-fgColor="#080" data-displayInput="false" data-angleOffset="0" data-linecap="round" />
        </div>
			</dd>
		</dl>
		<button id="dismissnotice_submit">' . $txt['save'] . '</button>
		<button id="dismissnotice_cancel">' . $txt['cancel'] . '</button>
		<button id="dismissnotice_reset">' . $txt['reset'] . '</button>';
	template_list_groups_collapsible();
	echo '
	</div>
	<script>';

	if (!empty($context['datepicker_local']))
	{
		echo '
		$.datepicker.setDefaults(
			$.extend(
				$.datepicker.regional[\'' . $context['datepicker_local'] . '\']
			)
		);';
	}
	echo '
		$(function() {
			var $expire = $("#expire");

			$expire.datepicker({
				altField: \'#expire_alt\',
				altFormat: \'yy-mm-dd\'
			});
			if ($expire.val() != 0)
			{
				$expire.val(
					$.datepicker.formatDate(
						$("#expire").datepicker("option", "dateFormat"),
						new Date($("#expire").val() * 1000)
				));
			}

			$(\'#position\').knob();
		});
	</script>';
}