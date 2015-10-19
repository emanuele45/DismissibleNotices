<?php
/**
 * @name      Dismissible Notices
 * @copyright Dismissible Notices contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 *
 */

class Dismissnotice_Controller extends Action_Controller
{
	public function action_index()
	{
		global $user_info;

		checkSession('get');

		$id_notice = isset($_GET['idnotice']) ? (int) $_GET['idnotice'] : 0;
		if (!empty($id_notice))
		{
			require_once(SUBSDIR . '/DismissibleNotices.class.php');
			$notice = new Dismissible_Notices();

			$notice->disableMemberNotice($id_notice, $user_info['id']);
		}

		die();
	}
}