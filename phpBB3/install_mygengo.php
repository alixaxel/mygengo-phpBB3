<?php

$phpEx = ltrim(strrchr(__FILE__, '.'), '.');
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';

if (define('IN_PHPBB', true) === true)
{
	require_once($phpbb_root_path . 'config.' . $phpEx);
	require_once($phpbb_root_path . 'includes/constants.' . $phpEx);
	require_once($phpbb_root_path . 'includes/db/' . $dbms . '.' . $phpEx);

	if (is_object($db = new $sql_db()) === true)
	{
		$db->sql_connect($dbhost, $dbuser, $dbpasswd, $dbname, $dbport, false, false);

		if (is_int($base = Module('', '', 'ACP_MYGENGO')) === true)
		{
			foreach (array('account', 'order', 'overview') as $mode)
			{
				Module('mygengo', $mode, sprintf('ACP_MYGENGO_%s', strtoupper($mode)), $base, true);
			}

			foreach (array('approve', 'cancel', 'comment', 'languages', 'reject', 'review', 'revise') as $mode)
			{
				Module('mygengo', $mode, sprintf('ACP_MYGENGO_%s', strtoupper($mode)), $base, false);
			}
		}

		if ((is_writable(__FILE__) === true) && (unlink(__FILE__) === true))
		{
			Dump('All done, don\'t forget to purge the phpBB cache!'); die();
		}

		Dump('All done, don\'t forget to delete this file and purge the phpBB cache!'); die();
	}
}

Dump('An unknown error has occurred!'); die();

function Dump()
{
	foreach (func_get_args() as $argument)
	{
		if (is_resource($argument) === true)
		{
			$result = sprintf('%s (#%u)', get_resource_type($argument), $argument);
		}

		else if ((is_array($argument) === true) || (is_object($argument) === true))
		{
			$result = rtrim(print_r($argument, true));
		}

		else
		{
			$result = stripslashes(preg_replace("~^'|'$~", '', var_export($argument, true)));
		}

		if (strncmp('cli', PHP_SAPI, 3) !== 0)
		{
			$result = '<pre style="background: #df0; margin: 5px; padding: 5px; text-align: center;">' . htmlspecialchars($result, ENT_QUOTES) . '</pre>';
		}

		echo $result . "\n";
	}
}

function Module($name = null, $mode = null, $language = null, $parent = true, $display = true)
{
	global $db;

	$data = array
	(
		'module_enabled' => '1',
		'module_display' => intval($display),
		'module_basename' => trim($name),
		'module_class' => 'acp',
		'parent_id' => intval($parent),
		'module_langname' => trim($language),
		'module_mode' => trim($mode),
		'module_auth' => 'acl_a_',
	);

	if ($data['parent_id'] >= 1)
	{
		$sql = sprintf('SELECT left_id, right_id FROM %s WHERE module_langname = "%s";', MODULES_TABLE, $db->sql_escape($data['module_langname']));
		$query = $db->sql_query($sql);
		$result = $db->sql_fetchrow($query);

		if (empty($result) === true)
		{
			$sql = sprintf('SELECT left_id, right_id FROM %s WHERE module_id = %u;', MODULES_TABLE, $data['parent_id']);
			$query = $db->sql_query($sql);
			$result = $db->sql_fetchrow($query);

			if (is_array($result) === true)
			{
				$db->sql_query(sprintf('UPDATE %s SET left_id = left_id + 2, right_id = right_id + 2 WHERE module_class = "%s" AND left_id > %u;', MODULES_TABLE, 'acp', $result['right_id']));
				$db->sql_query(sprintf('UPDATE %s SET right_id = right_id + 2 WHERE module_class = "%s" AND %u BETWEEN left_id AND right_id;', MODULES_TABLE, 'acp', $result['left_id']));

				$data['left_id'] = intval($result['right_id']);
				$data['right_id'] = intval($result['right_id']) + 1;

				$db->sql_query('INSERT INTO ' . MODULES_TABLE . $db->sql_build_array('INSERT', $data));

				return $db->sql_nextid();
			}
		}
	}

	return false;
}

?>