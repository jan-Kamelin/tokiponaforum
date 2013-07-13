<?php
/**
*
* @package auth
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\Uri;

/**
* OAuth authentication provider for phpBB3
*
* @package auth
*/
class phpbb_auth_provider_oauth extends phpbb_auth_provider_base
{
	/**
	* Database driver
	*
	* @var phpbb_db_driver
	*/
	protected $db;

	/**
	* phpBB config
	*
	* @var phpbb_config
	*/
	protected $config;

	/**
	* phpBB request object
	*
	* @var phpbb_request
	*/
	protected $request;

	/**
	* phpBB user
	*
	* @var phpbb_user
	*/
	protected $user;

	/**
	* Cache driver.
	*
	* @var phpbb_cache_driver_interface
	*/
	protected $driver;

	/**
	* OAuth Authentication Constructor
	*
	* @param 	phpbb_db_driver 				$db
	* @param 	phpbb_config 					$config
	* @param 	phpbb_request 					$request
	* @param 	phpbb_user 						$user
	* @param	phpbb_cache_driver_interface	$driver
	*/
	public function __construct(phpbb_db_driver $db, phpbb_config $config, phpbb_request $request, phpbb_user $user, phpbb_cache_driver_interface $driver)
	{
		$this->db = $db;
		$this->config = $config;
		$this->request = $request;
		$this->user = $user;
		$this->driver = $driver;
	}

	/**
	* {@inheritdoc}
	*/
	public function login($username, $password)
	{
		// Requst the name of the OAuth service
		$service = $this->request->variable('oauth_service', '', false, phpbb_request_interface::POST);
		if ($service === '')
		{
			return array(
				'status'		=> LOGIN_ERROR_EXTERNAL_AUTH,
				'error_msg'		=> 'LOGIN_ERROR_EXTERNAL_AUTH_APACHE',
				'user_row'		=> array('user_id' => ANONYMOUS),
			);
		}

		// Get the service credentials for the given service
		$service_credentials = $this->get_credentials($service);

		// Check that the service has settings
		if ($service_credentials['key'] == false || $service_credentials['secret'] == false)
		{
			return array(
				'status'		=> LOGIN_ERROR_EXTERNAL_AUTH,
				'error_msg'		=> 'LOGIN_ERROR_EXTERNAL_AUTH_APACHE',
				'user_row'		=> array('user_id' => ANONYMOUS),
			);
		}

		$service_factory = new \OAuth\ServiceFactory();
		$uri_factory = new \OAuth\Common\Http\Uri\UriFactory();
		$current_uri = $uri_factory->createFromSuperGlobalArray($this->request->get_super_global(phpbb_request_interface::SERVER));
		$current_uri->setQuery('');

		// In-memory storage
		$storage = new phpbb_auth_oauth_token_storage($this->driver);

		// Setup the credentials for the requests
		$credentials = new Credentials(
			$service_credentials['key'],
			$service_credentials['secret'],
			$current_uri->getAbsoluteUri()
		);

		if ($this->request->is_set('code', phpbb_request_interface::GET))
		{
			// Second pass: request access token, authenticate with phpBB
		} else {
			// First pass: get authorization uri, redirect to service
		}
	}

	/**
	*
	*/
	protected function get_service_credentials($service)
	{
		return array(
			'key'		=> $this->config['auth_oauth_' . $service . '_key'],
			'secret'	=> $this->config['auth_oauth_' . $service . '_secret'],
		);
	}
}
