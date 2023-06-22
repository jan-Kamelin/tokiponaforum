<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

require_once __DIR__ . '/../test_framework/phpbb_session_test_case.php';

class phpbb_session_garbage_collection_test extends phpbb_session_test_case
{
	public $session;

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/fixtures/sessions_garbage.xml');
	}

	protected function setUp(): void
	{
		parent::setUp();
		$this->session = $this->session_factory->get_session($this->db);

		global $phpbb_container;

		$plugins = new \phpbb\di\service_collection($phpbb_container);
		$plugins->add('core.captcha.plugins.nogd');
		$phpbb_container->set(
			'captcha.factory',
			new \phpbb\captcha\factory($phpbb_container, $plugins)
		);
		$phpbb_container->set(
			'core.captcha.plugins.nogd',
			new \phpbb\captcha\plugins\nogd()
		);
	}

	public function test_session_gc()
	{
		global $config;
		$config['session_length'] = 3600;

		$this->check_expired_sessions_recent(
			[
				[
					'session_user_id' => 4,
					'recent_time' => 1500000000,
				],
				[
					'session_user_id' => 5,
					'recent_time' => 1500000000,
				],
			],
			'Before test, should get recent expired sessions only.'
		);

		// There is an error unless the captcha plugin is set
		$config['captcha_plugin'] = 'core.captcha.plugins.nogd';
		$this->session->session_gc();
		$this->check_expired_sessions_recent(
			[],
			'After garbage collection, all expired sessions should be removed.'
		);
	}

	public function test_cleanup_all()
	{
		$this->check_sessions_equals(
			[
				[
					'session_id' => 'anon_session00000000000000000000',
					'session_user_id' => 1,
				],
				[
					'session_id' => 'bar_session000000000000000000000',
					'session_user_id' => 4,
				],
				[
					'session_id' => 'bar_session000000000000000000002',
					'session_user_id' => 4,
				],
				[
					'session_id' => 'foo_session000000000000000000000',
					'session_user_id' => 5,
				],
				[
					'session_id' => 'foo_session000000000000000000002',
					'session_user_id' => 5,
				],
			],
			'Before test, should have some sessions.'
		);
		// Set session length so it clears all
		global $config;
		$config['session_length'] = 0;
		// There is an error unless the captcha plugin is set
		$config['captcha_plugin'] = 'core.captcha.plugins.nogd';
		$this->session->session_gc();
		$this->check_sessions_equals(
			[],
			'After setting session time to 0, should remove all.'
		);
	}
}
