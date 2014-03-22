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
class variable-cache {
		
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


/**
 * cache_clear_all function.
 * Clear cached items.
 * @access public
 * @param mixed $cid (default: NULL)
 * @param mixed $bin (default: NULL)
 * @param mixed $wildcard (default: FALSE)
 * @return void
 */
function cache_clear_all($cid = NULL, $bin = NULL, $wildcard = FALSE) {
  if (!isset($cid) && !isset($bin)) {
    // Clear the block cache first, so stale data will
    // not end up in the page cache.
    if (module_exists('block')) {
      cache_clear_all(NULL, 'cache_block');
    }
    cache_clear_all(NULL, 'cache_page');
    return;
  }
  return _cache_get_object($bin)->clear($cid, $wildcard);
}
?>