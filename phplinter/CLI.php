<?php
/**
----------------------------------------------------------------------+
*  @desc			CLI version.
----------------------------------------------------------------------+
*  @file 			Lint_class.php
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
namespace phplinter; 
class CLI {
	/* @var String */
	protected $target;
	/* @var Array */
	protected $use_rules;
	/* @var float */
	protected $penalty;
	/* @var Array */
	protected $stats;
	/* @var Config Object */
	protected $config;
	/**
	----------------------------------------------------------------------+
	* @desc 	__construct. Defaults to use color.
	----------------------------------------------------------------------+
	*/
	public function __construct() {
		$this->stats = array();
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Output help.
	----------------------------------------------------------------------+
	*/
	protected function help() {
		echo "PHPLinter. Lint and score PHP files.\n";
		echo "Usage phplinter [Options] [file|directory]\n\n";
		echo "\t-q: Quiet mode (Surpress output)\n";
		echo "\t-v: Verbose mode\n";
		echo "\t-N: Turn off color output.\n";
		echo "\t-S: Score only.\n";
		echo "\t-c FILE: Configuration file.\n";
		
		echo "\nFilters:\n";
		echo "\t-I: Report extra information (default off).\n";
		echo "\t-C: Dont report conventions.\n";
		echo "\t-W: Dont report warnings.\n";
		echo "\t-R: Dont report refactor warnings.\n";
		echo "\t-E: Dont report errors.\n";
		echo "\t-D: Dont report documentation warnings.\n";
		echo "\t-X: Dont report security warnings.\n";
		echo "\t-O: Security report only.\n";
		
		echo "\nDebugging:\n";
		echo "\t-M: View scope map.\n";
		echo "\t-V: Debug mode. Again for extra debug info.\n";
		echo "\t-T: Time execution. Again for extra time info.\n";
		
		echo "\nReporting:\n";
		echo "\t-H PATH: HTML report.\n";
		echo "\t-J PATH: JSON report.\n";
		echo "\t-i PATTERN: Ignore pattern (| delimited)\n";
		echo "\t-e PATTERN: file extensions (| delimited) (default 'php')\n";
		echo "\t-w: Overwrite output directory. (Warning: Will empty directory)\n";
		
		echo "\nOther (Experimental):\n";
		echo "\t-Z PATH: Harvest documentation into json-file.\n";
		echo "\t-F: Report formatting errors.\n";
		echo "<jtm@robot.is>\n";
		exit;
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Process commandline options
	* @param	Array
	* @param	Int
	----------------------------------------------------------------------+
	*/
	public function process_options($argv, $argc) {
		$flags = OPT_USE_COLOR | OPT_NO_INFORMATION | OPT_NO_FORMATTING;
		$options = array();
		for($i = 1;$i < $argc; $i++) {
			if($argv[$i][0] == '-') {
				$l = mb_strlen($argv[$i]);
				for($j=1; $j < $l; $j++) {
					switch($argv[$i][$j]) {
						case 'I':
							$flags &= ~OPT_NO_INFORMATION;
							break;
						case 'D':
							$flags |= OPT_NO_DEPRICATED;
							break;
						case 'C':
							$flags |= OPT_NO_CONVENTION;
							break;
						case 'F':
							$flags &= ~OPT_NO_FORMATTING;
							break;
						case 'W':
							$flags |= OPT_NO_WARNING;
							break;
						case 'R':
							$flags |= OPT_NO_REFACTOR;
							break;
						case 'E':
							$flags |= OPT_NO_ERROR;
							break;
						case 'X':
							$flags |= OPT_NO_SECURITY;
							break;
						case 'O':
							$flags |= OPT_ONLY_SECURITY;
							break;
						case 'v':
							$flags |= OPT_VERBOSE;
							break;
						case 'q':
							$flags |= OPT_QUIET;
							break;
						case 'M':
							$flags |= OPT_SCOPE_MAP;
							break;
						case 'V':
							if($flags & OPT_DEBUG) {
								$flags |= OPT_DEBUG_EXTRA;
							} else {
								$flags |= (OPT_DEBUG | OPT_VERBOSE);
							}
							break;
						case 'T':
							if($flags & OPT_DEBUG_TIME) {
								$flags |= OPT_DEBUG_TIME_EXTRA;
							}
							else $flags |= OPT_DEBUG_TIME;
							break;
						case 'S':
							$flags |= OPT_SCORE_ONLY;
							break;
						case 'N':
							$flags &= ~OPT_USE_COLOR;
							break;
						case 'H':
							$flags |= OPT_HTML_REPORT;
							$options['html'] = array(
								'out' => $this->consume($argv, $i)
							);
							continue 3;
						case 'J':
							$flags |= OPT_JSON_REPORT;
							$options['json_out'] = $this->consume($argv, $i);
							continue 3;
						case 'Z':
							$flags |= OPT_HARVEST_DOCS;
							$options['docs_out'] = $this->consume($argv, $i);
							continue 3;
						case 'w':
							$flags |= OPT_OVERWRITE_REPORT;
							break;
						case 'c':
							$conffile = $this->consume($argv, $i);
							continue 3;
						case 'i':
							$options['ignore'] = $this->consume($argv, $i);
							continue 3;
						case 't':
							$options['threshold'] = intval($argv[++$i]);
							continue 3;
						case 'e':
							$options['extensions'] = '';
							$ext = $this->consume($argv, $i);
							if(preg_match('/[a-z0-9\|]+/iu', $ext)) {
								$options['extensions'] .= '|'.$ext;
							} elseif(!empty($ext)) {
								$this->error('Extensions must include only letters and numbers');
							}
							continue 3;
						case 'h':
							$this->help();
						default:
							$this->error("Unrecognized option `{$argv[$i][$j]}`");
					}
				}
			} else {
				$options['target'] = $argv[$i];
			}
		}
		$this->config = empty($conffile) 
			? new Config()
			: new Config($conffile);
		// CLI switches override config-file
		$this->config->setFlags($flags);
		$this->config->setOptions($options);
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Consume next cli argument
	* @param	String
	----------------------------------------------------------------------+
	*/
	protected function consume($argv, &$i) {
		if(mb_strlen($argv[$i]) > 2) {
			return mb_substr($argv[$i], 2);
		}
		if(isset($argv[++$i])) return trim($argv[$i]);
		$i--;
		return false;
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Die with error message. Outputs help.
	* @param	String
	----------------------------------------------------------------------+
	*/
	protected function error($msg) {
		exit("$msg Try -h\n"); 
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Output message
	* @param	String
	----------------------------------------------------------------------+
	*/
	protected function msg($msg, $flag=OPT_VERBOSE) {
		if(!$this->config->check(OPT_QUIET)) {
			if($flag && !$this->config->check($flag))
				return;
			echo $msg;
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Analyse directory
	----------------------------------------------------------------------+
	*/
	protected function lint_directory() {
		$ext = $this->config->check('extensions');
		if(empty($ext)) $ext = 'php';
		$ignore = $this->config->check('ignore');
		$files = empty($ignore)
			? Path::find($this->target, "/^.*?\.($ext)$/u")
			: Path::find($this->target, "/^.*?\.($ext)$/u", $ignore);
			
		$verbose = $this->config->check(OPT_VERBOSE);
		$this->penalty = 0;
		$numfiles = count($files);
		$reports = array();
		$penaltys = array();
		foreach($files as $_) {
			$this->msg("Linting file: $_\n");
			if($this->config->check(OPT_DEBUG_TIME_EXTRA)) 
				$time = microtime(true);
			$linter = new Linter($_, $this->config);
			$report = $linter->lint();
			$penalty = $linter->penalty();
			$stats = array($_, $linter->score());
			if($this->config->check(OPT_REPORT)) {
				if($_[0] !== '/') {
					$href = (preg_match('/^\.\//u', $_)) 
								? $_ : "./$_";
				} else $href = $_;
				$reports[$href] = $report;
				$penaltys[$href] = $penalty;
			}
			$this->penalty += $penalty;
			$this->msg($this->reporter->score($penalty));
			if($this->config->check(OPT_DEBUG_TIME_EXTRA)) {
				$x = microtime(true) - $time;
				$stats[] = $x;
				$this->msg("Time for file: $x seconds\n");	
			}
			$this->stats[] = $stats;
		}
		$this->reporter->create($reports, null, $this->target);
		$cnt = count($this->stats);
		$this->msg("$cnt files, ", 0);	
		$this->msg($this->reporter->average($this->penalty, $numfiles), 0);
		$arr = array();
		foreach($this->stats as $_) $arr[] = $_[1];
		array_multisort($this->stats, SORT_NUMERIC, $arr);
		$this->msg("Worst: {$this->stats[0][0]} with {$this->stats[0][1]}\n", 0);
		if($this->config->check(OPT_DEBUG_TIME_EXTRA)) {
			$arr = array();
			foreach($this->stats as $_) $arr[] = $_[2];
			$avg = array_sum($arr) / $cnt;
			echo "Avarage time per file: $avg seconds\n";	
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Analyse single file
	* @param	String	Filename
	----------------------------------------------------------------------+
	*/
	protected function lint_file($file) {
		$linter = new Linter($file, $this->config);
		$this->reporter->create($linter->lint(), $linter->penalty());
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Lint target
	----------------------------------------------------------------------+
	*/
	public function lint() {
		$this->target = $this->config->check('target');
		if(!file_exists($this->target)) {
			$this->error('Need valid target...');
		}

		$this->setReport();
		
		if($this->config->check(OPT_DEBUG_TIME)) 
			$time = microtime(true);

		if(is_dir($this->target)) {
			$report = $this->lint_directory();
		} else {
			$report = $this->lint_file($this->target);
		}
		
		if($this->config->check(OPT_DEBUG_TIME)) {
			$x = microtime(true) - $time;
			$this->msg("Total time: $x seconds\n", OPT_DEBUG_TIME);	
		}
		if($this->config->check(OPT_DEBUG)) {
			$mem =  memory_get_peak_usage(true);
			echo 'Peak memory use: ' . round($mem/1048576,2) . " MiB\n";
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	FIXME
	----------------------------------------------------------------------+
	*/
	protected function setReport() {
		if($this->config->check(OPT_REPORT)) {
			if($this->config->check(OPT_HTML_REPORT)) {
				$this->reporter = new Report\Html($this->config);
			} else {
				$this->reporter = new Report\JSON($this->config);
			}
		} else {
			$this->reporter = new Report\Bash($this->config);
		}
		$err = $this->reporter->prepare();
		if($err !== true) $this->error($err);
	}
}