<?php
/**
 * A CakePHP Plugin by Loïc Boutter (http://www.loicboutter.fr)
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Loïc Boutter (http://www.loicboutter.fr)
 * @link          http://www.loicboutter.fr
 * @package       FlatAcl.Component
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */


App::uses('Component', 'Controller');

class FlatAclComponent extends Component {

	/**
	 * Contains the FlatAcl.Permission Model
	 */
	public $Permission;

	
	/**
	 * Constructor, initialize the Permission model
	 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);

		App::uses('Permission', 'FlatAcl.Model');
		$this->Permission = new Permission();
	}


	/**
	 * Check whether one of some AROs can access an ACO
	 *
	 * @param array|string $aros AROs requesting, format: ['model' => ..., 'id' => [...]] or ['alias1', 'alias2', ...] or string(alias)
	 * @param array|string $aco  ACO requested, format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param string $action Action to specifically check for. Default: '*'
	 * @return bool Success
	 */
	public function check($aros, $aco, $action = '*') {
		return $this->Permission->check($aros, $aco, $action);
	}


	/**
	 * Grant an ARO access to an ACO
	 *
	 * @param array|string $aro ARO requesting, format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param array|string $aco ACO requested,  format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param array|string $actions Actions to specifically grant access. Default: '*'
	 * @return bool Success
	 */
	public function allow($aro, $aco, $actions = '*') {
		return $this->Permission->allow($aro, $aco, $actions, true);
	}


	/**
	 * Deny an ARO access to an ACO
	 *
	 * @param array|string $aro ARO requesting, format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param array|string $aco ACO requested,  format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param array|string $actions Actions to specifically deny access. Default: '*'
	 * @return bool Success
	 */
	public function deny($aro, $aco, $actions = '*') {
		return $this->Permission->allow($aro, $aco, $actions, false);
	}

	
}