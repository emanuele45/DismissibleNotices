<?php

/**
 * @name      Dismissible Notices
 * @copyright Dismissible Notices contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 *
 */

// Thats just no to you
if (!defined('ELK'))
	die('No access...');

class Dismissible_Notices_Integrate
{
	public static function integrate_load_theme()
	{
		global $user_info, $txt;

		$name = $user_info['is_guest'] ? $txt['guest_title'] : $user_info['name'];
		$notices = self::getNotices($user_info['id'], $name, $user_info['groups']);

		if (!empty($notices))
			self::buildTemplate($notices);
	}

	public static function integrate_sa_manage_news(&$subActions)
	{
		$subActions['notices'] = array(
			'file' => 'ManageDismissnotice.controller.php',
			'dir' => ADMINDIR,
			'controller' => 'Manage_Dismissnotice_Controller',
			'function' => 'action_index'
		);
	}

	public static function integrate_admin_areas(&$menuData, &$menuOptions)
	{
		global $txt;

		if (isset($menuData['forum']['areas']['news']['subsections']))
		{
			loadLanguage('DismissibleNotices');
			$menuData['forum']['areas']['news']['subsections']['notices'] = array($txt['dismissnotices_title_list'], 'edit_news');
		}
	}

	protected static function buildTemplate($notices)
	{
		global $context, $user_info;

		loadTemplate('DismissibleNotices');
		loadLanguage('DismissibleNotices');
		Template_Layers::getInstance()->addAfter('notices', 'body');
		loadJavascriptFile('notify.js', array('defer' => true));
		loadCSSFile('notify.css');

		foreach ($notices as $key => $notice)
		{
			$notice['body'] = str_replace('{username}', $user_info['name'], $notice['body']);
			$context['notices'][$key] = $notice;
		}

		$context['notices'] = $notices;
	}

	protected static function getNotices($id_member, $name, $groups)
	{
		require_once(SUBSDIR . '/DismissibleNotices.class.php');
		$notice = new Dismissible_Notices();

		return $notice->getMemberNotices($id_member, $name, $groups);
	}

	public static function disableNotice($id_notice)
	{
		global $user_info;

		if (empty($user_info['id']))
			return;

		require_once(SUBSDIR . '/DismissibleNotices.class.php');
		$notice = new Dismissible_Notices();

		return $notice->disableNotice($id_notice, $user_info['id']);
	}

	public static function formatExpireCol($time)
	{
		if ($time == 0)
			return '<i class="fa fa-check success"></i>';
		elseif ($time > forum_time(false))
			return standardTime($time) . ' <i class="fa fa-clock-o success"></i>';
		else
			return '<i class="fa fa-times-circle-o  error"></i>';
	}
}