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
		$this->setCategory('light',1);
		$this->loadCmdFromConf($this->getConfiguration('type'));
	}

}

class hclCmd extends cmd {

	public function postSave() {
		if ($this->getType() == 'info') {
			$this->event($this->execute());
		}
	}

	public function preSave() {
		if ($this->getType() == "info") {
			if (strpos($this->getEqLogic()->getConfiguration('eqLogic'), '&&')) {
				$ids = explode('&&', $this->getEqLogic()->getConfiguration('eqLogic'));
				$id = $ids[0];
			} else {
				$id = $this->getEqLogic()->getConfiguration('eqLogic');
			}
			$id = str_replace("#", "", str_replace("eqLogic", "", $id));
			$cmd = cmd::byEqLogicIdAndGenericType($id, $this->getLogicalId());
			if (is_object($cmd)) {$this->setValue('#' . $cmd->getId() . '#');}
		}
	}

	public function execute($_options = null) {
		if ($this->getType() == "info") {
			if (strpos($this->getEqLogic()->getConfiguration('eqLogic'), '&&')) {
				$ids = explode('&&', $this->getEqLogic()->getConfiguration('eqLogic'));
				$id = $ids[0];
			} else {
				$id = $this->getEqLogic()->getConfiguration('eqLogic');
			}
			$id = str_replace("#", "", str_replace("eqLogic", "", $id));
			$cmd = cmd::byEqLogicIdAndGenericType($id, $this->getLogicalId());
			if (is_object($cmd)) {return $cmd->execCmd();}
		} else {
			$type = $this->getLogicalId();
			//manage special ON/OFF that power on the lamp whatever light sensor or restriction

			//manage a command send for a defined "ambiance"
			if ($type == 'LIGHT_MODE') {
				$type == 'LIGHT_SLIDER';
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
			}
			if (strpos($this->getEqLogic()->getConfiguration('eqLogic'), '&&')) {
				log::add('hcl', 'info', 'Multiples eqLogic');
				foreach (explode('&&', $this->getEqLogic()->getConfiguration('eqLogic')) as $id) {
					$this->triggerLight($id,$type,$_options);
				}
			} else {
				$this->triggerLight($this->getEqLogic()->getConfiguration('eqLogic'),$type,$_options);
			}
		}
	}

	public function triggerLight($_id, $_type, $_options = null) {
		$id = str_replace("#", "", str_replace("eqLogic", "", $_id));
		log::add('hcl', 'info', 'Lancement commande sur eqLogic : ' . $id);
		//$eqLogic = eqLogic::byId($id);
		//if (!is_object($eqLogic)) {return ;}
		if ($_type == 'refresh') {
			$cmd = cmd::byEqLogicIdAndLogicalId($id,'refresh');
		} else {
			$cmd = cmd::byEqLogicIdAndGenericType($id,$_type);
		}
		if (is_object($cmd)) {$cmd->execCmd($_options);}
	}
}
?>
