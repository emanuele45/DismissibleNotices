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

	public function getMemberNotices($id_member, $groups)
	{
		$request = $this->_db->query('', '
			SELECT n.id_notice, n.body, n.class, n.expire, n.show_to
			FROM {db_prefix}notices AS n
				LEFT JOIN {db_prefix}log_notices AS ln ON (ln.id_notice = n.id_notice AND ln.id_member = {int:current_member})
			WHERE ln.dismissed IS NULL
				AND (n.expire = 0 OR n.expire < {int:time})',
			array(
				'current_member' => $id_member,
				'time' => forum_time(false),
			)
		);

		$notices = array();
		while ($row = $this->_db->fetch_assoc($request))
		{
			$show = explode(',', $row['show_to']);
			foreach ($groups as $group)
			{
				if (in_array($group, $show))
				{
					$row['body'] = parse_bbc($row['body']);
					$notices[] = $row;
					break;
				}
			}
		}
		$this->_db->free_result($request);

		return $notices;
	}

	public function getNoticeById($id_notice)
	{
		$request = $this->_db->query('', '
			SELECT n.id_notice, n.body, n.class, n.expire, n.show_to, n.added
			FROM {db_prefix}notices AS n
			WHERE n.id_notice = {int:id_notice}',
			array(
				'id_notice' => $id_notice,
			)
		);

		$notice = $this->_db->fetch_assoc($request);
		$this->_db->free_result($request);
		$notice['body'] = parse_bbc($notice['body']);

		return $notice;
	}

	public function disableMemberNotice($id_notice, $id_member)
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

	public function save($id, $expire = null, $body = null, $class = null, $show_to = null)
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
					'show_to' => 'string-255'
				),
				array(
					time(),
					$expire,
					$body,
					$class,
					$show_to
				),
				array('id_notice')
			);
			$id = $this->_db->insert_id('{db_prefix}notices');
		}
		else
		{
			$current = $this->getNoticeById($id);

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
					show_to = {string:show_to}
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