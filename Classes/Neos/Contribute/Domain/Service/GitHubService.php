<?php
 /***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Daniel Lienert <lienert@punkt.de>
 *
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Neos\Contribute\Domain\Service;

use TYPO3\Flow\Annotations as Flow;
use Github\Client as GitHubClient;
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use Github\Exception\RuntimeException;
use TYPO3\Flow\Utility\Arrays;

class GitHubService {


	/**
	* @Flow\Inject(setting="gitHub")
	*/
	protected $gitHubSettings;


	/**
	 * @var GitHubClient
	 */
	protected $gitHubClient;


	/**
	 * @var array
	 */
	protected $repositoryConfigurationCollection = NULL;


	public function initializeObject() {
		$this->gitHubClient = new GithubClient();
	}


	public function authenticate() {
		$gitHubAccessToken = (string) Arrays::getValueByPath($this->gitHubSettings, 'contributor.accessToken');

		if(!$gitHubAccessToken) {
			throw new InvalidConfigurationException('The GitHub access token was not configured.', 1439627205);
		}

		$this->gitHubClient->authenticate($gitHubAccessToken, GithubClient::AUTH_HTTP_TOKEN);

		try {
			$this->gitHubClient->currentUser()->show();
		} catch (RuntimeException $exception) {
			throw new InvalidConfigurationException('It was not possible to authenticate to GitHub: ' . $exception->getMessage(), $exception->getCode());
		}
	}


	/**
	 * @param string $organization
	 * @param string $repositoryName
	 * @return \Guzzle\Http\EntityBodyInterface|mixed|string
	 */
	public function forkRepository($organization, $repositoryName) {
		$this->authenticate();
		return $this->gitHubClient->repo()->forks()->create($organization, $repositoryName);
	}


	/**
	 * @param $repositoryName
	 * @return bool
	 */
	public function checkRepositoryExists($repositoryName) {
		return count($this->getRepositoryConfiguration($repositoryName)) ? TRUE : FALSE;
	}


	/**
	 * @param $repository
	 * @param $key
	 * @return mixed
	 */
	public function getRepositoryConfigurationProperty($repository, $key) {
		return Arrays::getValueByPath($this->getRepositoryConfiguration($repository), $key);
	}


	/**
	 * @param $repositoryName
	 * @return array
	 * @throws InvalidConfigurationException
	 */
	public function getRepositoryConfiguration($repositoryName) {
		$this->authenticate();

		if($this->repositoryConfigurationCollection === NULL) {
			$this->repositoryConfigurationCollection = $this->gitHubClient->currentUser()->repositories();
		}

		foreach($this->repositoryConfigurationCollection as $repository) {
			if($repository['name'] == $repositoryName) {
				return $repository;
			}
		}

		return array();
	}


	/**
	 * @param mixed $gitHubSettings
	 * @return $this
	 */
	public function setGitHubSettings($gitHubSettings) {
		$this->gitHubSettings = $gitHubSettings;
		return $this;
	}
}