<?php

class acp_mygengo_info
{
	function module()
	{
		return array
		(
			'filename' => 'acp_mygengo',
			'title' => 'ACP_MYGENGO',
			'version' => '1.0.0',
			'modes' => array
			(
				'account' => array
				(
					'title' => 'ACP_MYGENGO_ACCOUNT',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'approve' => array
				(
					'title' => 'ACP_MYGENGO_APPROVE',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'cancel' => array
				(
					'title' => 'ACP_MYGENGO_CANCEL',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'comment' => array
				(
					'title' => 'ACP_MYGENGO_COMMENT',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'languages' => array
				(
					'title' => 'ACP_MYGENGO_LANGUAGES',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'order' => array
				(
					'title' => 'ACP_MYGENGO_ORDER',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'overview' => array
				(
					'title' => 'ACP_MYGENGO_OVERVIEW',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'reject' => array
				(
					'title' => 'ACP_MYGENGO_REJECT',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'review' => array
				(
					'title' => 'ACP_MYGENGO_REVIEW',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),

				'revise' => array
				(
					'title' => 'ACP_MYGENGO_REVISE',
					'auth' => 'acl_a_',
					'cat' => array(''),
				),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>