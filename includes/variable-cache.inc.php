<?php
/**
 * @file
 * The file that serves all functions for SQL.
 * @package sqlinterface
 * @author Ron Mac Quarrie
 * @link http://www.it-werx.net
 * @license http://opensource.org/licenses/GPL-3.0
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 *
 */

/** variable_cache class. */
class variable_cache {
		
		/**
		 * variable_set function.
		 * 
		 * @access public
		 * @param mixed $name
		 * @param mixed $value
		 * @return $value Set a variable global.
		 */
		function variable_set($name, $value) {
		  global $conf;

		  db_merge('variable')->key(array('name' => $name))->fields(array('value' => serialize($value)))->execute();

		  cache_clear_all('variables', 'cache_bootstrap');

		  $conf[$name] = $value;
		}

		/**
		 * variable_get function.
		 * 
		 * @access public
		 * @param mixed $name
		 * @param mixed $default (default: NULL)
		 * @return $conf[array] Retrieve a variable.
		 */
		function variable_get($name, $default = NULL) {
		  global $conf;

		  return isset($conf[$name]) ? $conf[$name] : $default;
		}

		/**
		 * variable_del function.
		 * 
		 * @access public
		 * @param mixed $name Name of the variable
		 * @return $conf[$name] Delete a variable from cached variables.
		 */
		function variable_del($name) {
		  global $conf;

		  db_delete('variable')->condition('name', $name)->execute();
		  cache_clear_all('variables', 'cache_bootstrap');

		  unset($conf[$name]);
		}
}
?>