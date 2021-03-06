<?php
/**
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\Encryption\Tests\Controller;


use OCA\Encryption\Controller\RecoveryController;
use OCP\AppFramework\Http;
use Test\TestCase;

class RecoveryControllerTest extends TestCase {
	/**
	 * @var RecoveryController
	 */
	private $controller;
	private $appName;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $requestMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $configMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $l10nMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $recoveryMock;

	public function adminRecoveryProvider() {
		return [
			['test', 'test', '1', 'Recovery key successfully enabled', HTTP::STATUS_OK],
			['', 'test', '1', 'Missing recovery key password', HTTP::STATUS_BAD_REQUEST],
			['test', '', '1', 'Please repeat the recovery key password', HTTP::STATUS_BAD_REQUEST],
			['test', 'soimething that doesn\'t match', '1', 'Repeated recovery key password does not match the provided recovery key password', HTTP::STATUS_BAD_REQUEST],
			['test', 'test', '0', 'Recovery key successfully disabled', HTTP::STATUS_OK],
		];
	}

	/**
	 * @dataProvider adminRecoveryProvider
	 * @param $recoveryPassword
	 * @param $passconfirm
	 * @param $enableRecovery
	 * @param $expectedMessage
	 * @param $expectedStatus
	 */
	public function testAdminRecovery($recoveryPassword, $passconfirm, $enableRecovery, $expectedMessage, $expectedStatus) {


		$this->recoveryMock->expects($this->any())
			->method('enableAdminRecovery')
			->willReturn(true);

		$this->recoveryMock->expects($this->any())
			->method('disableAdminRecovery')
			->willReturn(true);

		$response = $this->controller->adminRecovery($recoveryPassword,
			$passconfirm,
			$enableRecovery);


		$this->assertEquals($expectedMessage, $response->getData()['data']['message']);
		$this->assertEquals($expectedStatus, $response->getStatus());


	}

	public function changeRecoveryPasswordProvider() {
		return [
			['test', 'test', 'oldtestFail', 'Could not change the password. Maybe the old password was not correct.', HTTP::STATUS_BAD_REQUEST],
			['test', 'test', 'oldtest', 'Password successfully changed.', HTTP::STATUS_OK],
			['test', 'notmatch', 'oldtest', 'Repeated recovery key password does not match the provided recovery key password', HTTP::STATUS_BAD_REQUEST],
			['', 'test', 'oldtest', 'Please provide a new recovery password', HTTP::STATUS_BAD_REQUEST],
			['test', 'test', '', 'Please provide the old recovery password', HTTP::STATUS_BAD_REQUEST]
		];
	}

	/**
	 * @dataProvider changeRecoveryPasswordProvider
	 * @param $password
	 * @param $confirmPassword
	 * @param $oldPassword
	 * @param $expectedMessage
	 * @param $expectedStatus
	 */
	public function testChangeRecoveryPassword($password, $confirmPassword, $oldPassword, $expectedMessage, $expectedStatus) {
		$this->recoveryMock->expects($this->any())
			->method('changeRecoveryKeyPassword')
			->with($password, $oldPassword)
			->will($this->returnValueMap([
				['test', 'oldTestFail', false],
				['test', 'oldtest', true]
			]));

		$response = $this->controller->changeRecoveryPassword($password,
			$oldPassword,
			$confirmPassword);

		$this->assertEquals($expectedMessage, $response->getData()['data']['message']);
		$this->assertEquals($expectedStatus, $response->getStatus());


	}

	public function userSetRecoveryProvider() {
		return [
			['1', 'Recovery Key enabled', Http::STATUS_OK],
			['0', 'Could not enable the recovery key, please try again or contact your administrator', Http::STATUS_BAD_REQUEST]
		];
	}

	/**
	 * @dataProvider userSetRecoveryProvider
	 * @param $enableRecovery
	 * @param $expectedMessage
	 * @param $expectedStatus
	 */
	public function testUserSetRecovery($enableRecovery, $expectedMessage, $expectedStatus) {
		$this->recoveryMock->expects($this->any())
			->method('setRecoveryForUser')
			->with($enableRecovery)
			->will($this->returnValueMap([
				['1', true],
				['0', false]
			]));


		$response = $this->controller->userSetRecovery($enableRecovery);

		$this->assertEquals($expectedMessage, $response->getData()['data']['message']);
		$this->assertEquals($expectedStatus, $response->getStatus());

	}

	protected function setUp() {
		parent::setUp();

		$this->appName = 'encryption';
		$this->requestMock = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();

		$this->configMock = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();

		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();

		// Make l10n work in our tests
		$this->l10nMock->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->recoveryMock = $this->getMockBuilder('OCA\Encryption\Recovery')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new RecoveryController($this->appName,
			$this->requestMock,
			$this->configMock,
			$this->l10nMock,
			$this->recoveryMock);
	}

}
