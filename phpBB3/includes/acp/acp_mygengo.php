<?php

class acp_mygengo
{
	var $notes;
	var $myGengo;

	function main($id, $mode)
	{
		global $config, $template;

		if (in_array($mode, get_class_methods(__CLASS__)) === true)
		{
			$this->notes = array();
			$public = myGengo::_value($config, 'mygengo_public_key');
			$private = myGengo::_value($config, 'mygengo_private_key');

			if ((strlen($public) * strlen($private)) == 0)
			{
				$mode = 'account';

				$this->notes[] = array
				(
					'CLASS' => 'errorbox notice',
					'MESSAGE' => 'You need to setup your <a href="http://mygengo.com/services/api/" rel="external">myGengo API</a> keys.',
				);
			}

			$this->myGengo = new myGengo($public, $private, myGengo::_value($config, 'mygengo_environment', true));
			$this->tpl_name = sprintf('acp_mygengo_%s', strtolower($mode));
			$this->page_title = sprintf('ACP_MYGENGO_%s', strtoupper($mode));

			call_user_func_array(array($this, $mode), array(request_var('job_id', '0'), request_var('rev_id', '0')));

			if (is_array($this->notes) === true)
			{
				if ((isset($_GET['notes']) === true) && (is_array($_GET['notes']) === true))
				{
					$this->notes = array_merge($_GET['notes'], $this->notes);
				}

				$this->notes = array_map('unserialize', array_unique(array_map('serialize', $this->notes)));

				foreach ($this->notes as $note)
				{
					$template->assign_block_vars('messages', array_change_key_case($note, CASE_UPPER));
				}
			}
		}
	}

