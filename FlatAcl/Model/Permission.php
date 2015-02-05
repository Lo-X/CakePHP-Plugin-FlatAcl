<?php
App::uses('AppModel', 'Model');

class Permission extends AppModel {

	/**
	 * Explicitly disable in-memory query caching
	 *
	 * @var bool
	 */
	public $cacheQueries = false;

	/**
	 * Override default table name
	 *
	 * @var string
	 */
	public $useTable = 'aros_acos';

	/**
	 * Permissions link AROs with ACOs
	 *
	 * @var array
	 */
	public $belongsTo = array('Aro', 'Aco');

	/**
	 * No behaviors for this model
	 *
	 * @var array
	 */
	public $actsAs = null;

	/**
	 * We need to be recursive
	 *
	 * @var array
	 */
	public $recursive = 0;



	/**
	 * Check whether one of some AROs can access an ACO
	 *
	 * @param array|string $aros AROs requesting, format: ['model' => ..., 'id' => [...]] or ['alias1', 'alias2', ...] or string(alias)
	 * @param array|string $aco  ACO requested, format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param string $action Action to specifically check for. Default: '*'
	 * @return bool Success
	 */
	public function check($aros, $aco, $action = '*') {
		if(!$aros || !$aco) {
			return false;
		}

		if(!is_string($action)) {
			throw new AclException(__d('cake_dev', 'Invalid action parameter'));
		}

		$permissionKeys = $this->getAcoKeys($this->schema());	// Retrieve actions in table schema
		$permissions = $this->getAclLink($aros, $aco); 			// Retrieve ACO data, including the list of AROs and their permissions


		// No data ? No access
		if(empty($permissions)) {
			return false;
		}

		if($action != '*' && !in_array('_'.$action, $permissionKeys)) {
			return false;
		}

		// Check each possible permission to see if one matches the action
		foreach ($permissions as $permission) {
			if($action == '*') {
				$allowances = [];
				foreach ($permissionKeys as $pkey) {
					if(isset($permission[$this->alias][$pkey]) && $permission[$this->alias][$pkey] == 1) {
						$allowances[$pkey] = 1;
					} else {
						break;
					}
				}

				if(count($allowances) == count($permissionKeys)) {
					return true;
				}
			} else {
				if($permission[$this->alias]["_{$action}"] == 1) {
					return true;
				}
			}
		}

		return false; // At that point, we return false anyway
	}


	/**
	 * Grant an ARO access to an ACO
	 *
	 * @param array|string $aro ARO requesting, format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param array|string $aco ACO requested,  format: ['model' => ..., 'id' => ...] or string(alias)
	 * @param array|string $actions Actions to specifically grant or deny access. Default: '*'
	 * @param bool $value Value to indicate access type
	 * @return bool Success
	 */
	public function allow($aro, $aco, $actions = '*', $value = true) {
		$permissionKeys = $this->getAcoKeys($this->schema());	// Retrieve actions in table schema
		$permissions = $this->getAclLink($aro, $aco); 			// Retrieve ACO data, including the list of AROs and their permissions
		$save = [];

		// If permissions have already been set, take it as a base
		if(!empty($permissions[0][$this->alias])) {
			$save = $permissions[0][$this->alias];
		}

		// If actions is '*', we fill the save array with $value
		if($actions == '*') {
			$save = array_combine($permissionKeys, array_pad(array(), count($permissionKeys), $value));
		} else {
			if(!is_array($actions)) {
				$actions = array('_'.$actions);
			}

			foreach($actions as $action) {
				if($action{0} !== '_') {
					$action = '_'.$action;
				}

				if(!in_array($action, $permissionKeys, true)) {
					throw new AclException(__d('cake_dev', 'Invalid permission key "%s"', $action));
				}

				$save[$action] = $value;
			}
		}

		// Save or update data
		if(empty($permissions[0][$this->alias])) {
			unset($save['id']);
			$this->id = null;
			$save['aro_id'] = $permissions[0][$this->Aro->alias]['id'];
			$save['aco_id'] = $permissions[0][$this->Aco->alias]['id'];
		}

		return ($this->save($save) !== false);
	}


	/**
	 * Get an array of access-control links between the given AROs and ACO
	 *
	 * @param array|string $aros AROs requesting, format: ['model' => ..., 'id' => [...]] or ['alias1', 'alias2', ...] or string(alias)
	 * @param array|string $aco  ACO requested, format: ['model' => ..., 'id' => ...] or string(alias)
	 * @return array Indexed array with sub arrays for: 'Aro', 'Aco' and 'Permission'
	 */
	public function getAclLink($aros, $aco) {
		$aroConditions = [];
		$acoConditions = [];

		// Construct conditions for AROs
		if(is_array($aros)) {
			if(array_key_exists('id', $aros) && array_key_exists('model', $aros)) {
				$aroConditions = [
					"{$this->Aro->alias}.model" => $aros['model'],
					"{$this->Aro->alias}.id" 	=> $aros['id']
				];
			} else {
				// It's an arry of aliases, format $aros for 'conditions' clause
				$aroConditions = ["{$this->Aro->alias}.alias" => $aros];
			}
		} elseif(is_string($aros)) {
			// It's an alias, alone
			$aroConditions = ["{$this->Aro->alias}.alias" => $aros];
		} else {
			throw new AclException(__d('cake_dev', 'Invalid AROs parameter'));
		}

		// Construct conditions for ACO
		if(is_array($aco)) {
			if(array_key_exists('id', $aco) && array_key_exists('model', $aco)) {
				$acoConditions = [
					"{$this->Aco->alias}.model" => $aco['model'],
					"{$this->Aco->alias}.id" 	=> $aco['id']
				];
			} else {
				throw new AclException(__d('cake_dev', 'Invalid ACO parameter'));
			}
		} elseif(is_string($aco)) {
			$acoConditions = ["{$this->Aco->alias}.alias" => $aco];
		} else {
			throw new AclException(__d('cake_dev', 'Invalid ACO parameter'));
		}

		$this->Aro->recursive = -1;
		$this->Aco->recursive = -1;
		$linkData = $this->find('all', [
			'conditions' => ($aroConditions + $acoConditions)
		]);

		if(empty($linkData)) {
			$linkData[0] = [$this->alias => []];
			$aroData = $this->Aro->find('first', ['fields' => 'Aro.*', 'conditions' => $aroConditions]);
			$acoData = $this->Aco->find('first', ['fields' => 'Aco.*', 'conditions' => $acoConditions]);
		
			if(empty($aroData)) {
				throw new AclException(__d('cake_dev', 'Invalid AROs parameter, AROs not found'));
			} else {
				$linkData[0][$this->Aro->alias] = current($aroData);
			}

			if(empty($acoData)) {
				throw new AclException(__d('cake_dev', 'Invalid ACO parameter, ACO not found'));
			} else {
				$linkData[0][$this->Aco->alias] = current($acoData);
			}
		}

		return $linkData;
	}

	/**
	 * Get the crud type keys
	 *
	 * @param array $keys Permission schema
	 * @return array permission keys
	 */
	public function getAcoKeys($schema) {
		$newKeys = array();
		$keys = array_keys($schema);
		foreach ($keys as $key) {
			if (!in_array($key, array('id', 'aro_id', 'aco_id'))) {
				$newKeys[] = $key;
			}
		}
		return $newKeys;
	}
}
