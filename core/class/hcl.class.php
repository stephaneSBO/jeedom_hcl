<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class hcl extends eqLogic {

	public function loadCmdFromConf($type) {
		if (!is_file(dirname(__FILE__) . '/../config/devices/' . $type . '.json')) {
			return;
		}
		$content = file_get_contents(dirname(__FILE__) . '/../config/devices/' . $type . '.json');
		if (!is_json($content)) {
			return;
		}
		$device = json_decode($content, true);
		if (!is_array($device) || !isset($device['commands'])) {
			return true;
		}
		foreach ($device['commands'] as $command) {
			$cmd = null;
			foreach ($this->getCmd() as $liste_cmd) {
				if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId'])
				|| (isset($command['name']) && $liste_cmd->getName() == $command['name'])) {
					$cmd = $liste_cmd;
					break;
				}
			}
			if ($cmd == null || !is_object($cmd)) {
				$cmd = new hclCmd();
				$cmd->setEqLogic_id($this->getId());
				utils::a2o($cmd, $command);
				$cmd->save();
			}
		}
	}

	public function postAjax() {
		$this->loadCmdFromConf('hcl');
	}

}

class hclCmd extends cmd {
	public function execute($_options = null) {
		$id = str_replace("#", "", str_replace("eqLogic", "", $this->getEqLogic()->getConfiguration('eqLogic')));
		if ($this->getLogicalId() == 'refresh') {
			$eqLogic = eqLogic::byId($id);
			$cmd = cmd::byEqLogicIdAndLogicalId($id,'refresh');
		} else if ($this->getLogicalId() == 'LIGHT_MODE') {
			$eqLogic = eqLogic::byId($id);
			$cmd = cmd::byEqLogicIdAndGenericType($id,'LIGHT_SLIDER');
			switch ($_options['select']) {
				case '1':
					$_options['slider'] = 2500;
					break;
				case '2':
					$_options['slider'] = 3700;
					break;
				case '3':
					$_options['slider'] = 5000;
					break;
				case '4':
					$_options['slider'] = 5700;
					break;
				case '5':
					$_options['slider'] = 6500;
					break;
				case '6':
				//a modifier
					$_options['slider'] = 6000;
					break;
			}
		} else {
			$cmd = cmd::byEqLogicIdAndGenericType($id,$this->getLogicalId());
		}
		if (is_object($cmd)) {$cmd->execCmd($_options);}
	}
}
?>