	function account()
	{
		global $user, $config, $template;

		try
		{
			if (strcasecmp('POST', $_SERVER['REQUEST_METHOD']) === 0)
			{
				set_config('mygengo_public_key', trim(request_var('mygengo_public_key', '')), true);
				set_config('mygengo_private_key', trim(request_var('mygengo_private_key', '')), true);
				set_config('mygengo_environment', intval(request_var('mygengo_environment', '1')), true);
				set_config('mygengo_auto_approve', intval(request_var('mygengo_auto_approve', '0')), true);

				$public = myGengo::_value($config, 'mygengo_public_key');
				$private = myGengo::_value($config, 'mygengo_private_key');

				if ((strlen($public) * strlen($private)) == 0)
				{
					$this->notes[] = array
					(
						'CLASS' => 'errorbox notice',
						'MESSAGE' => 'You need to setup your <a href="http://mygengo.com/services/api/" rel="external">myGengo API</a> keys.',
					);
				}

				else
				{
					$this->notes = array();
				}

				$this->myGengo = new myGengo($public, $private, myGengo::_value($config, 'mygengo_environment', true));
			}

			foreach (array('public_key', 'private_key', 'environment', 'auto_approve') as $key)
			{
				$template->assign_var(strtoupper($key), myGengo::_value($config, 'mygengo_' . $key));
			}

			if (is_array($balance = $this->myGengo->account->balance()) === true)
			{
				$template->assign_vars(array_change_key_case($balance, CASE_UPPER));
			}

			if (is_array($statistics = $this->myGengo->account->statistics()) === true)
			{
				if (array_key_exists('user_since', $statistics) === true)
				{
					$statistics['user_since'] = $user->format_date($statistics['user_since']);
				}

				$template->assign_vars(array_change_key_case($statistics, CASE_UPPER));
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}
	}

	function approve($job_id)
	{
		try
		{
			if (empty($_POST['job']['rating']) !== true)
			{
				//$_POST['job']['rating'] = intval($_POST['job']['rating']);
			}

			if ($this->myGengo->job($job_id)->put('approve', myGengo::_value($_POST, 'job')) === true)
			{
				$this->notes[] = array
				(
					'CLASS' => 'successbox',
					'MESSAGE' => sprintf('Job approved successfully.'),
				);
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}

		self::_redirect('review', array('job_id' => $job_id), $this->notes);
	}

	function cancel($job_id)
	{
		try
		{
			if ($this->myGengo->job($job_id)->delete() === true)
			{
				$this->notes[] = array
				(
					'CLASS' => 'successbox',
					'MESSAGE' => sprintf('Job canceled successfully.'),
				);
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}

		self::_redirect('overview', null, $this->notes);
	}

	function comment($job_id)
	{
		try
		{
			if ($this->myGengo->job($job_id)->comment(myGengo::_value($_POST, 'comment')) === true)
			{
				$this->notes[] = array
				(
					'CLASS' => 'successbox',
					'MESSAGE' => sprintf('Comment submitted successfully.'),
				);
				$this->notes[] = array
				(
					'CLASS' => 'successbox',
					'MESSAGE' => sprintf('Comment submitted successfully.'),
				);
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}

		self::_redirect('review', array('job_id' => $job_id), $this->notes);
	}

	function languages()
	{
		if (ob_end_clean() === true)
		{
			if (strcasecmp('XMLHttpRequest', myGengo::_value($_SERVER, 'HTTP_X_REQUESTED_WITH')) === 0)
			{
				try
				{
					if (is_array($pairs = $this->myGengo->service->languagePairs(request_var('lc_src', ''))) === true)
					{
						$result = array();

						foreach ($pairs as $pair)
						{
							$result[$pair['lc_tgt']] = $pair['lc_tgt'];
						}

						if (is_array($languages = $this->myGengo->service->languages()) === true)
						{
							foreach ($languages as $language)
							{
								if (array_key_exists($language['lc'], $result) === true)
								{
									$result[$language['lc']] = $language['language'];
								}
							}
						}

						echo '<option value=""></option>' . "\n";

						foreach ($result as $key => $value)
						{
							echo sprintf('<option value="%s">%s</option>', $key, $value) . "\n";
						}
					}
				}

				catch (myGengo_Exception $e)
				{
					$this->notes[] = array
					(
						'CLASS' => 'errorbox',
						'MESSAGE' => $e->getMessage(),
					);
				}
			}
		}

		die();
	}

	function order()
	{
		global $db, $config, $template;

		try
		{
			if (is_array($languages = $this->myGengo->service->languages()) === true)
			{
				foreach ($languages as $language)
				{
					$template->assign_block_vars('languages', array_change_key_case($language, CASE_UPPER));
				}
			}

			$template->assign_var('PUBLIC_KEY', myGengo::_value($config, 'mygengo_public_key'));
			$template->assign_var('AUTO_APPROVE', myGengo::_value($config, 'mygengo_auto_approve', 0));

			if (strcasecmp('POST', $_SERVER['REQUEST_METHOD']) === 0)
			{
				$template->assign_vars(array_change_key_case($_POST['job'], CASE_UPPER));

				if (empty($_POST['quote']) === true)
				{
					if (is_array($job = myGengo::_value($this->myGengo->job->post($_POST['job']), 'job')) === true)
					{
						$this->notes[] = array
						(
							'CLASS' => 'successbox',
							'MESSAGE' => sprintf('Job submitted successfully.'),
						);

						self::_redirect('review', array('job_id' => $job['job_id']), $this->notes);
					}
				}

				else
				{
					if (is_array($quote = myGengo::_value($this->myGengo->service->quote($_POST['job']), array('jobs', 'job_1'))) === true)
					{
						$template->assign_vars(array_change_key_case($quote, CASE_UPPER));
					}
				}
			}

			else if (($post_id = intval(request_var('post_id', '0'))) > 0)
			{
				$sql = sprintf('SELECT * FROM %s WHERE post_id = "%u" LIMIT 1;', POSTS_TABLE, $db->sql_escape($post_id));
				$query = $db->sql_query($sql);
				$result = $db->sql_fetchrow($query);

				if ((is_array($result) === true) && (array_key_exists('post_text', $result) === true))
				{
					$data = array
					(
						'slug' => 'Post #' . $result['post_id'],
						'body_src' => $result['post_text'],
					);

					$template->assign_vars(array_change_key_case($data, CASE_UPPER));
				}
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}
	}

	function overview()
	{
		global $user, $config, $template;

		try
		{
			$template->assign_var('ENVIRONMENT', myGengo::_value($config, 'mygengo_environment', true));

			if (is_array($jobs = $this->myGengo->jobs()->filter()) === true)
			{
				if (is_array($languages = $this->myGengo->service->languages()) === true)
				{
					foreach ($languages as $language)
					{
						$languages[$language['lc']] = $language['language'];
					}
				}

				foreach ($jobs as $job)
				{
					if (is_array($job = myGengo::_value($this->myGengo->job($job['job_id'])->get(), 'job')) === true)
					{
						foreach (array('lc_src', 'lc_tgt', 'status', 'ctime') as $value)
						{
							if (strncmp('lc_', $value, 3) === 0)
							{
								if (isset($job[$value], $languages[$job[$value]]) === true)
								{
									$job[$value] = $languages[$job[$value]];
								}
							}

							$job[$value] = (is_int($job[$value]) == true) ? $user->format_date($job[$value]) : ucfirst($job[$value]);
						}

						$template->assign_block_vars('jobs', array_change_key_case($job, CASE_UPPER));
					}
				}
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}
	}

	function reject($job_id)
	{
		global $template;

		try
		{
			$template->assign_var('JOB_ID', $job_id);

			if (is_array($job = myGengo::_value($this->myGengo->job($job_id)->get(), 'job')) === true)
			{
				$template->assign_vars(array_change_key_case($job, CASE_UPPER));
			}

			if ((strcasecmp('POST', $_SERVER['REQUEST_METHOD']) === 0) && (empty($_POST['mode']) === true))
			{
				$template->assign_vars(array_change_key_case($_POST['job'], CASE_UPPER));

				if ($this->myGengo->job($job_id)->put('reject', $_POST['job']) === true)
				{
					$this->notes[] = array
					(
						'CLASS' => 'successbox',
						'MESSAGE' => sprintf('Job rejected successfully.'),
					);

					self::_redirect('review', array('job_id' => $job_id), $this->notes);
				}
			}
		}

		catch (myGengo_Exception $e)
		{
			$message = explode('|', $e->getMessage());

			if (count($message) > 1)
			{
				$template->assign_var('CAPTCHA_URL', array_pop($message));
			}

			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => implode('|', $message),
			);
		}
	}

	function review($job_id)
	{
		global $user, $config, $template;

		try
		{
			$template->assign_var('JOB_ID', $job_id);
			$template->assign_var('ENVIRONMENT', myGengo::_value($config, 'mygengo_environment', true));

			if (is_array($job = myGengo::_value($this->myGengo->job($job_id)->get(true), 'job')) === true)
			{
				if (is_array($languages = $this->myGengo->service->languages()) === true)
				{
					foreach ($languages as $language)
					{
						$languages[$language['lc']] = $language['language'];
					}
				}

				foreach (array('lc_src', 'lc_tgt', 'status', 'ctime') as $value)
				{
					if (strncmp('lc_', $value, 3) === 0)
					{
						if (isset($job[$value], $languages[$job[$value]]) === true)
						{
							$job[$value] = $languages[$job[$value]];
						}
					}

					$job[$value] = (is_int($job[$value]) == true) ? $user->format_date($job[$value]) : ucfirst($job[$value]);
				}

				$template->assign_vars(array_change_key_case($job, CASE_UPPER));
			}

			if (is_array($feedback = myGengo::_value($this->myGengo->job($job_id)->feedback(), 'feedback')) === true)
			{
				$template->assign_vars(array_change_key_case($feedback, CASE_UPPER));
			}

			if (is_array($comments = myGengo::_value($this->myGengo->job($job_id)->comments(), 'thread')) === true)
			{
				foreach ($comments as $comment)
				{
					if (array_key_exists('ctime', $comment) === true)
					{
						$comment['ctime'] = $user->format_date($comment['ctime']);
					}

					$template->assign_block_vars('comments', array_change_key_case($comment, CASE_UPPER));
				}
			}

			if (in_array($job['status'], array('Held', 'Reviewable')) !== true)
			{
				if (is_array($revisions = myGengo::_value($this->myGengo->job($job_id)->revisions(), 'revisions')) === true)
				{
					if ((count($revisions) > 0) && (count($revisions) == count($revisions, COUNT_RECURSIVE)))
					{
						$revisions = array($revisions);
					}

					if (is_array($revisions) === true)
					{
						foreach ($revisions as $revision)
						{
							if (is_array($revision = myGengo::_value($this->myGengo->job($job_id)->revision($revision['rev_id']), 'revision')) === true)
							{
								if (empty($revision['body_target']) !== true)
								{
									if (array_key_exists('ctime', $revision) === true)
									{
										$revision['ctime'] = $user->format_date($revision['ctime']);
									}

									$template->assign_block_vars('revisions', array_change_key_case($revision, CASE_UPPER));
								}
							}
						}
					}
				}
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}
	}

	function revise($job_id)
	{
		try
		{
			if ($this->myGengo->job($job_id)->put('revise', myGengo::_value($_POST, 'job')) === true)
			{
				$this->notes[] = array
				(
					'CLASS' => 'successbox',
					'MESSAGE' => sprintf('Job revised successfully.'),
				);
			}
		}

		catch (myGengo_Exception $e)
		{
			$this->notes[] = array
			(
				'CLASS' => 'errorbox',
				'MESSAGE' => $e->getMessage(),
			);
		}

		self::_redirect('review', array('job_id' => $job_id), $this->notes);
	}

	function _redirect($mode = null, $query = null, $notes = null)
	{
		if (strncmp('cli', PHP_SAPI, 3) !== 0)
		{
			$notes = rtrim('&' . http_build_query(array('notes' => $notes), '', '&'), '&');

			if (strncmp('cgi', PHP_SAPI, 3) === 0)
			{
				header('Status: 302', true, 302);
			}

			header('Location: ' . append_sid(generate_board_url() . '/adm/index.php', array_merge(array('i' => 'mygengo', 'mode' => $mode), (array) $query), false) . $notes, true, 302);
		}

		exit();
	}
}

class myGengo
{
	public static $public = null;
	public static $private = null;
	public static $production = null;

	public function __construct($public, $private, $production = false)
	{
		self::$public = $public;
		self::$private = $private;
		self::$production = $production;
	}

	public function __get($key)
	{
		$key = ucfirst($key);
		$class = __CLASS__ . '_' . $key;

		if (class_exists($class, false) === true)
		{
			$this->$key = new $class();
		}

		return $this->$key;
	}

	public static function _curl($url, $data = null, $method = 'GET', $options = null)
	{
		$result = false;

		if (is_resource($curl = curl_init()) === true)
		{
			if (($url = self::_url($url, null, (preg_match('~^(?:POST|PUT)$~i', $method) > 0) ? null : $data)) !== false)
			{
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_FAILONERROR, true);
				curl_setopt($curl, CURLOPT_AUTOREFERER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

				if (preg_match('~^(?:DELETE|GET|HEAD|OPTIONS|POST|PUT)$~i', $method) > 0)
				{
					if (preg_match('~^(?:HEAD|OPTIONS)$~i', $method) > 0)
					{
						curl_setopt_array($curl, array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true));
					}

					else if (preg_match('~^(?:POST|PUT)$~i', $method) > 0)
					{
						if ((is_array($data) === true) && ((count($data) != count($data, COUNT_RECURSIVE)) || (count(preg_grep('~^@~', $data)) == 0)))
						{
							$data = http_build_query($data, '', '&');
						}

						curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
					}

					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));

					if (is_array($options) === true)
					{
						curl_setopt_array($curl, $options);
					}

					for ($i = 1; $i <= 3; ++$i)
					{
						$result = curl_exec($curl);

						if (($i == 3) || ($result !== false))
						{
							break;
						}

						usleep(pow(2, $i - 2) * 1000000);
					}
				}
			}

			curl_close($curl);
		}

		return $result;
	}

	public static function _unicode($data)
	{
		if (is_array($data) === true)
		{
			$result = array();

			foreach ($data as $key => $value)
			{
				$result[self::_unicode($key)] = self::_unicode($value);
			}

			return $result;
		}

		else if (is_string($data) === true)
		{
			if (function_exists('iconv') === true)
			{
				return @iconv('UTF-8', 'UTF-8//IGNORE', $data);
			}

			else if (function_exists('mb_convert_encoding') === true)
			{
				return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
			}

			return utf8_encode(utf8_decode($data));
		}

		return $data;
	}

	public static function _url($url = null, $path = null, $query = null)
	{
		if (isset($url) === true)
		{
			if ((is_array($url = @parse_url($url)) === true) && (isset($url['scheme'], $url['host']) === true))
			{
				$result = strtolower($url['scheme']) . '://';

				if ((isset($url['user']) === true) || (isset($url['pass']) === true))
				{
					$result .= ltrim(rtrim(self::_value($url, 'user') . ':' . self::_value($url, 'pass'), ':') . '@', '@');
				}

				$result .= strtolower($url['host']) . '/';

				if ((isset($url['port']) === true) && (strcmp($url['port'], getservbyname($url['scheme'], 'tcp')) !== 0))
				{
					$result = rtrim($result, '/') . ':' . intval($url['port']) . '/';
				}

				if (($path !== false) && ((isset($path) === true) || (isset($url['path']) === true)))
				{
					if (is_scalar($path) === true)
					{
						if (($query !== false) && (preg_match('~[?&]~', $path) > 0))
						{
							$url['query'] = ltrim(rtrim(self::_value($url, 'query'), '&') . '&' . preg_replace('~^.*?[?&]([^#]*).*$~', '$1', $path), '&');
						}

						$url['path'] = '/' . ltrim(preg_replace('~[?&#].*$~', '', $path), '/');
					}

					while (preg_match('~/[.][.]?(?:/|$)~', $url['path']) > 0)
					{
						$url['path'] = preg_replace(array('~/+~', '~/[.](?:/|$)~', '~(?:^|/[^/]+)/[.]{2}(?:/|$)~'), '/', $url['path']);
					}

					$result .= preg_replace('~/+~', '/', ltrim($url['path'], '/'));
				}

				if (($query !== false) && ((isset($query) === true) || (isset($url['query']) === true)))
				{
					parse_str(self::_value($url, 'query'), $url['query']);

					if (is_array($query) === true)
					{
						$url['query'] = array_merge($url['query'], $query);
					}

					if ((count($url['query'] = self::_voodoo(array_filter($url['query'], 'count'))) > 0) && (ksort($url['query']) === true))
					{
						$result .= rtrim('?' . http_build_query($url['query'], '', '&'), '?');
					}
				}

				return preg_replace('~(%[0-9a-f]{2})~e', "strtoupper('$1')", $result);
			}

			return false;
		}

		return self::_url(getservbyport(self::_value($_SERVER, 'SERVER_PORT', 80), 'tcp') . '://' . self::_value($_SERVER, 'HTTP_HOST') . self::_value($_SERVER, 'REQUEST_URI'), $path, $query);
	}

	public static function _value($data, $key = null, $default = false)
	{
		if (isset($key) === true)
		{
			foreach ((array) $key as $value)
			{
				$data = (is_object($data) === true) ? get_object_vars($data) : $data;

				if ((is_array($data) !== true) || (array_key_exists($value, $data) !== true))
				{
					return $default;
				}

				$data = $data[$value];
			}
		}

		return $data;
	}

	public static function _voodoo($data)
	{
		if ((version_compare(PHP_VERSION, '6.0.0', '<') === true) && (get_magic_quotes_gpc() === 1))
		{
			if (is_array($data) === true)
			{
				$result = array();

				foreach ($data as $key => $value)
				{
					$result[self::_voodoo($key)] = self::_voodoo($value);
				}

				return $result;
			}

			return (is_string($data) === true) ? stripslashes($data) : $data;
		}

		return $data;
	}

	public static function call($url, $method, $payload = null)
	{
		$url = sprintf('http://api.mygengo.com/v1/%s', trim($url, '/'));
		$headers = array(CURLOPT_HTTPHEADER => array('Accept: application/json'));

		if (self::$production != true)
		{
			$url = str_replace('http://api.mygengo.com/', 'http://api.sandbox.mygengo.com/', $url);
		}

		if (isset(self::$public, self::$private) === true)
		{
			$data = array
			(
				'ts' => strval(time()),
				'api_key' => self::$public,
				'_method' => strtolower($method),
			);

			if ((isset($payload) === true) && (count($payload) != count($payload, COUNT_RECURSIVE)))
			{
				$payload = array('data' => json_encode($payload));
			}

			$data = array_filter(array_merge($data, self::_unicode((array) $payload)), 'count');

			if (ksort($data) === true)
			{
				$hmac = http_build_query($data, '', '&');

				if (in_array(strtoupper($method), array('PUT', 'POST')) === true)
				{
					$hmac = json_encode($data);
				}

				$data['api_sig'] = hash_hmac('sha1', $hmac, self::$private);
			}
		}

		if (($result = self::_curl($url, $data, $method, $headers)) !== false)
		{
			if (is_array($json = json_decode($result, true)) === true)
			{
				if (array_key_exists('err', $json) === true)
				{
					throw new myGengo_Exception(implode('|', (array) $json['err']['msg']), $json['err']['code']);
				}

				$result = (empty($json['response']) === true) ? true : $json['response'];
			}
		}

		return $result;
	}

	public static function job($id = null)
	{
		if (is_object($job = new ReflectionClass('myGengo_Job')) === true)
		{
			$job = $job->newInstance($id);
		}

		return $job;
	}

	public static function jobs($id = null)
	{
		if (is_object($job = new ReflectionClass('myGengo_Jobs')) === true)
		{
			$job = $job->newInstance($id);
		}

		return $job;
	}
}

class myGengo_Account extends myGengo
{
	public function __construct()
	{
	}

	public static function balance()
	{
		$url = 'account/balance';
		$method = 'GET';

		return parent::call($url, $method);
	}

	public static function statistics()
	{
		$url = 'account/stats';
		$method = 'GET';

		return parent::call($url, $method);
	}
}

class myGengo_Job extends myGengo
{
	protected static $id = null;

	public function __construct($id = null)
	{
		self::$id = $id;
	}

	public static function comment($comment)
	{
		$url = sprintf('translate/job/%u/comment', self::$id);
		$method = 'POST';
		$payload = json_encode(array('body' => $comment));

		return parent::call($url, $method, array_filter(array('data' => $payload)));
	}

	public static function comments()
	{
		$url = sprintf('translate/job/%u/comments', self::$id);
		$method = 'GET';

		return parent::call($url, $method);
	}

	public static function delete()
	{
		$url = sprintf('translate/job/%u', self::$id);
		$method = 'DELETE';

		return parent::call($url, $method);
	}

	public static function feedback()
	{
		$url = sprintf('translate/job/%u/feedback', self::$id);
		$method = 'GET';

		return parent::call($url, $method);
	}

	public static function get($machine = null)
	{
		$url = sprintf('translate/job/%u', self::$id);
		$method = 'GET';
		$payload = array('pre_mt' => intval($machine));

		return parent::call($url, $method, array_filter($payload));
	}

	public static function post($data)
	{
		$url = 'translate/job';
		$method = 'POST';

		$fields = array
		(
			'body_src' => 1350,
			'lc_src' => 1400,
			'lc_tgt' => 1450,
			'tier' => 1500,
			'auto_approve' => null,
			'callback_url' => null,
			'comment' => null,
			'custom_data' => null,
			'use_preferred' => null,
		);

		foreach (array_filter($fields) as $key => $value)
		{
			if ((is_array($data) !== true) || (array_key_exists($key, $data) !== true) || (empty($data[$key]) === true))
			{
				throw new myGengo_Exception(sprintf('%s is a required field', $key), $value);
			}
		}

		return parent::call($url, $method, array('job' => array_filter($data)));
	}

	public static function preview()
	{
		$url = sprintf('translate/job/%u/preview', self::$id);
		$method = 'GET';

		if (parent::call($url, 'HEAD') !== false)
		{
			return parent::call($url, $method);
		}

		return false;
	}

	public static function put($action, $data = null)
	{
		$url = sprintf('translate/job/%u', self::$id);
		$method = 'PUT';

		if (in_array($action, array('approve', 'purchase', 'reject', 'revise')) === true)
		{
			$fields = array();

			if ($action == 'revise')
			{
				$fields = array
				(
					'comment' => 2300,
				);
			}

			else if ($action == 'reject')
			{
				$fields = array
				(
					'comment' => 2300,
					'reason' => 2350,
					'captcha' => null,
					'follow_up' => null,
				);
			}

			else if ($action == 'approve')
			{
				$fields = array
				(
					'rating' => 2500,
					'for_translator' => null,
					'for_mygengo' => null,
					'public' => null,
				);
			}

			foreach (array_filter($fields) as $key => $value)
			{
				if ((is_array($data) !== true) || (array_key_exists($key, $data) !== true) || (empty($data[$key]) === true))
				{
					throw new myGengo_Exception(sprintf('%s is a required field', $key), $value);
				}
			}

			$payload = array_merge(array('action' => $action), array_intersect_key((array) $data, $fields));

			return parent::call($url, $method, array('data' => json_encode($payload)));
		}

		return false;
	}

	public static function revision($id = null)
	{
		$url = sprintf('translate/job/%u/revision/%u', self::$id, $id);
		$method = 'GET';

		return parent::call($url, $method);
	}

	public static function revisions()
	{
		$url = sprintf('translate/job/%u/revisions', self::$id);
		$method = 'GET';

		return parent::call($url, $method);
	}
}

class myGengo_Jobs extends myGengo
{
	protected static $id = null;

	public function __construct($id = null)
	{
		self::$id = $id;
	}

	public static function filter($count = null, $since = null, $status = null)
	{
		$url = 'translate/jobs';
		$method = 'GET';
		$payload = array
		(
			'status' => $status,
			'timestamp_after' => $since,
			'count' => $count,
		);

		return parent::call($url, $method, array_filter($payload, 'count'));
	}

	public static function get()
	{
		$url = sprintf('translate/jobs/%u', self::$id);
		$method = 'GET';

		return parent::call($url, $method);
	}
}

class myGengo_Service extends myGengo
{
	public function __construct()
	{
	}

	public static function languagePairs($input = null)
	{
		$url = 'translate/service/language_pairs';
		$method = 'GET';
		$payload = array('lc_src' => $input);

		return parent::call($url, $method, array_filter($payload));
	}

	public static function languages()
	{
		$url = 'translate/service/languages';
		$method = 'GET';

		return parent::call($url, $method);
	}

	public static function quote($data)
	{
		$url = 'translate/service/quote';
		$method = 'POST';

		$data = array
		(
			'jobs' => array
			(
				'job_1' => $data,
			),
		);

		return parent::call($url, $method, $data);
	}
}

class myGengo_Exception extends Exception
{
}

?>