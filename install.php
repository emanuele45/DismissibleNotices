<?php
/**
 * @name      Dismissible Notices
 * @copyright Dismissible Notices contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 *
 */

global $hooks, $mod_name;
$hooks = array(
	'class' => 'Dismissible_Notices_Integrate',
	'file' => 'SUBSDIR/DismissibleNotices.integrate.php',
	'hooks' => array(
		'integrate_load_theme', 'integrate_admin_areas', 'integrate_sa_manage_news'
	)
);
$mod_name = 'Dismissible Notices';

// ---------------------------------------------------------------------------------------------------------------------
define('ELK_INTEGRATION_SETTINGS', serialize(array(
	'integrate_menu_buttons' => 'install_menu_button',)));

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('ELK'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as ElkArte\'s index.php.');

if (ELK == 'SSI')
{
	// Let's start the main job
	install_mod();
	// and then let's throw out the template! :P
	obExit(null, null, true);
}
else
{
	setup_hooks();
}

function install_mod ()
{
	global $context, $mod_name;

	$context['mod_name'] = $mod_name;
	$context['sub_template'] = 'install_script';
	$context['page_title_html_safe'] = 'Install script of the mod: ' . $mod_name;
	if (isset($_GET['action']))
		$context['uninstalling'] = $_GET['action'] == 'uninstall' ? true : false;
	$context['html_headers'] .= '
	<style type="text/css">
    .buttonlist ul {
      margin:0 auto;
			display:table;
		}
	</style>';

	// Sorry, only logged in admins...
	isAllowedTo('admin_forum');

	if (isset($context['uninstalling']))
		setup_hooks();
}

function setup_hooks ()
{
	global $context, $hooks, $smcFunc;

	$integration_function = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';

	$class = $hooks['class'];
	$file = $hooks['file'];

	foreach ($hooks['hooks'] as $hook)
	{
		$integration_function($hook, $class . '::' . $hook, $file);
	}

	if (empty($context['uninstalling']))
	{
		updateSettings(array('prefix_style' => '<span class="topicprefix">{prefix_link}</span>&nbsp;'));

		$db_table = db_table();

		$db_table->db_add_column(
			'{db_prefix}members',
			array(
				'name' => 'plain_real_name',
				'type' => 'varchar',
				'size' => 255,
				'default' => ''
			)
		);
		$db_table->db_add_column(
			'{db_prefix}members',
			array(
				'name' => 'colored_names',
				'type' => 'TEXT'
			)
		);
	}
	else
	{
		$db = database();
		$db->query('', '
			UPDATE {db_prefix}members
			SET real_name = CASE WHEN plain_real_name != {string:empty}
					THEN plain_real_name
					ELSE real_name
					END',
			array(
				'empty' => '',
			)
		);
	}


	$context['installation_done'] = true;
}

function install_menu_button (&$buttons)
{
	global $boardurl, $context;

	$context['sub_template'] = 'install_script';
	$context['current_action'] = 'install';

	$buttons['install'] = array(
		'title' => 'Installation script',
		'show' => allowedTo('admin_forum'),
		'href' => $boardurl . '/install.php',
		'active_button' => true,
		'sub_buttons' => array(
		),
	);
}

function template_install_script ()
{
	global $boardurl, $context;

	echo '
	<div class="tborder login"">
		<div class="cat_bar">
			<h3 class="catbg">
				Welcome to the install script of the mod: ' . $context['mod_name'] . '
			</h3>
		</div>
		<span class="upperframe"><span></span></span>
		<div class="roundframe centertext">';
	if (!isset($context['installation_done']))
		echo '
			<strong>Please select the action you want to perform:</strong>
			<div class="buttonlist">
				<ul>
					<li>
						<a class="active" href="' . $boardurl . '/install.php?action=install">
							<span>Install</span>
						</a>
					</li>
					<li>
						<a class="active" href="' . $boardurl . '/install.php?action=uninstall">
							<span>Uninstall</span>
						</a>
					</li>
				</ul>
			</div>';
	else
		echo '<strong>Database adaptation successful!</strong>';

	echo '
		</div>
		<span class="lowerframe"><span></span></span>
	</div>';
}
?>