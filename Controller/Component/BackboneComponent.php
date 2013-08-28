<?php

App::uses('Inflector', 'Utility');

/*
 *	A compoonent to make sure JSON gets returned in the way that backbone likes it
 */
Class BackboneComponent extends Component {
	private $_backbone = false;

/**
 * Sets the view to that of the json type
 *
 * @param $controller object The controller object
 * @return void
 */
	public function startup(Controller $controller) {
		if (!$controller->RequestHandler->isAjax() && !$this->_isJsonRequest($controller)) {
			return;
		}
		$controller->view = 'CakephpBackbone./Backbone/json';
	}

	protected function _isJsonRequest(Controller $controller) {
		$extensionSet = (isset($controller->request->params['ext']));
		if ($extensionSet) {
			return ($controller->request->params['ext'] == 'json');
		}
		return false;
	}

/**
 * Sets the parameters of the view escaping cake's default of
 * assigning them as a keyed multi-dimensional array.
 *
 * @param $controller object The controller object
 * @return void
 */
	public function beforeRender(Controller $controller) {
		if (!$controller->RequestHandler->isAjax() && !$this->_isJsonRequest($controller)) {
			return;
		}

		$this->_prepareData($controller, '', $paging, $object);

		// respond as application/json in the headers
		$controller->RequestHandler->respondAs('json');
		$callback = $this->_hasCallback($controller);
		if ($callback) {
			if (!empty($paging)) {
				$data = array('paging' => $paging, 'data' => $object);
			} else {
				$data = $object;
			}
			$controller->autoRender = false;
			echo $callback."(".json_encode($data).");";
		}
	}

/**
 * Call this for customized controller actions 
 * that are NOT `index`
 * `view`, `add`, `edit`, `delete`
 *
 * @param $controller object The controller object
 * @return void
 */
	public function customizeBeforeRender(Controller $controller, $action) {
		if (!$controller->RequestHandler->isAjax() && !$this->_isJsonRequest($controller)) {
			return;
		}

		$this->_prepareData($controller, $action, $paging, $object);

		// respond as application/json in the headers
		$controller->RequestHandler->respondAs('json');
		$callback = $this->_hasCallback($controller);
		if ($callback) {
			if (!empty($paging)) {
				$data = array('paging' => $paging, 'data' => $object);
			} else {
				$data = $object;
			}
			$controller->autoRender = false;
			echo $callback."(".json_encode($data).");";
		}
	}

/**
 *
 * preparing the JSON data for consumption by backbone
 *
 * @param $controller object The controller object 
 * @param $action String optional. The action type that will determine the format of data returned
 * @param &$paging Pass-by-reference Paging array
 * @param &$object Pass-by-reference the data array
 */
	protected function _prepareData(Controller $controller, $similarActionType = '', &$paging, &$object) {
		$controllerName = $controller->request->params['controller'];
		$singular = Inflector::singularize($controllerName);
		$action = (empty($similarActionType)) ? $controller->request->params['action'] : $similarActionType;
		$modelName = Inflector::camelize($singular);
		switch ($action) {
			case 'index': 
				$param = $controllerName;
				break;
			case 'add':
				$param = $singular;
				break;
			case 'edit':
				$param = $singular;
				break;
			case 'delete':
				return;
				break;
			case 'view':
				$object = $singular;
				break;
			default:
				return;
				break;
		}
		$paging = array();
		if (!isset($object) && isset($param)) {
			if (isset($controller->viewVars[$param][0][$modelName])) {
				$object = array_map(function($row) use ($modelName) {
					foreach($row as $index => $value) {
						if ($index != $modelName) {
							$row[$modelName][$index] = $value;
						}
					}
					return $row[$modelName];
				}, $controller->viewVars[$param]);
				$controller->set('object', $object);
			}
			elseif (isset($controller->viewVars[$param][$modelName])) {
				$singleRecordData = $controller->viewVars[$param];
				foreach($singleRecordData as $index => $value) {
					if ($index != $modelName) {
						$singleRecordData[$modelName][$index] = $value;
					}
				}
				$object = $singleRecordData[$modelName];
				$controller->set('object', $object);

			} else {
				$object = $controller->viewVars[$param];
				$controller->set('object', $object);
			}
		} elseif(isset($object)) {
			$controller->set('object', $object);
		}

		if (isset($controller->request['paging'][$modelName])) {
			$paging = $controller->request['paging'][$modelName];
			$controller->set('paging', $paging);
		}
	}


/**
 * Checks for callback parameter and returns it. Mainly for jsonp callback.
 * Assume it as $callback
 *
 * @param $controller object The controller object
 * @return string/boolean Return the callback value in the $_GET if exist else return false
 */
	protected function _hasCallback(Controller $controller) {
		if (!empty($_GET['$callback'])) {
			return $_GET['$callback'];
		}
		return false;
	}

}
