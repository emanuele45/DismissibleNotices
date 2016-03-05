<?php
/**
 * @name      Dismissible Notices
 * @copyright Dismissible Notices contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 *
 */

class Manage_Dismissnotice_Controller extends Action_Controller
{
	public function action_index()
	{
		global $txt, $context;

		if (isset($_GET['api']))
			return $this->action_index_api();
		else
			return $this->action_list();
	}

	public function action_index_api()
	{
		Template_Layers::getInstance()->removeAll();
		loadTemplate('DismissibleNotices');
		loadLanguage('DismissibleNotices');
		if (isset($_GET['edit']))
			return $this->action_edit();
		elseif (isset($_GET['save']))
			return $this->action_save();
		elseif (isset($_GET['new']))
			return $this->action_new();
		elseif (isset($_GET['reset']))
			return $this->action_reset();
	}

	public function action_list()
	{
		global $txt, $context, $scripturl, $modSettings, $settings;

		loadJavascriptFile('notify.js', array('defer' => true));
		loadJavascriptFile('jquery.knob.js', array('defer' => true));
		loadCSSFile('notify.css');
		addInlineJavascript('dismissnotice_editable();', true);
		$modSettings['jquery_include_ui'] = true;
		loadCSSFile('dism/jquery.ui.theme.css');
		loadCSSFile('dism/jquery.ui.datepicker.css');
		loadCSSFile('dism/jquery.ui.d.theme.css');
		loadCSSFile('dism/jquery.ui.core.css');

		$possible_locals = array($txt['lang_locale']);
		$b = explode('_', $txt['lang_locale']);
		foreach ($b as $a)
			$possible_locals[] = $a;

		foreach ($possible_locals as $local)
		{
			if (file_exists($settings['default_theme_dir'] . '/scripts/datepicker-i18n/datepicker-' . $local . '.js'))
			{
				loadJavascriptFile('datepicker-i18n/datepicker-' . $local . '.js', array('defer' => true));
				break;
				$context['datepicker_local'] = $local;
			}
		}

		$list_options = array(
			'id' => 'list_dismissible_notices',
			'title' => $txt['dismissnotices_title_list'],
			'items_per_page' => 20,
			'base_href' => $scripturl . '?action=admin;area=dismissnotice;sa=list' . $context['session_var'] . '=' . $context['session_id'],
			'default_sort_col' => 'timeadded',
			'get_items' => array(
				'function' => array($this, 'getItems'),
			),
			'get_count' => array(
				'function' => array($this, 'countItems'),
			),
			'data_check' => array(
				'class' => function($rowData) {
					return 'editable_' . $rowData['id_notice'];
				},
			),
			'no_items_label' => $txt['hooks_no_hooks'],
			'columns' => array(
				'timeadded' => array(
					'header' => array(
						'value' => $txt['dismissnotices_time_added'],
					),
					'data' => array(
						'function' => function($rowData) {
							return standardTime($rowData['added']);
						},
					),
					'sort' => array(
						'default' => 'added',
						'reverse' => 'added DESC',
					),
				),
				'body' => array(
					'header' => array(
						'value' => $txt['dismissnotices_body'],
					),
					'data' => array(
						'db' => 'body',
					),
				),
				'class' => array(
					'header' => array(
						'value' => $txt['dismissnotices_class'],
					),
					'data' => array(
						'db_htmlsafe' => 'class',
					),
					'sort' => array(
						'default' => 'class',
						'reverse' => 'class DESC',
					),
				),
				'expire' => array(
					'header' => array(
						'value' => $txt['dismissnotices_expire'],
					),
					'data' => array(
						'function' => function($rowData) {
							return Dismissible_Notices_Integrate::formatExpireCol($rowData['expire']);
						},
					),
					'sort' => array(
						'default' => 'expire',
						'reverse' => 'expire DESC',
					),
				),
				'edit' => array(
					'header' => array(
						'value' => '',
					),
					'data' => array(
						'function' => function($rowData) {
							global $txt;

							return '<a data-idnotice="' . $rowData['id_notice'] . '" class="dismissnotice_editable" href="#">' . $txt['modify'] . '</a>';
						},
					),
				),
			),
			'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'value' => '<a id="dismissnotice_new" class="floatright linkbutton" href="#">' . $txt['new'] . '</a>',
				),
			),
		);

		require_once(SUBSDIR . '/GenericList.class.php');
		createList($list_options);
	}

	public function getItems($start, $per_page, $sort)
	{
		require_once(SUBSDIR . '/DismissibleNotices.class.php');
		$notice = new Dismissible_Notices();

		return $notice->getAll($start, $per_page, $sort);
	}

	public function countItems()
	{
		require_once(SUBSDIR . '/DismissibleNotices.class.php');
		$notice = new Dismissible_Notices();

		return $notice->countAll();
	}

	public function action_new()
	{
		global $context;

		$context['sub_template'] = 'dismissnotice_ajax_edit';

		$context['default_groups_list'] = $this->populateGroupList(array(-1, 0));

		$context['dismissnotice_data'] = array(
			'added' => 0,
			'expire' => 0,
			'body' => '',
			'class' => 'success',
			'element' => '',
			'position' => 0,
			'element_name' => 'success',
			'global' => 'checked="checked"',
			'groups' => array(),
		);
	}

	public function action_edit()
	{
		global $context;

		$context['sub_template'] = 'dismissnotice_ajax_edit';

		$id_notice = isset($_GET['idnotice']) ? (int) $_GET['idnotice']: 0;

		if (empty($id_notice))
			return $this->action_new();

		require_once(SUBSDIR . '/Post.subs.php');
		require_once(SUBSDIR . '/DismissibleNotices.class.php');
		$notice = new Dismissible_Notices();

		$dismissnotice_data = $notice->getNoticeById($id_notice);
		$selected_groups = (array) json_decode($dismissnotice_data['show_to']);

		$context['default_groups_list'] = $this->populateGroupList($selected_groups);

		$context['dismissnotice_data'] = array(
			'added' => standardTime($dismissnotice_data['added']),
			'expire' => $dismissnotice_data['expire'],
			'body' => un_preparsecode($dismissnotice_data['body']),
			'class' => $dismissnotice_data['class'],
			'element' => $this->positionChecked('element', $dismissnotice_data['positioning']['element']),
			'position' => $dismissnotice_data['positioning']['position'],
			'element_name' => $dismissnotice_data['positioning']['element_name'],
			'global' => $this->positionChecked('global', $dismissnotice_data['positioning']['element']),
			'groups' => $selected_groups,
		);
	}

	protected function positionChecked($position, $test)
	{
		if ($position == $test)
		{
			return 'checked="checked"';
		}
		else
		{
			return '';
		}
	}

	public function action_reset()
	{
		global $txt, $context;

		$id = isset($_REQUEST['idnotice']) ? (int) $_REQUEST['idnotice'] : 0;

		loadTemplate('Json');
		$context['sub_template'] = 'send_json';

		if (empty($id))
		{
			$context['json_data'] = array(
				'error' => $txt['dismissnotices_no_notice'],
			);
			return;
		}

		require_once(SUBSDIR . '/DismissibleNotices.class.php');
		$notice = new Dismissible_Notices();
		$new = $notice->reset($id);

		$context['json_data'] = array(
			'success' => $txt['dismissnotices_notice_reset'],
		);
	}

	protected function populateGroupList($selected_groups)
	{
		global $txt;

		require_once(SUBSDIR . '/Membergroups.subs.php');
		loadTemplate('GenericHelpers');

		// We need group data, including which groups we have and who is in them
		$allgroups = getBasicMembergroupData(array('all'), array(), null, true);
		$groups = $allgroups['groups'];

		$groups[-1] = array(
			'id' => -1,
			'name' => $txt['guests'],
			'member_count' => 0,
		);
		ksort($groups);
		// All of the members in post based and member based groups
		$pg = array();
		foreach ($allgroups['postgroups'] as $postgroup)
			$pg[] = $postgroup['id'];

		$mg = array();
		foreach ($allgroups['membergroups'] as $membergroup)
			$mg[] = $membergroup['id'];

		// How many are in each group
		$mem_groups = membersInGroups($pg, $mg, true, true);
		foreach ($mem_groups as $id_group => $member_count)
		{
			if (isset($groups[$id_group]['member_count']))
				$groups[$id_group]['member_count'] += $member_count;
			else
				$groups[$id_group]['member_count'] = $member_count;
		}

		foreach ($groups as $group)
		{
			$groups[$group['id']]['status'] = in_array($group['id'], $selected_groups) ? 'on' : 'off';
			$groups[$group['id']]['is_postgroup'] = in_array($group['id'], $pg);
		}

		return array(
			'select_group' => $txt['dismissnotices_groups_show_notice'],
			'member_groups' => $groups
		);
	}

	protected function validPositioning($position)
	{
		switch ($position)
		{
			case 'element':
			case 'global':
				return $position;
			default:
				return 'global';
		}
	}

	public function action_save()
	{
		global $context, $txt;

		require_once(SUBSDIR . '/Post.subs.php');

		if (empty($_POST['expire_alt']))
		{
			$expire = strtotime($_POST['expire']);
		}
		else
		{
			// This is the case date-picker doesn't kick in and the format is still an unix timestamp
			if (is_numeric($_POST['expire_alt']))
			{
				$expire = $_POST['expire_alt'];
			}
			else
			{
				$expire = strtotime($_POST['expire_alt']);
			}
		}

		$expire = (int) $expire;

		$id = isset($_REQUEST['idnotice']) ? (int) $_REQUEST['idnotice'] : 0;
		$body = isset($_REQUEST['body']) ? Util::htmlspecialchars($_REQUEST['body']) : '';
		$class = isset($_REQUEST['class']) ? Util::htmlspecialchars($_REQUEST['class']) : 'success';
		preparsecode($body);
		$groups = json_encode(array_map('intval', array_keys($_POST['default_groups_list'])));

		$positioning = array(
			'element' => $this->validPositioning(isset($_REQUEST['positioning']) ? $_REQUEST['positioning'] : null),
			'element_name' => isset($_REQUEST['element_name']) ? Util::htmlspecialchars($_REQUEST['element_name']) : '',
			'position' => isset($_REQUEST['position']) ? (int) $_REQUEST['position'] : 0,
		);

		require_once(SUBSDIR . '/DismissibleNotices.class.php');
		$notice = new Dismissible_Notices();
		$new = $notice->save($id, $expire, $body, $class, $groups, $positioning);

		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
		$context['json_data'] = array(
			'id' => $new['id_notice'],
			'added' => standardTime($new['added']),
			'expire' => Dismissible_Notices_Integrate::formatExpireCol($expire),
			'body' => un_htmlspecialchars($body),
			'class' => $new['class'],
			'groups' => $new['show_to'],
			'edit' => '<a data-idnotice="' . $new['id_notice'] . '" class="dismissnotice_editable" href="#">' . $txt['modify'] . '</a>',
		);
	}
}