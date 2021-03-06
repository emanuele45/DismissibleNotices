<?php

/**
 * @name      Dismissible Notices
 * @copyright Dismissible Notices contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 *
 */

class Dismissible_Notices
{
	protected $_db = null;

	public function __construct()
	{
		$this->_db = database();
	}

	public function getMemberNotices($id_member, $name, $groups)
	{
		$request = $this->_db->query('', '
			SELECT n.id_notice, n.body, n.class, n.expire, n.show_to, n.positioning
			FROM {db_prefix}notices AS n
				LEFT JOIN {db_prefix}log_notices AS ln ON (ln.id_notice = n.id_notice AND ln.id_member = {int:current_member})
			WHERE ln.dismissed IS NULL
				AND (n.expire = 0 OR n.expire > {int:time})',
			array(
				'current_member' => $id_member,
				'time' => forum_time(false),
			)
		);

		$notices = array();
		while ($row = $this->_db->fetch_assoc($request))
		{
			$show = (array) json_decode($row['show_to']);

			foreach ($groups as $group)
			{
				if (in_array($group, $show))
				{
					if (empty($id_member) && !empty($_SESSION['dismissible_notices'][$row['id_notice']]))
					{
						continue;
					}

					$row['positioning'] = $this->determinePositioning($row['positioning']);
					$row['body'] = $this->prepareBody($row['body'], $name);

					$notices[] = $row;
					break;
				}
			}
		}
		$this->_db->free_result($request);

		return $notices;
	}

	protected function prepareBody($body, $name)
	{
		$body = str_replace('{user_name}', $name, $body);
		$body = replaceBasicActionUrl($body);
		return parse_bbc($body);
	}

	protected function determinePositioning($positioning)
	{
		$positioning = (array) json_decode($positioning);

		if (empty($positioning['element']))
		{
			$positioning['element'] = 'global';
		}
		if (empty($positioning['position']))
		{
			$positioning['position'] = 0;
		}
		if (empty($positioning['element_name']))
		{
			$positioning['element_name'] = '';
		}
		return $positioning;
	}

	public function getNoticeById($id_notice, $parse = true)
	{
		$request = $this->_db->query('', '
			SELECT n.id_notice, n.body, n.class, n.expire, n.show_to, n.added, n.positioning
			FROM {db_prefix}notices AS n
			WHERE n.id_notice = {int:id_notice}',
			array(
				'id_notice' => $id_notice,
			)
		);

		$notice = $this->_db->fetch_assoc($request);
		$this->_db->free_result($request);

		if ($parse === true)
		{
			$notice['body'] = parse_bbc($notice['body']);
		}

		$notice['positioning'] = (array) json_decode($notice['positioning']);

		if (empty($notice['positioning']['element']))
		{
			$notice['positioning']['element'] = 'global';
		}
		if (empty($notice['positioning']['position']))
		{
			$notice['positioning']['position'] = 0;
		}
		if (empty($notice['positioning']['element_name']))
		{
			$notice['positioning']['element_name'] = '';
		}

		return $notice;
	}

	public function disableMemberNotice($id_notice, $id_member)
	{
		if(empty($id_member))
		{
			$_SESSION['dismissible_notices'][$id_notice] = false;
		}
		else
		{
			$this->_db->insert('ignore',
				'{db_prefix}log_notices',
				array(
					'id_notice' => 'int',
					'id_member' => 'int',
					'dismissed' => 'int',
				),
				array(
					$id_notice,
					$id_member,
					1
				),
				array(
					'id_notice'
				)
			);
		}
	}

	public function getAll($start, $per_page, $sort)
	{
		$valid_sort = array('id_notice', 'class', 'added', 'expire',
			'id_notice DESC', 'class DESC', 'added DESC', 'expire DESC');

		$request = $this->_db->query('', '
			SELECT id_notice, body, class, added, expire
			FROM {db_prefix}notices
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $start,
				'limit' => $per_page,
				'sort' => in_array($sort, $valid_sort) ? $sort : 'id_notice',
			)
		);
		$returns = array();
		while ($row = $this->_db->fetch_assoc($request))
		{
			$row['body'] = parse_bbc($row['body']);
			$returns[] = $row;
		}
		$this->_db->free_result($request);

		return $returns;
	}

	public function countAll()
	{
		$request = $this->_db->query('', '
			SELECT COUNT(*)
			FROM {db_prefix}notices',
			array()
		);
		list ($count) = $this->_db->fetch_row($request);
		$this->_db->free_result($request);

		return $count;
	}

	public function save($id, $expire = null, $body = null, $class = null, $show_to = null, $positioning = null)
	{
		if ($id == 0)
		{
			$this->_db->insert('',
				'{db_prefix}notices',
				array(
					'added' => 'int',
					'expire' => 'int',
					'body' => 'string',
					'class' => 'string-255',
					'show_to' => 'string-255',
					'positioning' => 'string-255'
				),
				array(
					time(),
					$expire,
					$body,
					$class,
					$show_to,
					json_encode($positioning)
				),
				array('id_notice')
			);
			$id = $this->_db->insert_id('{db_prefix}notices');
		}
		else
		{
			$current = $this->getNoticeById($id);
			if ($positioning !== null)
			{
				$positioning = json_encode($positioning);
			}

			foreach ($current as $key => $val)
			{
				if (isset($$key) && $$key !== null)
					$current[$key] = $$key;
			}

			$this->_db->query('', '
				UPDATE {db_prefix}notices
				SET
					expire = {int:expire},
					body = {string:body},
					class = {string:class},
					show_to = {string:show_to},
					positioning = {string:positioning}
				WHERE id_notice = {int:id_notice}',
				$current
			);
		}

		$new = $this->getNoticeById($id);

		return $new;
	}

	public function reset($id)
	{
		$this->_db->query('', '
			DELETE FROM {db_prefix}log_notices
			WHERE id_notice = {int:to_reset}',
			array(
				'to_reset' => $id
			)
		);
	}
}