<?php
/**
----------------------------------------------------------------------+
*  @desc			Unittests for PHPLinter.
----------------------------------------------------------------------+
*  @file 			Alltests.php
*  @author 			Jóhann T. Maríusson <jtm@robot.is>
*  @since 		    Oct 29, 2011
*  @package 		PHPLinter
*  @copyright     
*    phplinter is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------+
*/
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/Extensions/PhptTestSuite.php';

define('PLROOT', realpath(dirname(__FILE__) . '/../'));
require PLROOT . '/phplinter/autoloader.php';
require PLROOT . '/phplinter/constants.php';

class PHPLinterTest extends PHPUnit_Framework_TestCase {
	/**
	----------------------------------------------------------------------+
	* @desc 	Extract all test matches from file header
	----------------------------------------------------------------------+
	*/
	public function extract_test($file) {
		$flags = array();
		$lines = array();
		$rules = array();
		$score = false;
		foreach(file(PLROOT . '/tests/files/' . $file) as $line) {
			if(preg_match('/@flag([\sICWDRES0-9]+)L([0-9]+)/', $line, $m)) {
				$flags[] = trim($m[1]);
				$lines[] = trim($m[2]);
			}
			elseif(preg_match('/@score([\-\s\.0-9]+)/', $line, $m)) {
				$score = round(floatval(trim($m[1])), 2);
			}
			elseif(preg_match('/@rule([\sA-Z_]+)C(.*)/', $line, $m)) {
				$rules[trim($m[1])] = $m[2];
			}
			elseif(preg_match('/\*\//', $line, $m)) {
				break;
			}
		}
		return array($flags, $lines, $score, $rules);
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Run all unittests
	----------------------------------------------------------------------+
	*/
	public function test_run() {
		foreach(scandir(PLROOT . '/tests/files') as $_) {
        	if($_[0] === '.') continue;
        	$config = new phplinter\Config();
        	$config->setFlags(OPT_NO_FORMATTING);
        	list($flags, $lines, $score, $rules) = $this->extract_test($_);
        	if($rules) {
        		foreach($rules as $rule => $compare) {
        			$config->setRule($rule, array('compare' => $compare));
        		}
        	}
        	$fc = count($flags);
        	if($score !== false) {
        		$ll = new phplinter\Linter(PLROOT . '/tests/files/' . $_, 
        									$config);
        		$report = $ll->lint();
        		$this->assertEquals($score, $ll->score(), $_);
        		$this->assertEquals(count($report), $fc, $_);
				for($i = 0; $i < $fc; $i++) {
					$this->assertEquals($flags[$i], $report[$i]['flag'], $_);
					$this->assertEquals($lines[$i], $report[$i]['line'], $_);
				}
        	}
        }
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Validate that all PHPLinters files pass its own rules.
	----------------------------------------------------------------------+
	*/
	public function test_self() {
		$dir = PLROOT . '/phplinter/';
		$config = new phplinter\Config();
		foreach(phplinter\Path::find($dir, '/\.php$/') as $_) {
			$ll = new phplinter\Linter($_, $config);
			$ll->lint();
			$score = $ll->score();
			$this->assertTrue($score > 8.00, "$_: $score !> 8.00");
		}
	}
}
